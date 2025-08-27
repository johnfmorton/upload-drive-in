<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Setup Instructions - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/setup-status.js'])

    <!-- Status Indicator Styles -->
    <style>
        .status-indicator {
            @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
        }

        .status-completed {
            @apply bg-green-100 text-green-800;
        }

        .status-incomplete {
            @apply bg-red-100 text-red-800;
        }

        .status-error {
            @apply bg-red-100 text-red-800;
        }

        .status-checking {
            @apply bg-blue-100 text-blue-800;
        }

        .status-cannot-verify {
            @apply bg-gray-100 text-gray-800;
        }

        .status-needs_attention {
            @apply bg-yellow-100 text-yellow-800;
        }

        .status-icon {
            @apply w-4 h-4 mr-1.5;
        }

        .refresh-button {
            @apply bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed;
        }

        .refresh-button:disabled {
            @apply bg-gray-400;
        }

        .loading-spinner {
            @apply animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full;
        }

        .step-status-container {
            @apply flex items-center justify-between mb-4;
        }

        .step-header {
            @apply flex items-center;
        }

        .step-status-details {
            @apply mt-2 text-sm text-gray-600 hidden;
        }

        .step-status-details.show {
            @apply block;
        }

        /* Toast Container Styles */
        .toast-container {
            @apply fixed top-4 right-4 z-50 space-y-2;
            max-width: 400px;
        }

        @media (max-width: 640px) {
            .step-status-container {
                @apply flex-col items-start space-y-2;
            }

            .status-indicator {
                @apply text-xs px-2 py-1;
            }

            .toast-container {
                @apply left-4 right-4 top-4;
                max-width: none;
            }
        }
    </style>
</head>

<body class="font-sans antialiased bg-gray-50">
    <!-- Toast Container -->
    <div id="toast-container" class="toast-container"></div>
    <div class="min-h-screen py-12 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="sm:mx-auto sm:w-full sm:max-w-4xl">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Upload Drive-in Setup Instructions</h1>
                <p class="mt-2 text-lg text-gray-600">Complete these steps to configure your application</p>
            </div>
        </div>

        <!-- Status Refresh Section - Sticky -->
        <div id="status-refresh-section" class="sticky top-0 z-40 bg-gray-50 border-b border-gray-200 py-4 mb-8">
            <div class="sm:mx-auto sm:w-full sm:max-w-4xl px-6 sm:px-8">
                <div class="flex flex-col sm:flex-row items-center justify-center space-y-3 sm:space-y-0 sm:space-x-4">
                    <button id="refresh-status-btn"
                        class="inline-flex items-center px-3 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                        <span id="refresh-btn-text">Check Status</span>
                        <div id="refresh-spinner" class="loading-spinner hidden ml-2"></div>
                    </button>

                    <div id="last-checked" class="text-sm text-gray-500 hidden">
                        Last checked: <span id="last-checked-time">Never</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="sm:mx-auto sm:w-full sm:max-w-4xl">

            <!-- Instructions Card -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="px-6 py-8 sm:px-10">

                    <!-- Step 1: Database Configuration -->
                    <div class="mb-10" data-step="database">
                        <div class="step-status-container">
                            <div class="step-header">
                                <div
                                    class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">
                                    1
                                </div>
                                <h2 class="ml-3 text-xl font-semibold text-gray-900">Database Configuration</h2>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="status-indicator status-checking" id="status-database">
                                    <span class="status-emoji w-4 h-4 mr-1.5 text-base">🔄</span>
                                    <span id="status-database-text">Checking...</span>
                                </div>
                                <button class="text-gray-400 hover:text-gray-600"
                                    onclick="toggleStatusDetails('database')" title="Show details">
                                    <span class="w-4 h-4 text-base">ℹ️</span>
                                </button>
                            </div>
                        </div>
                        <div class="step-status-details" id="details-database">
                            <div class="p-3 bg-gray-50 rounded-md">
                                <p class="text-sm text-gray-600" id="details-database-text">Click "Check Status" to
                                    verify database configuration.</p>
                            </div>
                        </div>

                        <p class="text-gray-600 mb-4">
                            Add these variables to your <code class="bg-gray-100 px-2 py-1 rounded text-sm">.env</code>
                            file:
                        </p>

                        <div class="bg-gray-900 rounded-lg p-4 relative">
                            <button onclick="copyToClipboard('database-config')"
                                class="absolute top-2 right-2 bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm transition-colors">
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
                                    <span class="h-5 w-5 text-lg">ℹ️</span>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        <strong>Note:</strong> Replace the placeholder values with your actual database
                                        credentials.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Mail Configuration -->
                    <div class="mb-10" data-step="mail">
                        <div class="step-status-container">
                            <div class="step-header">
                                <div
                                    class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">
                                    2
                                </div>
                                <h2 class="ml-3 text-xl font-semibold text-gray-900">Mail Configuration</h2>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="status-indicator status-checking" id="status-mail">
                                    <span class="status-emoji w-4 h-4 mr-1.5 text-base">🔄</span>
                                    <span id="status-mail-text">Checking...</span>
                                </div>
                                <button class="text-gray-400 hover:text-gray-600" onclick="toggleStatusDetails('mail')"
                                    title="Show details">
                                    <span class="w-4 h-4 text-base">ℹ️</span>
                                </button>
                            </div>
                        </div>
                        <div class="step-status-details" id="details-mail">
                            <div class="p-3 bg-gray-50 rounded-md">
                                <p class="text-sm text-gray-600" id="details-mail-text">Click "Check Status" to verify
                                    mail configuration.</p>
                            </div>
                        </div>

                        <p class="text-gray-600 mb-4">
                            Add these mail server settings to your <code
                                class="bg-gray-100 px-2 py-1 rounded text-sm">.env</code>
                            file. The application requires email functionality to work properly:
                        </p>

                        <div class="bg-gray-900 rounded-lg p-4 relative">
                            <button onclick="copyToClipboard('mail-config')"
                                class="absolute top-2 right-2 bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm transition-colors">
                                Copy
                            </button>
                            <pre id="mail-config" class="text-green-400 text-sm overflow-x-auto"><code># Mail Configuration
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.example.com
MAIL_PORT=465
MAIL_USERNAME=username
MAIL_PASSWORD=smtppassword
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=name@example.com</code></pre>
                        </div>

                        <div class="mt-4 p-4 bg-red-50 border-l-4 border-red-400">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <span class="h-5 w-5 text-lg">🚨</span>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700">
                                        <strong>Required:</strong> Replace all placeholder values with your actual SMTP
                                        server credentials.
                                        The application cannot function without proper mail configuration as it's used
                                        for upload notifications and user verification.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 p-4 bg-blue-50 border-l-4 border-blue-400">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        <strong>Local Development:</strong> For local development environments using
                                        tools like Mailpit or MailHog
                                        (host: 127.0.0.1 or localhost, port: 1025), username and password can be set to
                                        null.
                                        Production environments require valid SMTP credentials.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Common Mail Provider Examples -->
                        <div class="mt-6">
                            <details class="bg-gray-50 border border-gray-200 rounded-lg">
                                <summary
                                    class="cursor-pointer p-4 font-medium text-gray-900 hover:bg-gray-100 transition-colors">
                                    📧 Common Mail Provider Settings
                                </summary>
                                <div class="p-4 pt-0 border-t border-gray-200">
                                    <div class="space-y-4 text-sm pt-3">
                                        <div>
                                            <h4 class="font-semibold text-gray-900 mb-2">Gmail</h4>
                                            <div class="bg-gray-100 p-3 rounded font-mono text-xs">
                                                MAIL_HOST=smtp.gmail.com<br>
                                                MAIL_PORT=587<br>
                                                MAIL_ENCRYPTION=tls
                                            </div>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-900 mb-2">Outlook/Hotmail</h4>
                                            <div class="bg-gray-100 p-3 rounded font-mono text-xs">
                                                MAIL_HOST=smtp-mail.outlook.com<br>
                                                MAIL_PORT=587<br>
                                                MAIL_ENCRYPTION=tls
                                            </div>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-900 mb-2">Yahoo</h4>
                                            <div class="bg-gray-100 p-3 rounded font-mono text-xs">
                                                MAIL_HOST=smtp.mail.yahoo.com<br>
                                                MAIL_PORT=587<br>
                                                MAIL_ENCRYPTION=tls
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>

                    <!-- Step 3: Google Drive Configuration -->
                    <div class="mb-10" data-step="google_drive">
                        <div class="step-status-container">
                            <div class="step-header">
                                <div
                                    class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">
                                    3
                                </div>
                                <h2 class="ml-3 text-xl font-semibold text-gray-900">Google Drive Configuration</h2>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="status-indicator status-checking" id="status-google_drive">
                                    <span class="status-emoji w-4 h-4 mr-1.5 text-base">🔄</span>
                                    <span id="status-google_drive-text">Checking...</span>
                                </div>
                                <button class="text-gray-400 hover:text-gray-600"
                                    onclick="toggleStatusDetails('google_drive')" title="Show details">
                                    <span class="w-4 h-4 text-base">ℹ️</span>
                                </button>
                            </div>
                        </div>
                        <div class="step-status-details" id="details-google_drive">
                            <div class="p-3 bg-gray-50 rounded-md">
                                <p class="text-sm text-gray-600" id="details-google_drive-text">Click "Check Status"
                                    to verify Google Drive configuration.</p>
                            </div>
                        </div>

                        <p class="text-gray-600 mb-4">
                            Add these Google Drive API credentials to your <code
                                class="bg-gray-100 px-2 py-1 rounded text-sm">.env</code> file:
                        </p>

                        <div class="bg-gray-900 rounded-lg p-4 relative">
                            <button onclick="copyToClipboard('google-drive-config')"
                                class="absolute top-2 right-2 bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm transition-colors">
                                Copy
                            </button>
                            <pre id="google-drive-config" class="text-green-400 text-sm overflow-x-auto"><code># Google Drive Configuration
GOOGLE_DRIVE_CLIENT_ID=your_google_client_id
GOOGLE_DRIVE_CLIENT_SECRET=your_google_client_secret
CLOUD_STORAGE_DEFAULT=google-drive</code></pre>
                        </div>

                        <!-- Google Console Redirect URI -->
                        <div class="mt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Google Console Configuration</h3>
                            <p class="text-gray-600 mb-4">
                                When <a href="https://console.cloud.google.com/apis/credentials" target="_blank"
                                    class="underline text-blue-800" rel="noopener noreferrer">setting up your Google
                                    Cloud Project OAuth 2.0 client</a>, add the following <strong>Authorized JavaScript
                                    origin</strong> and <strong>Authorized
                                    redirect URI</strong> to your app:
                            </p>

                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 relative">
                                <button onclick="copyToClipboard('javascript-origin')"
                                    class="absolute top-2 right-2 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition-colors">
                                    Copy
                                </button>
                                <div class="pr-16">
                                    <label class="block text-sm font-medium text-blue-800 mb-2">Authorized JavaScript
                                        origin:</label>
                                    <code id="javascript-origin"
                                        class="block bg-white border border-blue-300 rounded px-3 py-2 text-blue-900 font-mono text-sm break-all">{{ config('app.url') }}</code>
                                </div>
                            </div>

                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-8  relative">
                                <button onclick="copyToClipboard('redirect-uri')"
                                    class="absolute top-2 right-2 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition-colors">
                                    Copy
                                </button>
                                <div class="pr-16">
                                    <label class="block text-sm font-medium text-blue-800 mb-2">Authorized redirect
                                        URI:</label>
                                    <code id="redirect-uri"
                                        class="block bg-white border border-blue-300 rounded px-3 py-2 text-blue-900 font-mono text-sm break-all">{{ config('app.url') }}/google-drive/callback</code>
                                </div>
                            </div>
                        </div>

                        <!-- Google Cloud Console Setup Guide -->
                        <div class="mt-6">
                            <details class="bg-gray-50 border border-gray-200 rounded-lg">
                                <summary
                                    class="cursor-pointer p-4 font-medium text-gray-900 hover:bg-gray-100 transition-colors">
                                    📋 Step-by-step Google Cloud Console Setup Guide
                                </summary>
                                <div class="p-4 pt-0 border-t border-gray-200">
                                    <ol class="list-decimal list-inside space-y-3 text-sm text-gray-700 pt-3">
                                        <li>Go to the <a href="https://console.cloud.google.com/" target="_blank"
                                                class="text-blue-600 hover:text-blue-800 underline">Google Cloud
                                                Console</a></li>
                                        <li>Create a new project or select an existing one</li>
                                        <li>Navigate to <strong>APIs & Services</strong> → <strong>Library</strong></li>
                                        <li>Search for "Google Drive API" and enable it</li>
                                        <li>Go to <strong>APIs & Services</strong> → <strong>Credentials</strong></li>
                                        <li>Click <strong>Create Credentials</strong> → <strong>OAuth 2.0 Client
                                                IDs</strong></li>
                                        <li>Choose <strong>Web application</strong> as the application type</li>
                                        <li>Add the authorized JavaScript origin shown above to <strong>Authorized
                                                JavaScript origins
                                            </strong></li>
                                        <li>Add the redirect URI shown above to <strong>Authorized redirect
                                                URIs</strong></li>
                                        <li>Copy the <strong>Client ID</strong> and <strong>Client Secret</strong> to
                                            your .env file</li>
                                    </ol>
                                </div>
                            </details>
                        </div>

                        <div class="mt-4 p-4 bg-amber-50 border-l-4 border-amber-400">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-amber-700">
                                        <strong>Important:</strong> The redirect URI above is automatically generated
                                        based on your application URL.
                                        Make sure to use the exact URL shown when configuring your Google Cloud Console
                                        OAuth client.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Setup Queue Worker -->
                    <div class="mb-10" data-step="queue_worker">
                        <div class="step-status-container">
                            <div class="step-header">
                                <div
                                    class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">
                                    4
                                </div>
                                <h2 class="ml-3 text-xl font-semibold text-gray-900">Setup Queue Worker</h2>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="status-indicator status-checking" id="status-queue_worker">
                                    <span class="status-emoji w-4 h-4 mr-1.5 text-base">🔄</span>
                                    <span id="status-queue_worker-text">Checking...</span>
                                </div>
                                <button class="text-gray-400 hover:text-gray-600"
                                    onclick="toggleStatusDetails('queue_worker')" title="Show details">
                                    <span class="w-4 h-4 text-base">ℹ️</span>
                                </button>
                            </div>
                        </div>
                        <div class="step-status-details" id="details-queue_worker">
                            <div class="p-3 bg-gray-50 rounded-md">
                                <p class="text-sm text-gray-600" id="details-queue_worker-text">Click "Check Status"
                                    to verify queue worker status.</p>
                            </div>
                        </div>

                        <p class="text-gray-600 mb-4">
                            The application uses background jobs for file uploads to Google Drive. You need to set up a
                            queue worker to process these jobs.
                        </p>

                        <!-- Queue Worker Test Section -->
                        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-md font-medium text-blue-900">Test Queue Worker</h3>
                                <button id="test-queue-worker-btn"
                                    class="inline-flex items-center px-3 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    <span id="test-queue-worker-btn-text">Test Queue Worker</span>
                                </button>
                            </div>
                            <p class="text-sm text-blue-700 mb-3">
                                Click the button above to test if your queue worker is running and processing jobs
                                correctly.
                            </p>

                            <!-- Test Results -->
                            <div id="queue-test-results" class="hidden">
                                <div class="mt-3 p-3 bg-white border border-blue-200 rounded">
                                    <div id="queue-test-status" class="text-sm"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4 p-4 bg-blue-50 border-l-4 border-blue-400">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        <strong>Path Configuration:</strong> The examples below use common default
                                        paths.
                                        Adjust the paths (<code>/var/www/html</code>, <code>/home/forge/default</code>)
                                        to match your actual application directory.
                                        For Laravel Forge, depending on how your server is configured, you may use the
                                        specific PHP version (e.g., <code>php8.3</code>,
                                        <code>php8.4</code>) instead of just <code>php</code>.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-900 rounded-lg p-4 relative">
                            <button onclick="copyToClipboard('worker-command')"
                                class="absolute top-2 right-2 bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm transition-colors">
                                Copy
                            </button>
                            <pre id="worker-command" class="text-green-400 text-sm overflow-x-auto"><code>php artisan queue:work</code></pre>
                        </div>

                        <div class="mt-4 p-4 bg-amber-50 border-l-4 border-amber-400">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-amber-700">
                                        <strong>Production Setup:</strong> For production environments, you should set
                                        up the queue worker as a daemon using a process manager like Supervisor,
                                        systemd, or your hosting provider's process management tools. The worker should
                                        restart automatically if it stops.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Production Setup Examples -->
                        <div class="mt-6">
                            <details class="bg-gray-50 border border-gray-200 rounded-lg">
                                <summary
                                    class="cursor-pointer p-4 font-medium text-gray-900 hover:bg-gray-100 transition-colors">
                                    🔧 Production Queue Worker Setup Examples
                                </summary>
                                <div class="p-4 pt-0 border-t border-gray-200 pt-3">
                                    <div class="space-y-6 text-sm">
                                        <!-- Supervisor Example -->
                                        <div>
                                            <h4 class="font-semibold text-gray-900 mb-2">Supervisor Configuration</h4>
                                            <p class="text-gray-600 mb-2">Create a file like
                                                <code>/etc/supervisor/conf.d/laravel-worker.conf</code>:
                                            </p>
                                            <div class="bg-gray-100 p-3 rounded font-mono text-xs overflow-x-auto">
                                                [program:laravel-worker]<br>
                                                process_name=%(program_name)s_%(process_num)02d<br>
                                                command=php {{ base_path() }}/artisan queue:work --sleep=3 --tries=3
                                                --max-time=3600<br>
                                                autostart=true<br>
                                                autorestart=true<br>
                                                stopasgroup=true<br>
                                                killasgroup=true<br>
                                                user=www-data<br>
                                                numprocs=1<br>
                                                redirect_stderr=true<br>
                                                stdout_logfile={{ base_path() }}/storage/logs/worker.log<br>
                                                stopwaitsecs=3600
                                            </div>
                                            <p class="text-gray-600 mt-2 text-xs">Then run: <code>sudo supervisorctl
                                                    reread && sudo supervisorctl update && sudo supervisorctl start
                                                    laravel-worker:*</code></p>
                                        </div>

                                        <!-- Systemd Example -->
                                        <div>
                                            <h4 class="font-semibold text-gray-900 mb-2">Systemd Service</h4>
                                            <p class="text-gray-600 mb-2">Create a file like
                                                <code>/etc/systemd/system/laravel-worker.service</code>:
                                            </p>
                                            <div class="bg-gray-100 p-3 rounded font-mono text-xs overflow-x-auto">
                                                [Unit]<br>
                                                Description=Laravel Queue Worker<br>
                                                After=network.target<br>
                                                <br>
                                                [Service]<br>
                                                User=www-data<br>
                                                Group=www-data<br>
                                                Restart=always<br>
                                                ExecStart=php {{ base_path() }}/artisan queue:work --sleep=3 --tries=3
                                                --max-time=3600<br>
                                                WorkingDirectory={{ base_path() }}<br>
                                                <br>
                                                [Install]<br>
                                                WantedBy=multi-user.target
                                            </div>
                                            <p class="text-gray-600 mt-2 text-xs">Then run: <code>sudo systemctl enable
                                                    laravel-worker && sudo systemctl start laravel-worker</code></p>
                                        </div>

                                        <!-- Laravel Forge Example -->
                                        <div>
                                            <h4 class="font-semibold text-gray-900 mb-2">Laravel Forge</h4>
                                            <p class="text-gray-600 mb-2">In your Forge dashboard, go to your server →
                                                Daemons and add:</p>
                                            <div class="bg-gray-100 p-3 rounded font-mono text-xs">
                                                <strong>Command:</strong> php artisan queue:work --sleep=3 --tries=3
                                                --max-time=3600<br>
                                                <strong>User:</strong> forge<br>
                                                <strong>Directory:</strong> {{ base_path() }}
                                            </div>
                                            <div class="mt-3 p-2 bg-amber-50 border border-amber-200 rounded text-xs">
                                                <strong>Note:</strong> Depending on your server's set up, you may
                                                replace <code>php</code> with your server's
                                                PHP version (e.g., <code>php8.4</code>, <code>php8.2</code>). Also,
                                                replace <code>{{ base_path() }}</code> with
                                                the directory to your site.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </details>
                        </div>

                        <!-- Daemon vs Site Queue Worker Explanation -->
                        <div class="mt-6">
                            <details class="bg-amber-50 border border-amber-200 rounded-lg">
                                <summary
                                    class="cursor-pointer p-4 font-medium text-gray-900 hover:bg-amber-100 transition-colors">
                                    ⚡ Why Use Daemons Instead of Site Queue Workers?
                                </summary>
                                <div class="p-4 pt-0 border-t border-amber-200">
                                    <div class="space-y-4 text-sm pt-3">
                                        <p class="text-amber-800">
                                            <strong>Laravel Forge offers two ways to run queue workers:</strong>
                                            Server-level Daemons (recommended above)
                                            and site-specific Queue Workers. Here's why we recommend Daemons:
                                        </p>

                                        <div class="grid md:grid-cols-2 gap-4">
                                            <div class="bg-green-50 border border-green-200 rounded p-3">
                                                <h5 class="font-semibold text-green-800 mb-2">✅ Daemons (Recommended)
                                                </h5>
                                                <ul class="text-green-700 text-xs space-y-1">
                                                    <li>• <strong>Greater Control:</strong> Full command customization
                                                    </li>
                                                    <li>• <strong>Deployment Independence:</strong> Runs during
                                                        deployments</li>
                                                    <li>• <strong>Laravel Best Practice:</strong> Uses Supervisor
                                                        directly</li>
                                                    <li>• <strong>Server-level Management:</strong> Centralized process
                                                        control</li>
                                                    <li>• <strong>Production Ready:</strong> More reliable for critical
                                                        applications</li>
                                                </ul>
                                            </div>

                                            <div class="bg-blue-50 border border-blue-200 rounded p-3">
                                                <h5 class="font-semibold text-blue-800 mb-2">ℹ️ Site Queue Workers</h5>
                                                <ul class="text-blue-700 text-xs space-y-1">
                                                    <li>• <strong>Simplified Setup:</strong> GUI-based configuration
                                                    </li>
                                                    <li>• <strong>Laravel-focused:</strong> Pre-configured for Laravel
                                                    </li>
                                                    <li>• <strong>Site-specific:</strong> Tied to individual sites</li>
                                                    <li>• <strong>Limited Options:</strong> Fewer customization options
                                                    </li>
                                                    <li>• <strong>Deployment Tied:</strong> May restart during
                                                        deployments</li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="bg-amber-100 border border-amber-300 rounded p-3">
                                            <p class="text-amber-800 text-xs">
                                                <strong>Bottom Line:</strong> For production file upload applications,
                                                Daemons provide the reliability,
                                                control, and deployment independence needed for critical background
                                                processing. Site Queue Workers
                                                are better suited for simpler applications or development environments.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </details>
                        </div>

                        <div class="mt-4 p-4 bg-blue-50 border-l-4 border-blue-400">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        <strong>Testing:</strong> For testing purposes, you can run the worker manually
                                        with the command above. However, for production use, always set up a proper
                                        daemon process that will restart automatically if it fails.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Run Migrations -->
                    <div class="mb-10" data-step="migrations">
                        <div class="step-status-container">
                            <div class="step-header">
                                <div
                                    class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">
                                    5
                                </div>
                                <h2 class="ml-3 text-xl font-semibold text-gray-900">Run Database Migrations</h2>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="status-indicator status-checking" id="status-migrations">
                                    <span class="status-emoji w-4 h-4 mr-1.5 text-base">🔄</span>
                                    <span id="status-migrations-text">Checking...</span>
                                </div>
                                <button class="text-gray-400 hover:text-gray-600"
                                    onclick="toggleStatusDetails('migrations')" title="Show details">
                                    <span class="w-4 h-4 text-base">ℹ️</span>
                                </button>
                            </div>
                        </div>
                        <div class="step-status-details" id="details-migrations">
                            <div class="p-3 bg-gray-50 rounded-md">
                                <p class="text-sm text-gray-600" id="details-migrations-text">Click "Check Status" to
                                    verify database migrations.</p>
                            </div>
                        </div>

                        <p class="text-gray-600 mb-4">
                            Run this command to set up the database tables:
                        </p>

                        <div class="bg-gray-900 rounded-lg p-4 relative">
                            <button onclick="copyToClipboard('migration-command')"
                                class="absolute top-2 right-2 bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm transition-colors">
                                Copy
                            </button>
                            <pre id="migration-command" class="text-green-400 text-sm overflow-x-auto"><code>php artisan migrate</code></pre>
                        </div>
                    </div>

                    <!-- Step 6: Create Admin User -->
                    <div class="mb-10" data-step="admin_user">
                        <div class="step-status-container">
                            <div class="step-header">
                                <div
                                    class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">
                                    6
                                </div>
                                <h2 class="ml-3 text-xl font-semibold text-gray-900">Create Admin User</h2>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="status-indicator status-checking" id="status-admin_user">
                                    <span class="status-emoji w-4 h-4 mr-1.5 text-base">🔄</span>
                                    <span id="status-admin_user-text">Checking...</span>
                                </div>
                                <button class="text-gray-400 hover:text-gray-600"
                                    onclick="toggleStatusDetails('admin_user')" title="Show details">
                                    <span class="w-4 h-4 text-base">ℹ️</span>
                                </button>
                            </div>
                        </div>
                        <div class="step-status-details" id="details-admin_user">
                            <div class="p-3 bg-gray-50 rounded-md">
                                <p class="text-sm text-gray-600" id="details-admin_user-text">Click "Check Status" to
                                    verify admin user exists.</p>
                            </div>
                        </div>

                        <p class="text-gray-600 mb-4">
                            Run this command on your server to create the initial admin user:
                        </p>

                        <div class="bg-gray-900 rounded-lg p-4 relative">
                            <button onclick="copyToClipboard('admin-command')"
                                class="absolute top-2 right-2 bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm transition-colors">
                                Copy
                            </button>
                            <pre id="admin-command" class="text-green-400 text-sm overflow-x-auto"><code>php artisan user:create --name="Admin User" --email="admin@example.com" --role=admin --password="secure-password"</code></pre>
                        </div>

                        <div class="mt-4 p-4 bg-red-50 border-l-4 border-red-400">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.30 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700">
                                        <strong>Security:</strong> Replace the example email and password with your
                                        actual admin credentials.
                                        Use a strong, unique password.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- Completion Notice -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-medium text-green-800">
                                    Setup Complete!
                                </h3>
                                <div class="mt-2 text-sm text-green-700">
                                    <p>
                                        Once you've completed all the steps above, refresh this page to access the
                                        application.
                                        The system will automatically detect your configuration and redirect you to the
                                        login page.
                                    </p>
                                </div>
                                <div class="mt-4">
                                    <button onclick="window.location.reload()"
                                        class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
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
        /**
         * Copy text to clipboard functionality
         */
        function copyToClipboard(elementId) {
            console.log('copyToClipboard called with elementId:', elementId);

            const element = document.getElementById(elementId);
            if (!element) {
                console.error('Element not found:', elementId);
                alert('Copy failed. Please select and copy the text manually.');
                return;
            }

            const text = element.textContent || element.innerText;
            console.log('Text to copy:', text);

            // Function to show success feedback
            function showSuccessFeedback() {
                console.log('Showing success feedback');

                // Find the button - try multiple approaches
                let button = null;

                // First, try to find button in the same parent container
                const container = element.parentElement;
                button = container.querySelector('button[onclick*="copyToClipboard"]');

                // If not found, try looking in the parent's parent (for the redirect-uri case)
                if (!button && container.parentElement) {
                    button = container.parentElement.querySelector('button[onclick*="copyToClipboard"]');
                }

                // If still not found, try a more specific approach for redirect-uri
                if (!button && elementId === 'redirect-uri') {
                    // The redirect-uri is in a div with class bg-blue-50, button should be in same div
                    const blueContainer = element.closest('.bg-blue-50');
                    if (blueContainer) {
                        button = blueContainer.querySelector('button');
                    }
                }

                console.log('Button found:', button);

                if (button) {
                    const originalText = button.textContent;
                    const originalClasses = Array.from(button.classList);

                    console.log('Original button text:', originalText);
                    console.log('Original button classes:', originalClasses);

                    button.textContent = 'Copied!';

                    // Handle different button styles
                    if (button.classList.contains('bg-gray-700')) {
                        button.classList.add('bg-green-600');
                        button.classList.remove('bg-gray-700');
                        button.classList.remove('hover:bg-gray-600');
                    } else if (button.classList.contains('bg-blue-600')) {
                        button.classList.add('bg-green-600');
                        button.classList.remove('bg-blue-600');
                        button.classList.remove('hover:bg-blue-700');
                    }

                    setTimeout(function() {
                        console.log('Restoring button to original state');
                        button.textContent = originalText;
                        button.className = originalClasses.join(' ');
                    }, 30000);
                } else {
                    console.error('Copy button not found in container');
                }
            }

            // Try modern clipboard API first
            if (navigator.clipboard && navigator.clipboard.writeText) {
                console.log('Using modern clipboard API');
                navigator.clipboard.writeText(text).then(function() {
                    console.log('Clipboard API success');
                    showSuccessFeedback();
                }).catch(function(err) {
                    console.error('Clipboard API failed: ', err);
                    fallbackCopy();
                });
            } else {
                console.log('Modern clipboard API not available, using fallback');
                fallbackCopy();
            }

            // Fallback copy method
            function fallbackCopy() {
                console.log('Using fallback copy method');
                try {
                    // Create a temporary textarea element
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    textArea.style.position = 'fixed';
                    textArea.style.left = '-999999px';
                    textArea.style.top = '-999999px';
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();

                    const successful = document.execCommand('copy');
                    document.body.removeChild(textArea);

                    console.log('execCommand copy result:', successful);

                    if (successful) {
                        showSuccessFeedback();
                    } else {
                        throw new Error('execCommand failed');
                    }
                } catch (fallbackErr) {
                    console.error('Fallback copy failed: ', fallbackErr);

                    // Final fallback - select the text for manual copying
                    try {
                        const range = document.createRange();
                        range.selectNodeContents(element);
                        const selection = window.getSelection();
                        selection.removeAllRanges();
                        selection.addRange(range);
                        alert('Text selected. Please press Ctrl+C (or Cmd+C on Mac) to copy.');
                    } catch (selectErr) {
                        console.error('Text selection failed: ', selectErr);
                        alert('Copy failed. Please select and copy the text manually.');
                    }
                }
            }
        }
    </script>
</body>

</html>
