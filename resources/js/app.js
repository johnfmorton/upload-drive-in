import './bootstrap';


// Import Uppy CSS
import '@uppy/core/dist/style.min.css';
import '@uppy/dashboard/dist/style.min.css';

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';

window.Alpine = Alpine;
Alpine.plugin(persist);

// Alpine.magic('clipboard', () => {
//   return (subject) => navigator.clipboard.writeText(subject);
// });

Alpine.start();

// --- Uppy Initialization ---
import Uppy from '@uppy/core';
import Dashboard from '@uppy/dashboard';
// Remove XhrUpload import
// import XhrUpload from '@uppy/xhr-upload';
// Import Tus plugin
import Tus from '@uppy/tus';

// Check if the Uppy dashboard element exists before initializing
const uppyDashboardElement = document.getElementById('uppy-dashboard');

if (uppyDashboardElement) {
    // Get the CSRF token and upload URL from meta tags or data attributes
    // We can no longer use Blade's {{ route(...) }} or {{ csrf_token() }} here
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    // We need to pass the upload URL from the Blade template to the JS
    // Let's assume we add a data attribute to the uppyDashboardElement in the Blade file:
    // <div id="uppy-dashboard" data-upload-url="{{ route('chunk.upload') }}"></div>
    const uploadUrl = uppyDashboardElement.dataset.uploadUrl;

    if (!uploadUrl) {
        console.error('Uppy dashboard element is missing the data-upload-url attribute!');
    } else {
        const uppy = new Uppy({
            debug: true,
            autoProceed: false,
            restrictions: {
                // Add any restrictions if needed
            }
        })
        .use(Dashboard, {
            inline: true,
            target: '#uppy-dashboard', // Target the container div (or uppyDashboardElement)
            proudlyDisplayPoweredByUppy: true,
            height: 470,
            showProgressDetails: true,
            // Updated note for clarity, though functionally chunking depends on Tus now
            note: 'Upload files here. Large files will be uploaded in chunks.',
            browserBackButtonClose: true
        })
        // Replace XhrUpload with Tus
        .use(Tus, {
            endpoint: uploadUrl, // Use the same endpoint, pion backend supports Tus
            retryDelays: [0, 1000, 3000, 5000], // Optional: configure retries
            chunkSize: 5 * 1024 * 1024, // Suggest a 5MB chunk size (Tus may negotiate)
            limit: 10, // Keep concurrent upload limit
            headers: {
                'X-CSRF-TOKEN': csrfToken // Send CSRF token in headers
            },
            // Tus automatically handles metadata like filename, type
            // No need for formData or fieldName
        });

        uppy.on('upload-success', (file, response) => {
            // Tus response might be different from XHR, check pion docs or log response
            // For pion/laravel-chunk-upload with Tus, the final response url might be needed
            // or it might return the same JSON if pion handles the final assembly response.
            // Let's assume for now the backend controller still returns the JSON on completion.
            console.log('File uploaded successfully:', file.name);
            // Attempt to parse the uploadURL which might contain the final file info if backend uses Tus protocol correctly
            console.log('Upload URL from response:', response.uploadURL);
            // If the backend controller's `saveFile` method is still hit on completion and returns JSON:
            // We need to access the response potentially differently if it's not in `response.body` anymore
            // For now, let's log the whole response to see what we get
            console.log('Tus success response:', response);

            // Modification for associateMessage: Access file ID from Tus upload URL or metadata
            // This part might need adjustment based on how pion returns the final file ID with Tus
            // Let's keep the existing logic but add a check for response.body
            if (response.body && response.body.file_upload_id) {
                 file.meta.file_upload_id = response.body.file_upload_id;
            } else {
                // Attempt to extract from uploadURL or log a warning
                console.warn('Could not find file_upload_id directly in Tus response body. Check console logs for details.');
                // You might need to make another request to the server using response.uploadURL to get the final ID
            }
        });

        uppy.on('upload-error', (file, error, response) => {
            console.error('Error uploading file:', file.name, error, response);
        });

        uppy.on('complete', result => {
            console.log('All uploads complete!', result);
            const messageInput = document.getElementById('message');
            if (messageInput) {
                const message = messageInput.value;
                console.log('Message entered:', message);

                // Only proceed if there's a message and at least one successful upload
                if (message && result.successful.length > 0) {
                    const successfulFileIds = result.successful.map(file => {
                        // Try accessing the ID potentially stored in meta during upload-success
                        return file.meta.file_upload_id || (file.response && file.response.body && file.response.body.file_upload_id);
                    }).filter(id => id); // Filter out any undefined IDs

                    if (successfulFileIds.length > 0) {
                        console.log('Sending message for file IDs:', successfulFileIds);
                        fetch('/api/uploads/associate-message', { // Define the API endpoint
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken // Reuse the CSRF token
                            },
                            body: JSON.stringify({
                                message: message,
                                file_upload_ids: successfulFileIds
                            })
                        })
                        .then(response => {
                            if (!response.ok) {
                                // Log detailed error if response is not OK
                                response.text().then(text => {
                                    console.error('Error response from associate-message:', response.status, text);
                                });
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                         })
                        .then(data => {
                            console.log('Message associated successfully:', data);
                            // Optionally clear the message field or give user feedback
                            messageInput.value = ''; // Clear message field on success
                        })
                        .catch(error => {
                            console.error('Error associating message:', error);
                            // Optionally inform the user that the message could not be saved
                        });
                    } else {
                        console.log('No successful file IDs found in Uppy results to associate message with.');
                    }
                } else {
                    console.log('No message entered or no successful uploads, skipping message association.');
                }
            }
        });
    }
}
// --- End Uppy Initialization ---
