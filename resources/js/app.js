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
import XhrUpload from '@uppy/xhr-upload';

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
            note: 'Upload files here. Chunking is enabled for large files.',
            browserBackButtonClose: true
        })
        .use(XhrUpload, {
            endpoint: uploadUrl, // Use the URL passed from the Blade template
            formData: true,
            fieldName: 'file',
            chunkSize: 5 * 1024 * 1024, // 5MB chunks
            limit: 10,
            headers: {
                'X-CSRF-TOKEN': csrfToken // Use the token fetched from the meta tag
            }
        });

        uppy.on('upload-success', (file, response) => {
            console.log('File uploaded successfully:', file.name, response.body);
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
                        // Assuming the backend response JSON for a successful upload
                        // looks like { ..., "file_upload_id": 123, ... }
                        return file.response.body.file_upload_id;
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
