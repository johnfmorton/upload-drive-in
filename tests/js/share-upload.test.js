/**
 * Tests for the "Send files" page (resources/js/share-upload.js).
 *
 * Dropzone is replaced with a small fake that records its options and event
 * handlers, so the tests drive the page's own logic: size limits, the
 * summary, creating the draft share, retrying failed files and finalizing.
 */

jest.mock("dropzone", () => {
    class FakeDropzone {
        constructor(element, options) {
            this.element = element;
            this.options = options;
            this.files = [];
            this.handlers = {};
            this.processQueue = jest.fn();
            FakeDropzone.instances.push(this);
        }

        on(event, handler) {
            (this.handlers[event] ||= []).push(handler);
        }

        emit(event, ...args) {
            (this.handlers[event] || []).forEach((handler) => handler.apply(this, args));
        }
    }

    FakeDropzone.ERROR = "error";
    FakeDropzone.QUEUED = "queued";
    FakeDropzone.SUCCESS = "success";
    FakeDropzone.instances = [];

    return { __esModule: true, default: FakeDropzone };
});
jest.mock("dropzone/dist/dropzone.css", () => ({}), { virtual: true });

import Dropzone from "dropzone";
import { initShareUpload, formatBytes } from "../../resources/js/share-upload.js";

const MB = 1024 * 1024;

const strings = {
    drop: "Drop files",
    remove: "Remove",
    cancel: "Cancel",
    fileTooLarge: "File too large",
    shareTooLarge: "Share too large",
    summary: ":count files, :size",
    progress: ":sent of :total",
    uploading: "Uploading…",
    retry: "Retry failed files",
    someFailed: "Some files failed",
    addFiles: "Add a file",
    uploadFailed: "Upload failed",
};

const urls = {
    upload_url: "/admin/shares/01ABC/files",
    finalize_url: "/admin/shares/01ABC/finalize",
};

function buildPage({ maxFileSize = 10 * MB, maxShareSize = 15 * MB } = {}) {
    document.head.innerHTML = '<meta name="csrf-token" content="test-token">';
    document.body.innerHTML = `
        <div id="share-create"
             data-max-file-size="${maxFileSize}"
             data-max-share-size="${maxShareSize}">
            <form action="/admin/shares">
                <input name="title" value="Quarterly report">
                <div data-dropzone></div>
                <span data-summary></span>
                <textarea name="message">Here you go</textarea>
                <select name="expiry_days"><option value="14" selected>14</option></select>
                <input name="password" type="password" value="">
                <textarea name="recipient_emails">a@example.com, b@example.com; c@example.com</textarea>
                <p data-error hidden></p>
                <div data-progress hidden>
                    <div data-progress-bar style="width: 0"></div>
                    <p data-progress-text></p>
                </div>
                <button data-submit>Upload and create</button>
            </form>
        </div>`;

    const root = document.getElementById("share-create");
    root.dataset.strings = JSON.stringify(strings);

    initShareUpload(root);

    return {
        root,
        form: root.querySelector("form"),
        dropzone: Dropzone.instances[Dropzone.instances.length - 1],
        error: root.querySelector("[data-error]"),
        submit: root.querySelector("[data-submit]"),
        summary: root.querySelector("[data-summary]"),
    };
}

/** Add a file the way Dropzone does: push it, run accept(), fire addedfile. */
function addFile(dropzone, name, size) {
    const file = { name, size, upload: { uuid: `uuid-${name}`, totalChunkCount: 1 } };
    dropzone.files.push(file);

    let rejection;
    dropzone.options.accept.call(dropzone, file, (message) => {
        rejection = message;
    });
    file.accepted = !rejection;
    file.status = rejection ? Dropzone.ERROR : Dropzone.QUEUED;
    dropzone.emit("addedfile", file);

    return { file, rejection };
}

function jsonResponse(body, status = 200) {
    return Promise.resolve({
        ok: status >= 200 && status < 300,
        status,
        json: () => Promise.resolve(body),
    });
}

function submitForm(form) {
    form.dispatchEvent(new Event("submit", { cancelable: true }));
}

/** Let the async submit and queuecomplete handlers finish. */
const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

beforeEach(() => {
    Dropzone.instances.length = 0;
    global.fetch = jest.fn();
});

describe("formatBytes", () => {
    test("formats bytes with one decimal above 1 KB", () => {
        expect(formatBytes(0)).toBe("0 B");
        expect(formatBytes(512)).toBe("512 B");
        expect(formatBytes(1536)).toBe("1.5 KB");
        expect(formatBytes(5 * 1024 * MB)).toBe("5.0 GB");
    });
});

describe("Dropzone setup", () => {
    test("uploads in ordered 5MB chunks and waits for the form to be submitted", () => {
        const { dropzone } = buildPage();

        expect(dropzone.options.chunking).toBe(true);
        expect(dropzone.options.forceChunking).toBe(true);
        expect(dropzone.options.chunkSize).toBe(5 * MB);
        expect(dropzone.options.parallelChunkUploads).toBe(false);
        expect(dropzone.options.autoProcessQueue).toBe(false);
        expect(dropzone.options.headers).toEqual({ "X-CSRF-TOKEN": "test-token" });
    });

    test("sends the chunk details pion expects", () => {
        const { dropzone } = buildPage();
        const file = { size: 12 * MB, upload: { uuid: "abc", totalChunkCount: 3 } };

        const params = dropzone.options.params.call(dropzone, [file], null, { file, index: 2 });

        expect(params).toEqual({
            dzuuid: "abc",
            dzchunkindex: 2,
            dztotalfilesize: 12 * MB,
            dzchunksize: 5 * MB,
            dztotalchunkcount: 3,
            dzchunkbyteoffset: 10 * MB,
        });
        expect(dropzone.options.params.call(dropzone, [file], null, undefined)).toEqual({});
    });
});

describe("size limits", () => {
    test("rejects a file over the per-file limit", () => {
        const { dropzone } = buildPage({ maxFileSize: 10 * MB });

        expect(addFile(dropzone, "small.bin", 9 * MB).rejection).toBeUndefined();
        expect(addFile(dropzone, "big.bin", 11 * MB).rejection).toBe("File too large");
    });

    test("rejects a file that takes the share over its total limit", () => {
        const { dropzone } = buildPage({ maxFileSize: 10 * MB, maxShareSize: 15 * MB });

        addFile(dropzone, "one.bin", 8 * MB);

        expect(addFile(dropzone, "two.bin", 8 * MB).rejection).toBe("Share too large");
        // The rejected file no longer counts towards the total
        expect(addFile(dropzone, "three.bin", 7 * MB).rejection).toBeUndefined();
    });

    test("the summary counts only accepted files", () => {
        const { dropzone, summary } = buildPage({ maxFileSize: 10 * MB });

        addFile(dropzone, "one.bin", 2 * MB);
        addFile(dropzone, "big.bin", 11 * MB);
        addFile(dropzone, "two.bin", 1 * MB);

        expect(summary.textContent).toBe("2 files, 3.0 MB");
    });
});

describe("submitting", () => {
    test("asks for a file when none were added", async () => {
        const { form, error } = buildPage();

        submitForm(form);
        await flush();

        expect(error.hidden).toBe(false);
        expect(error.textContent).toBe("Add a file");
        expect(fetch).not.toHaveBeenCalled();
    });

    test("creates the draft share, then starts the upload", async () => {
        const { form, dropzone, submit } = buildPage();
        addFile(dropzone, "report.pdf", 1 * MB);
        fetch.mockReturnValueOnce(jsonResponse(urls));

        submitForm(form);
        await flush();

        expect(fetch).toHaveBeenCalledTimes(1);
        const [url, request] = fetch.mock.calls[0];
        expect(url).toBe("http://localhost/admin/shares"); // form.action is absolute
        expect(request.method).toBe("POST");
        expect(request.headers["X-CSRF-TOKEN"]).toBe("test-token");
        expect(JSON.parse(request.body)).toEqual({
            title: "Quarterly report",
            message: "Here you go",
            expiry_days: "14",
            password: "",
            recipient_emails: ["a@example.com", "b@example.com", "c@example.com"],
        });

        expect(dropzone.processQueue).toHaveBeenCalled();
        expect(dropzone.options.url()).toBe(urls.upload_url);
        expect(submit.disabled).toBe(true);
        expect(submit.textContent).toBe("Uploading…");
        expect(form.querySelector("[name=title]").disabled).toBe(true);
    });

    test("shows the server's first validation error and lets the sender try again", async () => {
        const { form, dropzone, error, submit } = buildPage();
        addFile(dropzone, "report.pdf", 1 * MB);
        fetch.mockReturnValueOnce(
            jsonResponse({ message: "Invalid", errors: { title: ["The title is required."] } }, 422)
        );

        submitForm(form);
        await flush();

        expect(error.textContent).toBe("The title is required.");
        expect(dropzone.processQueue).not.toHaveBeenCalled();
        expect(submit.disabled).toBe(false);
        expect(form.querySelector("[name=title]").disabled).toBe(false);
    });
});

describe("after the upload", () => {
    async function startUpload() {
        const page = buildPage();
        const added = addFile(page.dropzone, "report.pdf", 1 * MB);
        fetch.mockReturnValueOnce(jsonResponse(urls));
        submitForm(page.form);
        await flush();
        return { ...page, file: added.file };
    }

    test("finalizes the share once every file has uploaded", async () => {
        const { dropzone, file, error } = await startUpload();
        file.status = Dropzone.SUCCESS;
        fetch.mockReturnValueOnce(jsonResponse({ show_url: "/admin/shares/01ABC" }));
        // jsdom logs "navigation not implemented" when the page redirects to show_url
        const consoleError = jest.spyOn(console, "error").mockImplementation(() => {});

        try {
            dropzone.emit("queuecomplete");
            await flush();
        } finally {
            consoleError.mockRestore();
        }

        expect(fetch).toHaveBeenCalledTimes(2);
        expect(fetch.mock.calls[1][0]).toBe(urls.finalize_url);
        expect(error.hidden).toBe(true);
    });

    test("offers a retry when a file failed, and reuses the draft for it", async () => {
        const { form, dropzone, file, error, submit } = await startUpload();
        file.status = Dropzone.ERROR;

        dropzone.emit("queuecomplete");
        await flush();

        expect(error.textContent).toBe("Some files failed");
        expect(submit.textContent).toBe("Retry failed files");
        expect(submit.disabled).toBe(false);
        // The draft exists, so its details stay locked
        expect(form.querySelector("[name=title]").disabled).toBe(true);
        expect(fetch).toHaveBeenCalledTimes(1);

        submitForm(form);
        await flush();

        expect(fetch).toHaveBeenCalledTimes(1); // no second draft
        expect(file.status).toBe(Dropzone.QUEUED);
        expect(dropzone.processQueue).toHaveBeenCalledTimes(2);
    });

    test("ignores queuecomplete when no upload is running", async () => {
        const { dropzone } = buildPage();

        dropzone.emit("queuecomplete");
        await flush();

        expect(fetch).not.toHaveBeenCalled();
    });

    test("removing an uploaded file deletes it from the draft", () => {
        const { dropzone } = buildPage();
        const { file } = addFile(dropzone, "report.pdf", 1 * MB);
        fetch.mockReturnValue(jsonResponse({}));

        // Intermediate chunks don't carry a delete URL; the last one does
        dropzone.emit("success", file, { status: true });
        expect(file.deleteUrl).toBeUndefined();
        dropzone.emit("success", file, { delete_url: "/admin/shares/01ABC/files/7" });

        dropzone.emit("removedfile", file);

        expect(fetch).toHaveBeenCalledWith(
            "/admin/shares/01ABC/files/7",
            expect.objectContaining({ method: "DELETE" })
        );
    });

    test("removing a file that was never uploaded makes no request", () => {
        const { dropzone } = buildPage();
        const { file } = addFile(dropzone, "report.pdf", 1 * MB);

        dropzone.emit("removedfile", file);

        expect(fetch).not.toHaveBeenCalled();
    });

    test("shows upload progress", () => {
        const { root, dropzone } = buildPage();

        dropzone.emit("totaluploadprogress", 50, 4 * MB, 2 * MB);

        expect(root.querySelector("[data-progress-bar]").style.width).toBe("50%");
        expect(root.querySelector("[data-progress-text]").textContent).toBe("2.0 MB of 4.0 MB");
    });
});
