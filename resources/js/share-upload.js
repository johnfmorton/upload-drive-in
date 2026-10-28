/**
 * "Send files" page: collects the share details and files, creates a draft
 * share, uploads the files into it in 5MB chunks, then finalizes it.
 *
 * Loaded only on the create-share page (see shares/create.blade.php).
 */
import Dropzone from "dropzone";
import "dropzone/dist/dropzone.css";

Dropzone.autoDiscover = false;

const root = document.getElementById("share-create");

if (root) {
    initShareUpload(root);
}

export function initShareUpload(root) {
    const form = root.querySelector("form");
    const submitButton = root.querySelector("[data-submit]");
    const errorBox = root.querySelector("[data-error]");
    const progressBox = root.querySelector("[data-progress]");
    const progressBar = root.querySelector("[data-progress-bar]");
    const progressText = root.querySelector("[data-progress-text]");
    const summary = root.querySelector("[data-summary]");
    const strings = JSON.parse(root.dataset.strings);
    const maxFileSize = Number(root.dataset.maxFileSize);
    const maxShareSize = Number(root.dataset.maxShareSize);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    let urls = null; // set once the draft share exists
    let uploading = false;

    const dropzone = new Dropzone(root.querySelector("[data-dropzone]"), {
        url: () => urls.upload_url,
        paramName: "file",
        maxFilesize: null, // checked below against the server's limits instead
        chunking: true,
        forceChunking: true,
        chunkSize: 5 * 1024 * 1024,
        retryChunks: true,
        retryChunksLimit: 3,
        parallelChunkUploads: false, // pion needs chunks in order
        parallelUploads: 2,
        autoProcessQueue: false,
        addRemoveLinks: true,
        timeout: 0,
        headers: { "X-CSRF-TOKEN": csrfToken },
        dictDefaultMessage: strings.drop,
        dictRemoveFile: strings.remove,
        dictCancelUpload: strings.cancel,
        params(files, xhr, chunk) {
            if (!chunk) {
                return {};
            }

            return {
                dzuuid: chunk.file.upload.uuid,
                dzchunkindex: chunk.index,
                dztotalfilesize: chunk.file.size,
                dzchunksize: this.options.chunkSize,
                dztotalchunkcount: chunk.file.upload.totalChunkCount,
                dzchunkbyteoffset: chunk.index * this.options.chunkSize,
            };
        },
        accept(file, done) {
            if (file.size > maxFileSize) {
                done(strings.fileTooLarge);
            } else if (totalSize() > maxShareSize) {
                done(strings.shareTooLarge);
            } else {
                done();
            }
        },
    });

    function totalSize() {
        // Includes the file being accepted (accepted is still undefined then)
        return dropzone.files
            .filter((file) => file.accepted !== false)
            .reduce((sum, file) => sum + file.size, 0);
    }

    function acceptedFiles() {
        return dropzone.files.filter((file) => file.accepted);
    }

    function updateSummary() {
        const files = acceptedFiles();
        const bytes = files.reduce((sum, file) => sum + file.size, 0);
        summary.textContent = files.length
            ? strings.summary
                  .replace(":count", files.length)
                  .replace(":size", formatBytes(bytes))
            : "";
    }

    function showError(message) {
        errorBox.textContent = message;
        errorBox.hidden = !message;
    }

    dropzone.on("addedfile", updateSummary);
    dropzone.on("removedfile", (file) => {
        updateSummary();

        // Already uploaded into the draft: delete it there too
        if (file.deleteUrl) {
            fetch(file.deleteUrl, {
                method: "DELETE",
                headers: { "X-CSRF-TOKEN": csrfToken, Accept: "application/json" },
            });
        }
    });

    dropzone.on("success", (file, response) => {
        // Intermediate chunks answer {status: true}; the last one returns the file
        if (response && response.delete_url) {
            file.deleteUrl = response.delete_url;
        }
    });

    dropzone.on("error", (file, message) => {
        if (typeof message === "object") {
            message = message.error || message.message || strings.uploadFailed;
        }
        file.previewElement
            ?.querySelector("[data-dz-errormessage]")
            ?.replaceChildren(document.createTextNode(message));
    });

    dropzone.on("totaluploadprogress", (percent, totalBytes, sentBytes) => {
        progressBar.style.width = `${percent}%`;
        progressText.textContent = strings.progress
            .replace(":sent", formatBytes(sentBytes))
            .replace(":total", formatBytes(totalBytes));
    });

    dropzone.on("queuecomplete", async () => {
        if (!uploading) {
            return;
        }

        const failed = dropzone.files.filter((file) => file.status === Dropzone.ERROR);
        if (failed.length) {
            setUploading(false);
            submitButton.textContent = strings.retry;
            showError(strings.someFailed);
            return;
        }

        try {
            const response = await postJson(urls.finalize_url, {});
            window.removeEventListener("beforeunload", warnBeforeLeaving);
            window.location.assign(response.show_url);
        } catch (error) {
            setUploading(false);
            showError(error.message);
        }
    });

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        showError("");

        if (!acceptedFiles().length) {
            showError(strings.addFiles);
            return;
        }

        // Read the fields before setUploading() disables them (FormData skips disabled fields)
        const payload = urls ? null : formPayload();
        setUploading(true);

        try {
            if (!urls) {
                urls = await postJson(form.action, payload);
            }
        } catch (error) {
            setUploading(false);
            showError(error.message);
            return;
        }

        // Retrying: put failed files back in the queue
        dropzone.files
            .filter((file) => file.status === Dropzone.ERROR && file.accepted)
            .forEach((file) => {
                file.status = Dropzone.QUEUED;
                file.previewElement?.classList.remove("dz-error");
            });

        dropzone.processQueue();
    });

    function formPayload() {
        const data = Object.fromEntries(new FormData(form));
        data.recipient_emails = (data.recipient_emails || "")
            .split(/[\s,;]+/)
            .map((email) => email.trim())
            .filter(Boolean);
        return data;
    }

    function setUploading(value) {
        uploading = value;
        submitButton.disabled = value;
        progressBox.hidden = !value && !progressBar.style.width;
        form.querySelectorAll("input, textarea, select").forEach((field) => {
            // Details can't change once the draft exists
            field.disabled = value || Boolean(urls);
        });

        if (value) {
            submitButton.textContent = strings.uploading;
            window.addEventListener("beforeunload", warnBeforeLeaving);
        } else {
            window.removeEventListener("beforeunload", warnBeforeLeaving);
        }
    }

    async function postJson(url, data) {
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify(data),
        });
        const body = await response.json().catch(() => ({}));

        if (!response.ok) {
            const firstError = body.errors && Object.values(body.errors)[0]?.[0];
            throw new Error(firstError || body.message || strings.uploadFailed);
        }

        return body;
    }
}

function warnBeforeLeaving(event) {
    event.preventDefault();
    event.returnValue = "";
}

export function formatBytes(bytes) {
    const units = ["B", "KB", "MB", "GB", "TB"];
    let value = bytes;
    let unit = 0;

    while (value >= 1024 && unit < units.length - 1) {
        value /= 1024;
        unit++;
    }

    return `${value.toFixed(unit === 0 ? 0 : 1)} ${units[unit]}`;
}
