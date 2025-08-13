<x-setup-layout 
    :title="'Database Configuration'" 
    :current-step="2" 
    :total-steps="5" 
    :steps="['Welcome', 'Database', 'Admin User', 'Cloud Storage', 'Complete']">

    <div class="p-8" data-setup-step="database">
        <!-- Progress Indicator -->
        <x-setup-progress-indicator 
            :current-step="$currentStep ?? 'database'" 
            :progress="$progress ?? 40" />

        <!-- Success Message -->
        @if(session('success'))
            <x-setup-success-display 
                :message="session('success')" 
                :show-progress="true"
                :progress="$progress ?? 40" />
        @endif

        <!-- Error Display -->
        @if(session('setup_error'))
            <x-setup-error-display 
                :error="session('setup_error')" 
                title="Database Configuration Error" />
        @elseif($errors->has('database_setup'))
            <x-setup-error-display 
                :error="['user_message' => $errors->first('database_setup')]" 
                title="Database Configuration Error" />
        @endif

        <!-- Header -->
        <div class="text-center mb-8">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 mb-4">
                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Database Configuration</h2>
            <p class="text-gray-600 max-w-md mx-auto">
                Configure your database connection. We support both SQLite for simple deployments and MySQL for production environments.
            </p>
        </div>

        <!-- Database Configuration Form -->
        <form method="POST" action="{{ route('setup.database.configure') }}" id="database-form">
            @csrf

            <!-- Database Type Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Database Type</label>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- SQLite Option -->
                    <div class="relative">
                        <input type="radio" 
                               id="sqlite" 
                               name="database_type" 
                               value="sqlite" 
                               class="sr-only peer"
                               {{ old('database_type', $currentDatabaseType ?? 'sqlite') === 'sqlite' ? 'checked' : '' }}>
                        <label for="sqlite" 
                               class="flex flex-col p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 peer-checked:border-blue-500 peer-checked:bg-blue-50">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-900">SQLite</span>
                                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">Recommended</span>
                            </div>
                            <p class="text-xs text-gray-600">
                                File-based database. Perfect for small to medium deployments. No additional setup required.
                            </p>
                        </label>
                    </div>

                    <!-- MySQL Option -->
                    <div class="relative">
                        <input type="radio" 
                               id="mysql" 
                               name="database_type" 
                               value="mysql" 
                               class="sr-only peer"
                               {{ old('database_type', $currentDatabaseType ?? 'sqlite') === 'mysql' ? 'checked' : '' }}>
                        <label for="mysql" 
                               class="flex flex-col p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 peer-checked:border-blue-500 peer-checked:bg-blue-50">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-900">MySQL/MariaDB</span>
                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">Production</span>
                            </div>
                            <p class="text-xs text-gray-600">
                                Full-featured database server. Ideal for high-traffic deployments and advanced features.
                            </p>
                        </label>
                    </div>
                </div>
                @error('database_type')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- SQLite Configuration -->
            <div id="sqlite-config" class="mb-6 {{ old('database_type', $currentDatabaseType ?? 'sqlite') === 'sqlite' ? '' : 'hidden' }}">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">SQLite Configuration</h3>
                            <div class="mt-2 text-sm text-green-700">
                                <p>SQLite requires minimal configuration. The database file will be created automatically at:</p>
                                <code class="mt-1 block bg-green-100 px-2 py-1 rounded text-xs">{{ database_path('database.sqlite') }}</code>
                                @if($sqliteStatus ?? false)
                                    <p class="mt-2 flex items-center">
                                        <svg class="w-4 h-4 mr-1 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        Database file exists and is writable
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MySQL Configuration -->
            <div id="mysql-config" class="mb-6 {{ old('database_type', $currentDatabaseType ?? 'sqlite') === 'mysql' ? '' : 'hidden' }}">
                <div class="space-y-4">
                    <!-- Host -->
                    <div>
                        <label for="mysql_host" class="block text-sm font-medium text-gray-700">Host</label>
                        <input type="text" 
                               id="mysql_host" 
                               name="mysql_host" 
                               value="{{ old('mysql_host', $mysqlConfig['host'] ?? 'localhost') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="localhost">
                        @error('mysql_host')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Port -->
                    <div>
                        <label for="mysql_port" class="block text-sm font-medium text-gray-700">Port</label>
                        <input type="number" 
                               id="mysql_port" 
                               name="mysql_port" 
                               value="{{ old('mysql_port', $mysqlConfig['port'] ?? '3306') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="3306">
                        @error('mysql_port')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Database Name -->
                    <div>
                        <label for="mysql_database" class="block text-sm font-medium text-gray-700">Database Name</label>
                        <input type="text" 
                               id="mysql_database" 
                               name="mysql_database" 
                               value="{{ old('mysql_database', $mysqlConfig['database'] ?? '') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="upload_drive_in"
                               required>
                        @error('mysql_database')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Username -->
                    <div>
                        <label for="mysql_username" class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" 
                               id="mysql_username" 
                               name="mysql_username" 
                               value="{{ old('mysql_username', $mysqlConfig['username'] ?? '') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="username"
                               required>
                        @error('mysql_username')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="mysql_password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" 
                               id="mysql_password" 
                               name="mysql_password" 
                               value="{{ old('mysql_password', $mysqlConfig['password'] ?? '') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="password">
                        @error('mysql_password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Test Connection Button -->
                    <div>
                        <button type="button" 
                                id="test-connection" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Test Connection
                        </button>
                        <div id="connection-status" class="mt-2 hidden"></div>
                    </div>
                </div>
            </div>

            <!-- Help Panel -->
            <div class="mb-6">
                <x-setup-help-panel step="database" />
            </div>

            <!-- Navigation -->
            <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                <a href="{{ route('setup.welcome') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="mr-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back
                </a>
                <button type="submit" 
                        class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Configure Database
                    <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>
        </form>
    </div>

</x-setup-layout>