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

        <!-- Step Completion Feedback -->
        @if(session('step_completed'))
            @php $stepData = session('step_completed'); @endphp
            <x-setup-step-completion 
                :step="$stepData['step']"
                :title="$stepData['details']['title']"
                :message="$stepData['details']['message']"
                :details="$stepData['details']['details']"
                :next-step="$stepData['next_step']"
                :progress="$stepData['progress']"
                :auto-advance="true"
                :auto-advance-delay="3000" />
        @endif

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
                               class="sr-only peer database-type-radio"
                               {{ old('database_type', $databaseType ?? 'sqlite') === 'sqlite' ? 'checked' : '' }}>
                        <label for="sqlite" 
                               class="flex flex-col p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-900">SQLite</span>
                                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">Recommended</span>
                            </div>
                            <p class="text-xs text-gray-600">
                                File-based database. Perfect for small to medium deployments. No additional setup required.
                            </p>
                            <div class="mt-2 text-xs text-gray-500">
                                ✓ Zero configuration • ✓ Automatic backups • ✓ High performance
                            </div>
                        </label>
                    </div>

                    <!-- MySQL Option -->
                    <div class="relative">
                        <input type="radio" 
                               id="mysql" 
                               name="database_type" 
                               value="mysql" 
                               class="sr-only peer database-type-radio"
                               {{ old('database_type', $databaseType ?? 'sqlite') === 'mysql' ? 'checked' : '' }}>
                        <label for="mysql" 
                               class="flex flex-col p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-900">MySQL/MariaDB</span>
                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">Production</span>
                            </div>
                            <p class="text-xs text-gray-600">
                                Full-featured database server. Ideal for high-traffic deployments and advanced features.
                            </p>
                            <div class="mt-2 text-xs text-gray-500">
                                ✓ Scalable • ✓ Advanced features • ✓ Industry standard
                            </div>
                        </label>
                    </div>
                </div>
                @error('database_type')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- SQLite Configuration -->
            <div id="sqlite-config" class="mb-6 database-config-section {{ old('database_type', $databaseType ?? 'sqlite') === 'sqlite' ? '' : 'hidden' }}">
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
                                <code class="mt-1 block bg-green-100 px-2 py-1 rounded text-xs font-mono">{{ database_path('database.sqlite') }}</code>
                                
                                <!-- SQLite Status Check -->
                                <div id="sqlite-status" class="mt-3">
                                    <div class="flex items-center">
                                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-green-600 mr-2 hidden" id="sqlite-loading"></div>
                                        <button type="button" 
                                                id="test-sqlite-connection" 
                                                class="inline-flex items-center px-3 py-1 border border-green-300 text-xs font-medium rounded text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Check SQLite Setup
                                        </button>
                                    </div>
                                    <div id="sqlite-status-result" class="mt-2 hidden"></div>
                                </div>

                                <!-- Optional Custom Path -->
                                <div class="mt-4">
                                    <label class="flex items-center">
                                        <input type="checkbox" id="sqlite-custom-path" class="rounded border-gray-300 text-green-600 shadow-sm focus:border-green-300 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                                        <span class="ml-2 text-xs text-green-700">Use custom database path</span>
                                    </label>
                                    <div id="sqlite-path-input" class="mt-2 hidden">
                                        <input type="text" 
                                               name="sqlite_path" 
                                               id="sqlite_path"
                                               value="{{ old('sqlite_path', '') }}"
                                               class="block w-full text-xs border-green-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                               placeholder="{{ database_path('database.sqlite') }}">
                                        <p class="mt-1 text-xs text-green-600">Leave empty to use the default path</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MySQL Configuration -->
            <div id="mysql-config" class="mb-6 database-config-section {{ old('database_type', $databaseType ?? 'sqlite') === 'mysql' ? '' : 'hidden' }}">
                <div class="space-y-6">
                    <!-- Connection Settings Header -->
                    <div class="border-b border-gray-200 pb-2">
                        <h3 class="text-lg font-medium text-gray-900">MySQL Connection Settings</h3>
                        <p class="mt-1 text-sm text-gray-600">Enter your MySQL database connection details below.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Host -->
                        <div class="sm:col-span-1">
                            <label for="mysql_host" class="block text-sm font-medium text-gray-700">
                                Host <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 relative">
                                <input type="text" 
                                       id="mysql_host" 
                                       name="mysql_host" 
                                       value="{{ old('mysql_host', $mysqlConfig['host'] ?? 'localhost') }}"
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm mysql-field"
                                       placeholder="localhost"
                                       required>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                Examples: <code class="bg-gray-100 px-1 rounded">localhost</code>, <code class="bg-gray-100 px-1 rounded">127.0.0.1</code>, <code class="bg-gray-100 px-1 rounded">mysql.example.com</code>
                            </p>
                            @error('mysql_host')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Port -->
                        <div class="sm:col-span-1">
                            <label for="mysql_port" class="block text-sm font-medium text-gray-700">
                                Port <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 relative">
                                <input type="number" 
                                       id="mysql_port" 
                                       name="mysql_port" 
                                       value="{{ old('mysql_port', $mysqlConfig['port'] ?? '3306') }}"
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm mysql-field"
                                       placeholder="3306"
                                       min="1"
                                       max="65535"
                                       required>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                Default MySQL port is <code class="bg-gray-100 px-1 rounded">3306</code>
                            </p>
                            @error('mysql_port')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Database Name -->
                    <div>
                        <label for="mysql_database" class="block text-sm font-medium text-gray-700">
                            Database Name <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1 relative">
                            <input type="text" 
                                   id="mysql_database" 
                                   name="mysql_database" 
                                   value="{{ old('mysql_database', $mysqlConfig['database'] ?? '') }}"
                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm mysql-field"
                                   placeholder="upload_drive_in"
                                   pattern="[a-zA-Z0-9_]+"
                                   required>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Only letters, numbers, and underscores allowed. Examples: <code class="bg-gray-100 px-1 rounded">upload_drive_in</code>, <code class="bg-gray-100 px-1 rounded">myapp_db</code>
                        </p>
                        @error('mysql_database')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Username -->
                        <div class="sm:col-span-1">
                            <label for="mysql_username" class="block text-sm font-medium text-gray-700">
                                Username <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 relative">
                                <input type="text" 
                                       id="mysql_username" 
                                       name="mysql_username" 
                                       value="{{ old('mysql_username', $mysqlConfig['username'] ?? '') }}"
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm mysql-field"
                                       placeholder="database_user"
                                       required>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                MySQL user with database access permissions
                            </p>
                            @error('mysql_username')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="sm:col-span-1">
                            <label for="mysql_password" class="block text-sm font-medium text-gray-700">
                                Password
                            </label>
                            <div class="mt-1 relative">
                                <input type="password" 
                                       id="mysql_password" 
                                       name="mysql_password" 
                                       value="{{ old('mysql_password', $mysqlConfig['password'] ?? '') }}"
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm mysql-field"
                                       placeholder="••••••••">
                                <button type="button" 
                                        id="toggle-password" 
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <svg class="h-4 w-4 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="password-show-icon">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    <svg class="h-4 w-4 text-gray-400 hover:text-gray-600 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="password-hide-icon">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                Leave empty if no password is required
                            </p>
                            @error('mysql_password')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Connection Test Section -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-medium text-gray-900">Connection Test</h4>
                            <button type="button" 
                                    id="test-mysql-connection" 
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                                    disabled>
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span id="test-button-text">Test Connection</span>
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 ml-2 hidden" id="mysql-loading"></div>
                            </button>
                        </div>
                        <p class="text-xs text-gray-600 mb-3">
                            Test your database connection before proceeding. Fill in all required fields above to enable testing.
                        </p>
                        <div id="mysql-connection-status" class="hidden"></div>
                        
                        <!-- Progress Indicator -->
                        <div id="connection-progress" class="hidden mt-3">
                            <div class="bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%" id="progress-bar"></div>
                            </div>
                            <p class="text-xs text-gray-600 mt-1" id="progress-text">Initializing connection test...</p>
                        </div>
                    </div>

                    <!-- Common Configuration Examples -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-blue-900 mb-2">Common Configurations</h4>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <h5 class="text-xs font-medium text-blue-800">Local Development</h5>
                                <div class="text-xs text-blue-700 mt-1 space-y-1">
                                    <div>Host: <code class="bg-blue-100 px-1 rounded">localhost</code></div>
                                    <div>Port: <code class="bg-blue-100 px-1 rounded">3306</code></div>
                                    <div>User: <code class="bg-blue-100 px-1 rounded">root</code></div>
                                </div>
                            </div>
                            <div>
                                <h5 class="text-xs font-medium text-blue-800">Shared Hosting</h5>
                                <div class="text-xs text-blue-700 mt-1 space-y-1">
                                    <div>Host: <code class="bg-blue-100 px-1 rounded">localhost</code></div>
                                    <div>Port: <code class="bg-blue-100 px-1 rounded">3306</code></div>
                                    <div>DB: <code class="bg-blue-100 px-1 rounded">username_dbname</code></div>
                                </div>
                            </div>
                        </div>
                        <button type="button" 
                                id="show-hosting-help" 
                                class="mt-3 text-xs text-blue-600 hover:text-blue-800 underline">
                            Show hosting provider setup instructions
                        </button>
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

    @push('scripts')
        @vite('resources/js/database-config.js')
    @endpush

</x-setup-layout>