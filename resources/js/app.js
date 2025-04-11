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
                // TODO: Implement sending the message after completion
                // You might make a fetch request here to a new endpoint
                // passing the message and perhaps identifiers for the uploaded files (from result.successful)
                if (message && result.successful.length > 0) {
                    // Example: Send message to backend
                    // fetch('/api/associate-message', {
                    //     method: 'POST',
                    //     headers: {
                    //         'Content-Type': 'application/json',
                    //         'X-CSRF-TOKEN': csrfToken
                    //     },
                    //     body: JSON.stringify({
                    //         message: message,
                    //         files: result.successful.map(f => f.response.body.name) // Assuming backend returns filename
                    //     })
                    // })
                    // .then(response => response.json())
                    // .then(data => console.log('Message associated:', data))
                    // .catch(error => console.error('Error associating message:', error));
                    console.log('TODO: Send message to backend:', message, result.successful);
                }
            }
        });
    }
}
// --- End Uppy Initialization ---
