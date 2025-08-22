<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup Instructions - {{ config('app.name', 'Laravel') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-4xl">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Setup Instructions</h1>
                <p class="mt-2 text-lg text-gray-600">Complete these steps to configure your application</p>
            </div>

            <!-- Instructions Card -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="px-6 py-8 sm:px-10">
                    
                    <!-- Step 1: Database Configuration -->
                    <div class="mb-10">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">
                                1
                            </div>
                            <h2 class="ml-3 text-xl font-semibold text-gray-900">Database Configuration</h2>
                        </div>
                        
                        <p class="text-gray-600 mb-4">
                            Add these variables to your <code class="bg-gray-100 px-2 py-1 rounded text-sm">.env</code> file:
                        </p>
                        
                        <div class="bg-gray-900 rounded-lg p-4 relative">
                            <button 
                                onclick="copyToClipboard('database-config')" 
                                class="absolute top-2 right-2 bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm transition-colors"
                            >
                                Copy
                            </button>
                            <pre id="database-config" class="text-green-400 text-sm overflow-x-auto"><code># Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_username
DB_PASSWORD=your_database_password</code></pre>
                        </div>
                        
                        <div class="mt-4 p-4 bg-blue-50 border-l-4 border-blue-400">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        <strong>Note:</strong> Replace the placeholder values with your actual database credentials.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Google Drive Configuration -->
                    <div class="mb-10">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">
                                2
                            </div>
                            <h2 class="ml-3 text-xl font-semibold text-gray-900">Google Drive Configuration</h2>
                        </div>
                        
                        <p class="text-gray-600 mb-4">
                            Add these Google Drive API credentials to your <code class="bg-gray-100 px-2 py-1 rounded text-sm">.env</code> file:
                        </p>
                        
                        <div class="bg-gray-900 rounded-lg p-4 relative">
                            <button 
                                onclick="copyToClipboard('google-drive-config')" 
                                class="absolute top-2 right-2 bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm transition-colors"
                            >
                                Copy
                            </button>
                            <pre id="google-drive-config" class="text-green-400 text-sm overflow-x-auto"><code># Google Drive Configuration
GOOGLE_DRIVE_CLIENT_ID=your_google_client_id
GOOGLE_DRIVE_CLIENT_SECRET=your_google_client_secret
GOOGLE_DRIVE_REDIRECT_URI=https://your-domain.com/admin/cloud-storage/google-drive/callback
CLOUD_STORAGE_DEFAULT=google-drive</code></pre>
                        </div>
                        
                        <div class="mt-4 p-4 bg-amber-50 border-l-4 border-amber-400">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-amber-700">
                                        <strong>Important:</strong> You need to create a Google Cloud Project and enable the Google Drive API to get these credentials. 
                                        Replace <code>your-domain.com</code> with your actual domain.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Create Admin User -->
                    <div class="mb-10">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">
                                3
                            </div>
                            <h2 class="ml-3 text-xl font-semibold text-gray-900">Create Admin User</h2>
                        </div>
                        
                        <p class="text-gray-600 mb-4">
                            Run this command on your server to create the initial admin user:
                        </p>
                        
                        <div class="bg-gray-900 rounded-lg p-4 relative">
                            <button 
                                onclick="copyToClipboard('admin-command')" 
                                class="absolute top-2 right-2 bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm transition-colors"
                            >
                                Copy
                            </button>
                            <pre id="admin-command" class="text-green-400 text-sm overflow-x-auto"><code>php artisan user:create --name="Admin User" --email="admin@example.com" --password="secure-password" --role=admin</code></pre>
                        </div>
                        
                        <div class="mt-4 p-4 bg-red-50 border-l-4 border-red-400">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700">
                                        <strong>Security:</strong> Replace the example email and password with your actual admin credentials. 
                                        Use a strong, unique password.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Run Migrations -->
                    <div class="mb-10">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">
                                4
                            </div>
                            <h2 class="ml-3 text-xl font-semibold text-gray-900">Run Database Migrations</h2>
                        </div>
                        
                        <p class="text-gray-600 mb-4">
                            Run this command to set up the database tables:
                        </p>
                        
                        <div class="bg-gray-900 rounded-lg p-4 relative">
                            <button 
                                onclick="copyToClipboard('migration-command')" 
                                class="absolute top-2 right-2 bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm transition-colors"
                            >
                                Copy
                            </button>
                            <pre id="migration-command" class="text-green-400 text-sm overflow-x-auto"><code>php artisan migrate</code></pre>
                        </div>
                    </div>

                    <!-- Completion Notice -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-medium text-green-800">
                                    Setup Complete!
                                </h3>
                                <div class="mt-2 text-sm text-green-700">
                                    <p>
                                        Once you've completed all the steps above, refresh this page to access the application. 
                                        The system will automatically detect your configuration and redirect you to the login page.
                                    </p>
                                </div>
                                <div class="mt-4">
                                    <button 
                                        onclick="window.location.reload()" 
                                        class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition-colors"
                                    >
                                        Refresh Page
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            navigator.clipboard.writeText(text).then(function() {
                // Show success feedback
                const button = element.parentElement.querySelector('button');
                const originalText = button.textContent;
                button.textContent = 'Copied!';
                button.classList.add('bg-green-600');
                button.classList.remove('bg-gray-700');
                
                setTimeout(function() {
                    button.textContent = originalText;
                    button.classList.remove('bg-green-600');
                    button.classList.add('bg-gray-700');
                }, 2000);
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
            });
        }
    </script>
</body>
</html>