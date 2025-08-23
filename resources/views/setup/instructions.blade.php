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
                            <div
                                class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">
                                1
                            </div>
                            <h2 class="ml-3 text-xl font-semibold text-gray-900">Database Configuration</h2>
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
                                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
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
                    <div class="mb-10">
                        <div class="flex items-center mb-4">
                            <div
                                class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">
                                2
                            </div>
                            <h2 class="ml-3 text-xl font-semibold text-gray-900">Mail Configuration</h2>
                        </div>

                        <p class="text-gray-600 mb-4">
                            Add these mail server settings to your <code class="bg-gray-100 px-2 py-1 rounded text-sm">.env</code>
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
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700">
                                        <strong>Required:</strong> Replace all placeholder values with your actual SMTP server credentials.
                                        The application cannot function without proper mail configuration as it's used for upload notifications and user verification.
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
                                        <strong>Local Development:</strong> For local development environments using tools like Mailpit or MailHog 
                                        (host: 127.0.0.1 or localhost, port: 1025), username and password can be set to null. 
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
                                    <div class="space-y-4 text-sm">
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
                    <div class="mb-10">
                        <div class="flex items-center mb-4">
                            <div
                                class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">
                                3
                            </div>
                            <h2 class="ml-3 text-xl font-semibold text-gray-900">Google Drive Configuration</h2>
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
                                    Cloud Project OAuth 2.0 client</a>, add this <strong>Authorized
                                    redirect URI</strong>:
                            </p>

                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 relative">
                                <button onclick="copyToClipboard('redirect-uri')"
                                    class="absolute top-2 right-2 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition-colors">
                                    Copy
                                </button>
                                <div class="pr-16">
                                    <label class="block text-sm font-medium text-blue-800 mb-2">Authorized Redirect
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
                                    <ol class="list-decimal list-inside space-y-3 text-sm text-gray-700">
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

                    <!-- Step 4: Run Migrations -->
                    <div class="mb-10">
                        <div class="flex items-center mb-4">
                            <div
                                class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">
                                4
                            </div>
                            <h2 class="ml-3 text-xl font-semibold text-gray-900">Run Database Migrations</h2>
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

                    <!-- Step 5: Create Admin User -->
                    <div class="mb-10">
                        <div class="flex items-center mb-4">
                            <div
                                class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">
                                5
                            </div>
                            <h2 class="ml-3 text-xl font-semibold text-gray-900">Create Admin User</h2>
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

                    <!-- Step 6: Setup Queue Worker -->
                    <div class="mb-10">
                        <div class="flex items-center mb-4">
                            <div
                                class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">
                                6
                            </div>
                            <h2 class="ml-3 text-xl font-semibold text-gray-900">Setup Queue Worker</h2>
                        </div>

                        <p class="text-gray-600 mb-4">
                            The application uses background jobs for file uploads to Google Drive. You need to set up a queue worker to process these jobs.
                        </p>

                        <div class="bg-gray-900 rounded-lg p-4 relative">
                            <button onclick="copyToClipboard('worker-command')"
                                class="absolute top-2 right-2 bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm transition-colors">
                                Copy
                            </button>
                            <pre id="worker-command" class="text-green-400 text-sm overflow-x-auto"><code>{{ base_path() }}/artisan queue:work</code></pre>
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
                                        <strong>Production Setup:</strong> For production environments, you should set up the queue worker as a daemon using a process manager like Supervisor, systemd, or your hosting provider's process management tools. The worker should restart automatically if it stops.
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
                                <div class="p-4 pt-0 border-t border-gray-200">
                                    <div class="space-y-6 text-sm">
                                        <!-- Supervisor Example -->
                                        <div>
                                            <h4 class="font-semibold text-gray-900 mb-2">Supervisor Configuration</h4>
                                            <p class="text-gray-600 mb-2">Create a file like <code>/etc/supervisor/conf.d/laravel-worker.conf</code>:</p>
                                            <div class="bg-gray-100 p-3 rounded font-mono text-xs overflow-x-auto">
[program:laravel-worker]<br>
process_name=%(program_name)s_%(process_num)02d<br>
command={{ base_path() }}/artisan queue:work --sleep=3 --tries=3 --max-time=3600<br>
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
                                            <p class="text-gray-600 mt-2 text-xs">Then run: <code>sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start laravel-worker:*</code></p>
                                        </div>

                                        <!-- Systemd Example -->
                                        <div>
                                            <h4 class="font-semibold text-gray-900 mb-2">Systemd Service</h4>
                                            <p class="text-gray-600 mb-2">Create a file like <code>/etc/systemd/system/laravel-worker.service</code>:</p>
                                            <div class="bg-gray-100 p-3 rounded font-mono text-xs overflow-x-auto">
[Unit]<br>
Description=Laravel Queue Worker<br>
After=network.target<br>
<br>
[Service]<br>
User=www-data<br>
Group=www-data<br>
Restart=always<br>
ExecStart={{ base_path() }}/artisan queue:work --sleep=3 --tries=3 --max-time=3600<br>
WorkingDirectory={{ base_path() }}<br>
<br>
[Install]<br>
WantedBy=multi-user.target
                                            </div>
                                            <p class="text-gray-600 mt-2 text-xs">Then run: <code>sudo systemctl enable laravel-worker && sudo systemctl start laravel-worker</code></p>
                                        </div>

                                        <!-- Laravel Forge Example -->
                                        <div>
                                            <h4 class="font-semibold text-gray-900 mb-2">Laravel Forge</h4>
                                            <p class="text-gray-600 mb-2">In your Forge dashboard, go to your site → Daemons and add:</p>
                                            <div class="bg-gray-100 p-3 rounded font-mono text-xs">
                                                <strong>Command:</strong> {{ base_path() }}/artisan queue:work --sleep=3 --tries=3 --max-time=3600<br>
                                                <strong>User:</strong> forge<br>
                                                <strong>Directory:</strong> {{ base_path() }}
                                            </div>
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
                                        <strong>Testing:</strong> For testing purposes, you can run the worker manually with the command above. However, for production use, always set up a proper daemon process that will restart automatically if it fails.
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
