import "./bootstrap";

// Import File Manager modules
import "./file-manager-coordination";

// Import Setup Wizard functionality
import "./setup-wizard";

// Import Admin Queue Testing functionality
import "./admin-queue-testing";

// Import Dropzone CSS
import "dropzone/dist/dropzone.css";

// Shoelace CSS
import "@shoelace-style/shoelace/dist/themes/light.css";
// import '@shoelace-style/shoelace/dist/shoelace.css';
// import Shoelace color picker
// import '@shoelace-style/shoelace/dist/components/color-picker/color-picker.css';
import "@shoelace-style/shoelace/dist/components/color-picker/color-picker.js";

import Alpine from "alpinejs";
import persist from "@alpinejs/persist";

window.Alpine = Alpine;
Alpine.plugin(persist);

// Alpine.magic('clipboard', () => {
//   return (subject) => navigator.clipboard.writeText(subject);
// });

Alpine.start();

// --- Dropzone Initialization ---
import Dropzone from "dropzone";

// Prevent Dropzone from automatically discovering elements
Dropzone.autoDiscover = false;

// Check if the Dropzone element exists
const dropzoneElement = document.getElementById("file-upload-dropzone");
const messageForm = document.getElementById("messageForm"); // Get the message form
const messageInput = document.getElementById("message"); // Get the message input
const fileIdsInput = document.getElementById("file_upload_ids"); // Hidden input for IDs

if (dropzoneElement && messageForm && messageInput && fileIdsInput) {
    // Get CSRF token and upload URL
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content");
    const uploadUrl = dropzoneElement.dataset.uploadUrl;

    if (!uploadUrl) {
        console.error(
            "Dropzone element is missing the data-upload-url attribute!"
        );
    } else {
        const myDropzone = new Dropzone("#file-upload-dropzone", {
            url: uploadUrl,
            paramName: "file", // Field name for the file
            maxFilesize: 5000, // Max file size in MB (adjust as needed)
            chunking: true,
            forceChunking: true,
            chunkSize: 5 * 1024 * 1024, // Chunk size in bytes (5MB)
            retryChunks: true, // Retry failed chunks
            retryChunksLimit: 3,
            parallelChunkUploads: false, // Upload chunks sequentially for pion
            addRemoveLinks: true,
            autoProcessQueue: false,
            // dictDefaultMessage: "Drop files here or click to upload",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
            },
            // Must match the parameters expected by Pion's Dropzone handler
            params: function (files, xhr, chunk) {
                const params = {};

                // Add chunk parameters if this is a chunked upload
                if (chunk) {
                    params.dzuuid = chunk.file.upload.uuid;
                    params.dzchunkindex = chunk.index;
                    params.dztotalfilesize = chunk.file.size;
                    params.dzchunksize = this.options.chunkSize;
                    params.dztotalchunkcount =
                        chunk.file.upload.totalChunkCount;
                    params.dzchunkbyteoffset =
                        chunk.index * this.options.chunkSize;
                }

                // Add selected company user ID if available
                const companyUserSelect =
                    document.getElementById("company_user_id");
                if (companyUserSelect && companyUserSelect.value) {
                    params.company_user_id = companyUserSelect.value;
                }

                return params;
            },
            uploadprogress: function (file, progress, bytesSent) {
                // Update our custom progress display
                updateFileProgress(file, progress);
                updateOverallProgress();
            },
            success: function (file, response) {
                // This callback can be triggered for each chunk OR for the final request.
                // For pion/laravel-chunk-upload, the final response (when finished) contains the file details.
                // Responses for intermediate chunks might be simple {status: true} messages.
                console.log(`Success callback for ${file.name}:`, response);

                // Check if the server response contains the final file details
                if (response && response.file_upload_id) {
                    console.log(
                        `Final FileUpload ID for ${file.name}: ${response.file_upload_id}`
                    );

                    // Use a flag on the file object to prevent adding the ID multiple times if success is called per chunk
                    if (!file.finalIdReceived) {
                        file.finalIdReceived = true; // Mark that we got the final ID
                        file.file_upload_id = response.file_upload_id; // Store it on the file object for reference

                        // Mark file as complete in progress display
                        markFileComplete(file, true);

                        // Add the ID to our hidden input for the form submission
                        let currentIds = fileIdsInput.value
                            ? JSON.parse(fileIdsInput.value)
                            : [];
                        if (!currentIds.includes(response.file_upload_id)) {
                            currentIds.push(response.file_upload_id);
                            fileIdsInput.value = JSON.stringify(currentIds);
                            console.log(
                                "Updated file_upload_ids:",
                                fileIdsInput.value
                            );
                        }
                    }
                } else {
                    console.log(
                        `Received intermediate chunk success for ${file.name}`
                    );
                }
            },
            error: function (file, message, xhr) {
                console.error(
                    "Error uploading file chunk:",
                    file.name,
                    message,
                    xhr
                );

                // Mark file as failed in progress display
                markFileComplete(file, false);

                const errorDisplay = document.getElementById("upload-errors");
                if (errorDisplay) {
                    // Handle both string and object error messages
                    const errorMessage =
                        typeof message === "object"
                            ? message.error || JSON.stringify(message)
                            : message;
                    errorDisplay.innerHTML += `<p class="text-red-500">Error uploading ${file.name}: ${errorMessage}</p>`;
                    errorDisplay.classList.remove("hidden");
                }
            },
            complete: function (file) {
                // Optional: handle file completion if needed beyond 'success'
                console.log(
                    "File processing complete (success or error): ",
                    file.name
                );
                // After a file completes, try processing the next one in the queue
                myDropzone.processQueue();
            },
            // queuecomplete: function() {
            //     console.log("All files in the queue have been processed.");
            //     // This might be a better place to enable the submit button if autoProcessQueue is true
            // }
        });

        // --- Progress tracking functions ---
        function showProgressOverlay() {
            const overlay = document.getElementById("upload-progress-overlay");
            if (overlay) {
                overlay.classList.remove("hidden");
                overlay.classList.add("flex");
            }
        }

        function hideProgressOverlay() {
            const overlay = document.getElementById("upload-progress-overlay");
            if (overlay) {
                overlay.classList.add("hidden");
                overlay.classList.remove("flex");
            }
        }

        function updateFileProgress(file, progress) {
            const progressContainer = document.getElementById(
                "file-progress-container"
            );
            if (!progressContainer) return;

            let fileProgressElement = document.getElementById(
                `progress-${file.upload.uuid}`
            );

            if (!fileProgressElement) {
                // Create progress element for this file
                fileProgressElement = document.createElement("div");
                fileProgressElement.id = `progress-${file.upload.uuid}`;
                fileProgressElement.className = "mb-3 last:mb-0";
                fileProgressElement.innerHTML = `
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700 truncate max-w-xs" title="${
                            file.name
                        }">${file.name}</span>
                        <span class="text-sm text-gray-500">${Math.round(
                            progress
                        )}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300 ease-out" style="width: ${progress}%"></div>
                    </div>
                `;
                progressContainer.appendChild(fileProgressElement);
            } else {
                // Update existing progress
                const percentageSpan =
                    fileProgressElement.querySelector(".text-gray-500");
                const progressBar =
                    fileProgressElement.querySelector(".bg-blue-600");

                if (percentageSpan)
                    percentageSpan.textContent = `${Math.round(progress)}%`;
                if (progressBar) progressBar.style.width = `${progress}%`;
            }
        }

        function updateOverallProgress() {
            const files = myDropzone.files;
            if (files.length === 0) return;

            let totalProgress = 0;
            let completedFiles = 0;
            let uploadingFiles = 0;
            let errorFiles = 0;

            files.forEach((file) => {
                if (file.status === Dropzone.SUCCESS) {
                    totalProgress += 100;
                    completedFiles++;
                } else if (file.status === Dropzone.UPLOADING) {
                    totalProgress += file.upload.progress || 0;
                    uploadingFiles++;
                } else if (file.status === Dropzone.ERROR) {
                    errorFiles++;
                    completedFiles++; // Count errors as "completed" for progress calculation
                }
            });

            const overallProgress = totalProgress / files.length;
            const overallProgressBar = document.getElementById(
                "overall-progress-bar"
            );
            const overallProgressText = document.getElementById(
                "overall-progress-text"
            );
            const progressStatus = document.getElementById("progress-status");

            if (overallProgressBar) {
                overallProgressBar.style.width = `${overallProgress}%`;

                // Change color based on status
                if (errorFiles > 0 && completedFiles === files.length) {
                    overallProgressBar.classList.remove(
                        "bg-blue-600",
                        "bg-green-600"
                    );
                    overallProgressBar.classList.add("bg-yellow-600");
                } else if (
                    completedFiles === files.length &&
                    errorFiles === 0
                ) {
                    overallProgressBar.classList.remove(
                        "bg-blue-600",
                        "bg-yellow-600"
                    );
                    overallProgressBar.classList.add("bg-green-600");
                }
            }

            if (overallProgressText) {
                overallProgressText.textContent = `${Math.round(
                    overallProgress
                )}%`;
            }

            if (progressStatus) {
                if (completedFiles === files.length) {
                    if (errorFiles > 0) {
                        progressStatus.textContent = `Upload completed with ${errorFiles} error${
                            errorFiles > 1 ? "s" : ""
                        }`;
                    } else {
                        progressStatus.textContent = "Processing uploads...";
                    }
                } else {
                    const remainingFiles = files.length - completedFiles;
                    progressStatus.textContent = `Uploading ${remainingFiles} of ${files.length} files...`;
                }
            }
        }

        function markFileComplete(file, success = true) {
            const fileProgressElement = document.getElementById(
                `progress-${file.upload.uuid}`
            );
            if (!fileProgressElement) return;

            const progressBar =
                fileProgressElement.querySelector(".bg-blue-600");
            const percentageSpan =
                fileProgressElement.querySelector(".text-gray-500");

            if (success) {
                if (progressBar) {
                    progressBar.classList.remove("bg-blue-600");
                    progressBar.classList.add("bg-green-600");
                    progressBar.style.width = "100%";
                }
                if (percentageSpan) {
                    percentageSpan.textContent = "✓ Complete";
                    percentageSpan.classList.remove("text-gray-500");
                    percentageSpan.classList.add("text-green-600");
                }
            } else {
                if (progressBar) {
                    progressBar.classList.remove("bg-blue-600");
                    progressBar.classList.add("bg-red-600");
                }
                if (percentageSpan) {
                    percentageSpan.textContent = "✗ Failed";
                    percentageSpan.classList.remove("text-gray-500");
                    percentageSpan.classList.add("text-red-600");
                }
            }
        }

        function clearProgressDisplay() {
            const progressContainer = document.getElementById(
                "file-progress-container"
            );
            if (progressContainer) {
                progressContainer.innerHTML = "";
            }

            const overallProgressBar = document.getElementById(
                "overall-progress-bar"
            );
            if (overallProgressBar) {
                overallProgressBar.style.width = "0%";
                overallProgressBar.classList.remove(
                    "bg-green-600",
                    "bg-yellow-600"
                );
                overallProgressBar.classList.add("bg-blue-600");
            }

            const overallProgressText = document.getElementById(
                "overall-progress-text"
            );
            if (overallProgressText) {
                overallProgressText.textContent = "0%";
            }

            const progressStatus = document.getElementById("progress-status");
            if (progressStatus) {
                progressStatus.textContent = "Preparing upload...";
            }
        }

        // Prevent accidental navigation during upload
        let uploadInProgress = false;

        function setUploadInProgress(inProgress) {
            uploadInProgress = inProgress;
            if (inProgress) {
                window.addEventListener("beforeunload", handleBeforeUnload);
            } else {
                window.removeEventListener("beforeunload", handleBeforeUnload);
            }
        }

        function handleBeforeUnload(e) {
            if (uploadInProgress) {
                const message =
                    "Files are currently uploading. Are you sure you want to leave?";
                e.preventDefault();
                e.returnValue = message;
                return message;
            }
        }

        // --- Handle message form submission ---
        messageForm.addEventListener("submit", function (e) {
            e.preventDefault(); // Prevent default form submission
            const submitButton = this.querySelector('button[type="submit"]');
            const queuedFiles = myDropzone.getQueuedFiles(); // Get files waiting
            const filesInProgress = myDropzone.getFilesWithStatus(
                Dropzone.UPLOADING
            ); // Files currently uploading (should be 0 or 1)
            const filesDone =
                myDropzone.getFilesWithStatus(Dropzone.SUCCESS).length +
                myDropzone.getFilesWithStatus(Dropzone.ERROR).length; // Files already processed

            console.log(
                `Submit triggered. Queued: ${queuedFiles.length}, InProgress: ${filesInProgress.length}, Done: ${filesDone}`
            ); // <-- ADD LOG

            // Check if there are files to upload
            if (queuedFiles.length > 0) {
                console.log("Starting file uploads for queue..."); // <-- ADD LOG
                submitButton.disabled = true;
                submitButton.textContent = "Uploading Files...";

                // Show progress overlay and clear any previous progress
                clearProgressDisplay();
                showProgressOverlay();
                setUploadInProgress(true);

                myDropzone.processQueue(); // Start uploading queued files
            } else if (
                myDropzone.getFilesWithStatus(Dropzone.SUCCESS).length > 0
            ) {
                // Files already uploaded, but maybe the message wasn't sent (e.g., error)
                // We can trigger the association logic directly here if needed,
                // but the queuecomplete event is generally safer.
                console.log(
                    "Files already uploaded, attempting to associate message via queuecomplete."
                );
                // Trigger queuecomplete manually? Or rely on it having fired?
                // For simplicity, let's assume queuecomplete will handle association.
                // If there were previous uploads, and the user hits submit again,
                // queuecomplete might need logic to handle this (e.g., check if message exists).
                // Let's initially focus on the primary flow.
                // We could potentially call the message association logic directly if needed:
                // associateMessageWithUploads(messageInput.value, fileIdsInput.value);
                // Dispatch event to show a specific modal (example, might need a different one)
                console.log(
                    "Submit triggered, but files seem already uploaded."
                ); // <-- ADD LOG
                window.dispatchEvent(
                    new CustomEvent("open-modal", { detail: "upload-error" })
                ); // Or a more specific modal name
            } else {
                // alert('Please add files to upload.');
                console.log("Submit triggered, but no files added."); // <-- ADD LOG
                window.dispatchEvent(
                    new CustomEvent("open-modal", { detail: "no-files-error" })
                );
            }
        });

        // --- Add queuecomplete listener for message association ---
        myDropzone.on("queuecomplete", function () {
            const finishedFiles =
                myDropzone.getFilesWithStatus(Dropzone.SUCCESS).length +
                myDropzone.getFilesWithStatus(Dropzone.ERROR).length;
            const totalFilesAdded = myDropzone.files.length; // Total files ever added to this instance
            console.log(
                `--- Queue Complete Fired --- Processed: ${finishedFiles}, Total Added: ${totalFilesAdded}`
            ); // <-- MODIFIED LOG

            // console.log("--- Queue Complete Fired ---"); // <-- LOG: Start of handler
            const submitButton = messageForm.querySelector(
                'button[type="submit"]'
            );
            const message = messageInput.value;
            const successfullyUploadedFiles = myDropzone.getFilesWithStatus(
                Dropzone.SUCCESS
            );

            // Get the stored file IDs from the successful files
            const successfulFileIds = successfullyUploadedFiles
                .map((file) => file.file_upload_id) // Assumes ID was stored on file object in 'success' callback
                .filter((id) => id); // Filter out any undefined IDs

            console.log("Queue complete. Message:", message); // <-- LOG: Message value
            console.log(
                "Queue complete. Successful file IDs:",
                successfulFileIds
            ); // <-- LOG: IDs found

            if (message && successfulFileIds.length > 0) {
                console.log("Attempting to associate message..."); // <-- LOG: Associating message path
                submitButton.textContent = "Associating Message..."; // Update button text

                // Update progress status
                const progressStatus =
                    document.getElementById("progress-status");
                if (progressStatus) {
                    progressStatus.textContent =
                        "Associating message with uploaded files...";
                }

                // Use employee upload config if available, otherwise use client endpoint
                const associateUrl = window.employeeUploadConfig
                    ? window.employeeUploadConfig.associateMessageUrl
                    : "/client/uploads/associate-message";

                fetch(associateUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                    body: JSON.stringify({
                        message: message,
                        file_upload_ids: successfulFileIds,
                    }),
                })
                    .then((response) => {
                        if (!response.ok) {
                            response.text().then((text) => {
                                console.error(
                                    "Error response from associate-message:",
                                    response.status,
                                    text
                                );
                            });
                            throw new Error(
                                `HTTP error! status: ${response.status}`
                            );
                        }
                        return response.json();
                    })
                    .then((data) => {
                        console.log("Message associated successfully:", data);
                        messageInput.value = ""; // Clear message field
                        fileIdsInput.value = "[]"; // Clear hidden input
                        myDropzone.removeAllFiles(true); // Clear Dropzone queue

                        // Hide progress overlay
                        hideProgressOverlay();
                        // alert('Files uploaded and message associated successfully!');
                        window.dispatchEvent(
                            new CustomEvent("open-modal", {
                                detail: "association-success",
                            })
                        );
                    })
                    .catch((error) => {
                        console.error("Error associating message:", error);
                        // alert('Files uploaded, but failed to associate message. Please check console or try submitting message again later.');
                        window.dispatchEvent(
                            new CustomEvent("open-modal", {
                                detail: "association-error",
                            })
                        );
                    })
                    .finally(() => {
                        // Re-enable button regardless of association outcome, allowing retry if needed
                        submitButton.disabled = false;
                        submitButton.textContent = "Upload and Send Message";
                        setUploadInProgress(false);
                    });
            } else if (successfulFileIds.length > 0 && !message) {
                console.log(
                    "Batch upload complete without message. Successful IDs:",
                    successfulFileIds
                );

                // --- Call Backend to Trigger Batch Notifications ---
                console.log("Calling /api/uploads/batch-complete...");
                submitButton.textContent = "Finalizing Upload..."; // Update button text
                submitButton.disabled = true; // Keep disabled while finalizing

                // Update progress status
                const progressStatus =
                    document.getElementById("progress-status");
                if (progressStatus) {
                    progressStatus.textContent =
                        "Finalizing upload and sending notifications...";
                }

                // Use employee upload config if available, otherwise use client endpoint
                const batchCompleteUrl = window.employeeUploadConfig
                    ? window.employeeUploadConfig.batchCompleteUrl
                    : "/client/uploads/batch-complete";

                fetch(batchCompleteUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrfToken, // Ensure CSRF token is included
                    },
                    body: JSON.stringify({
                        file_upload_ids: successfulFileIds,
                    }),
                })
                    .then((response) => {
                        if (!response.ok) {
                            console.error(
                                "Error response from batch-complete endpoint:",
                                response.status
                            );
                            // Try to get text even for non-JSON error responses
                            response
                                .text()
                                .then((text) =>
                                    console.error(
                                        "Batch Complete Error Body:",
                                        text
                                    )
                                );
                            throw new Error(
                                `HTTP error! status: ${response.status}`
                            );
                        }
                        return response.json(); // Expecting a JSON success response
                    })
                    .then((data) => {
                        console.log(
                            "Backend acknowledged batch completion:",
                            data
                        );
                        // NOW, show success modal and clean up UI
                        console.log("Dispatching upload-success modal...");
                        window.dispatchEvent(
                            new CustomEvent("open-modal", {
                                detail: "upload-success",
                            })
                        );

                        // Clear the Dropzone UI and the hidden input field
                        console.log("Attempting to clear Dropzone UI...");
                        myDropzone.removeAllFiles(true);
                        console.log("Dropzone UI should be cleared now.");
                        console.log("Attempting to clear file IDs input...");
                        fileIdsInput.value = "[]";
                        console.log("File IDs input cleared.");

                        // Hide progress overlay
                        hideProgressOverlay();
                        setUploadInProgress(false);

                        // Redirect to My Uploads page after a short delay
                        setTimeout(() => {
                            window.location.href = "/client/my-uploads";
                        }, 2000); // 2 second delay to show success message
                    })
                    .catch((error) => {
                        console.error(
                            "Error calling batch-complete endpoint:",
                            error
                        );
                        // Show a specific error modal for batch finalization failure?
                        window.dispatchEvent(
                            new CustomEvent("open-modal", {
                                detail: "association-error",
                            })
                        ); // Reuse association error for now
                        // OR create a new modal like 'batch-complete-error'
                    })
                    .finally(() => {
                        // Re-enable button and reset text, regardless of API call outcome
                        submitButton.disabled = false;
                        submitButton.textContent = "Upload and Send Message";
                        setUploadInProgress(false);

                        // Check for rejected files AFTER attempting batch complete
                        if (myDropzone.getRejectedFiles().length > 0) {
                            console.log(
                                "Found rejected files, dispatching upload-error modal as well."
                            );
                            window.dispatchEvent(
                                new CustomEvent("open-modal", {
                                    detail: "upload-error",
                                })
                            );
                        }
                    });
                // --- End Backend Call ---
            } else {
                console.log(
                    "Queue finished, but no successful uploads or handling other cases."
                ); // <-- LOG: Other conditions
                // Re-enable button if there were no successful uploads to process
                if (successfulFileIds.length === 0) {
                    submitButton.disabled = false;
                    submitButton.textContent = "Upload and Send Message";
                    if (myDropzone.getRejectedFiles().length > 0) {
                        // alert('Some files failed to upload. Please check errors and try again.');
                        window.dispatchEvent(
                            new CustomEvent("open-modal", {
                                detail: "upload-error",
                            })
                        );
                    }
                }
            }
        });
    }
}

// --- External Links have icon ---
const domainName = window.location.hostname;

document
    .querySelectorAll(
        'a[href^="http"]:not([href*="' +
            domainName +
            '"]):not([href^="#"]):not(.button-link)'
    )
    .forEach((link) => {
        // Prevent adding multiple icons if run multiple times
        if (!link.querySelector(".external-link-icon")) {
            link.innerHTML +=
                '<svg class="external-link-icon" xmlns="http://www.w3.org/2000/svg" baseProfile="tiny" version="1.2" viewBox="0 0 79 79"><path d="M64,39.8v34.7c0,2.5-2,4.5-4.5,4.5H4.5c-2.5,0-4.5-2-4.5-4.5V19.5c0-2.5,2-4.5,4.5-4.5h35.6c2.5,0,4.5,2,4.5,4.5s-.5,2.4-1.4,3.3c-.8.8-1.9,1.3-3.1,1.3H9v45.9h45.9v-30.2c0-1.3.5-2.4,1.4-3.3.8-.8,1.9-1.3,3.1-1.3,2.5,0,4.5,2,4.5,4.5h0Z"/><path d="M74.5,0h-28.7c-2.2,0-4.2,1.5-4.6,3.6s1.6,5.5,4.4,5.5h17.9l-31.5,31.6c-1.8,1.8-1.8,4.7,0,6.5h0c1.7,1.8,4.6,1.8,6.3,0l31.6-31.6v17.7c0,2.2,1.5,4.2,3.6,4.6s5.5-1.6,5.5-4.4V4.7c0-2.5-2-4.5-4.5-4.5h0v-.2Z"/></svg>';
        }
    });
