<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('messages.client_management_title') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Create User Form Section -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ __('messages.create_user_title') }}
                </h2>

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 border border-red-200 text-red-700 rounded">
                        <p class="font-bold">{{ __('messages.create_user_error_intro') }}</p>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4" x-data="{ 
                    submitting: false,
                    formErrors: {},
                    
                    validateForm() {
                        this.formErrors = {};
                        let isValid = true;
                        
                        // Validate name field
                        const nameField = $el.querySelector('input[name=name]');
                        if (!nameField.value.trim()) {
                            this.formErrors.name = '{{ __('messages.validation_name_required') }}';
                            isValid = false;
                        } else if (nameField.value.length > 255) {
                            this.formErrors.name = '{{ __('messages.validation_name_max') }}';
                            isValid = false;
                        }
                        
                        // Validate email field
                        const emailField = $el.querySelector('input[name=email]');
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailField.value.trim()) {
                            this.formErrors.email = '{{ __('messages.validation_email_required') }}';
                            isValid = false;
                        } else if (!emailRegex.test(emailField.value)) {
                            this.formErrors.email = '{{ __('messages.validation_email_format') }}';
                            isValid = false;
                        }
                        
                        return isValid;
                    },
                    
                    submitForm(action) {
                        if (this.submitting) return;
                        
                        // Validate form before submission
                        if (!this.validateForm()) {
                            return;
                        }
                        
                        // Validate action parameter
                        if (!action || !['create', 'create_and_invite'].includes(action)) {
                            this.formErrors.action = '{{ __('messages.validation_action_required') }}';
                            return;
                        }
                        
                        this.submitting = true;
                        
                        // Set the action parameter
                        const actionInput = document.createElement('input');
                        actionInput.type = 'hidden';
                        actionInput.name = 'action';
                        actionInput.value = action;
                        $el.appendChild(actionInput);
                        
                        // Submit the form
                        $el.submit();
                    }
                }">
                    @csrf
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">{{ __('messages.label_name') }}</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--brand-color)] focus:ring focus:ring-[var(--brand-color)]/50 sm:text-sm"
                               :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': formErrors.name }"
                               @input="formErrors.name = ''">
                        <p x-show="formErrors.name" x-text="formErrors.name" class="mt-1 text-sm text-red-600"></p>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">{{ __('messages.label_email') }}</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--brand-color)] focus:ring focus:ring-[var(--brand-color)]/50 sm:text-sm"
                               :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': formErrors.email }"
                               @input="formErrors.email = ''">
                        <p x-show="formErrors.email" x-text="formErrors.email" class="mt-1 text-sm text-red-600"></p>
                    </div>
                    <div class="mb-3">
                        <p class="text-sm text-gray-600">{{ __('messages.dual_action_help_text') }}</p>
                        <p x-show="formErrors.action" x-text="formErrors.action" class="mt-1 text-sm text-red-600"></p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button 
                            type="button" 
                            @click="submitForm('create')"
                            :disabled="submitting"
                            class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)] disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                            title="{{ __('messages.button_create_user_tooltip') }}"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span x-show="!submitting">{{ __('messages.button_create_user') }}</span>
                            <span x-show="submitting" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('messages.button_create_user_loading') }}
                            </span>
                        </button>
                        <button 
                            type="button" 
                            @click="submitForm('create_and_invite')"
                            :disabled="submitting"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-[var(--brand-color)] hover:brightness-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)] disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                            title="{{ __('messages.button_create_and_invite_tooltip') }}"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <span x-show="!submitting">{{ __('messages.button_create_and_invite') }}</span>
                            <span x-show="submitting" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('messages.button_create_and_invite_loading') }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Users Table Section -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div x-data="adminUsersData()" x-init="initData()"
 class="max-w-full">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">
                        {{ __('messages.users_list_title') }}
                    </h2>

                     @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded">
                            {{ session('success') }}
                        </div>
                     @endif
                     @if (session('warning'))
                        <div class="mb-4 p-4 bg-yellow-100 border border-yellow-200 text-yellow-700 rounded">
                            {{ session('warning') }}
                        </div>
                     @endif
                     @if (session('error'))
                        <div class="mb-4 p-4 bg-red-100 border border-red-200 text-red-700 rounded">
                            {{ session('error') }}
                        </div>
                     @endif

                     <!-- Delete User Confirmation Modal -->
                    <div x-show="showDeleteModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center" style="display: none;" @keydown.escape.window="showDeleteModal = false">
                        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="showDeleteModal = false"></div>
                        <div class="relative bg-white rounded-lg shadow-xl p-6 m-4 max-w-md w-full" @click.stop>
                            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                                {{ __('messages.delete_user_modal_title') }}
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 mb-4">
                                    {{ __('messages.delete_user_modal_text') }}
                                </p>
                                <label class="flex items-center space-x-2 mb-6">
                                    <input type="checkbox" x-model="deleteFilesCheckbox" class="rounded border-gray-300 text-[var(--brand-color)] shadow-sm focus:ring-[var(--brand-color)]">
                                    <span class="text-sm text-gray-700">{{ __('messages.delete_user_modal_checkbox') }}</span>
                                </label>
                            </div>
                            <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse space-y-2 sm:space-y-0 sm:space-x-3 sm:space-x-reverse">
                                <button @click="confirmDeleteUser()" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                                    {{ __('messages.delete_user_modal_confirm_button') }}
                                </button>
                                <button @click="showDeleteModal = false; userToDeleteId = null;" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)] sm:mt-0 sm:w-auto sm:text-sm">
                                    {{ __('messages.delete_modal_cancel_button') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Column Visibility Controls -->
                    <div class="mb-4 p-4 border rounded bg-gray-50 hidden lg:block">
                        <h3 class="text-md font-medium text-gray-700 mb-2">{{ __('messages.toggle_user_columns_title') }}</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-sm">
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.name" class="rounded border-gray-300 text-[var(--brand-color)] shadow-sm focus:ring-[var(--brand-color)]">
                                <span>{{ __('messages.label_name') }}</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.email" class="rounded border-gray-300 text-[var(--brand-color)] shadow-sm focus:ring-[var(--brand-color)]">
                                <span>{{ __('messages.label_email') }}</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.createdAt" class="rounded border-gray-300 text-[var(--brand-color)] shadow-sm focus:ring-[var(--brand-color)]">
                                <span>{{ __('messages.column_created_at') }}</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.loginUrl" class="rounded border-gray-300 text-[var(--brand-color)] shadow-sm focus:ring-[var(--brand-color)]">
                                <span>{{ __('messages.column_login_url') }}</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.actions" class="rounded border-gray-300 text-[var(--brand-color)] shadow-sm focus:ring-[var(--brand-color)]">
                                <span>{{ __('messages.column_actions') }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Filter Controls -->
                    <div class="mb-4 space-y-4">
                        <!-- Primary Contact Filter -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="flex items-center space-x-4">
                                <label class="text-sm font-medium text-gray-700">{{ __('messages.filter_by_primary_contact') }}:</label>
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('admin.users.index') }}" 
                                       class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)] {{ !request('filter') ? 'bg-gray-100 border-gray-400' : '' }}">
                                        {{ __('messages.filter_all_clients') }}
                                    </a>
                                    <a href="{{ route('admin.users.index', ['filter' => 'primary_contact']) }}" 
                                       class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)] {{ request('filter') === 'primary_contact' ? 'bg-blue-100 border-blue-400 text-blue-700' : '' }}">
                                        <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        {{ __('messages.filter_primary_contact_only') }}
                                    </a>
                                </div>
                            </div>
                            @if(request('filter') === 'primary_contact')
                                <div class="text-sm text-blue-600 bg-blue-50 px-3 py-1.5 rounded-md">
                                    {{ __('messages.showing_primary_contact_clients_only') }}
                                </div>
                            @endif
                        </div>
                        
                        <!-- Search Input -->
                        <div>
                            <label for="userFilter" class="sr-only">{{ __('messages.filter_users_label') }}</label>
                            <input type="text" id="userFilter" x-model.debounce.300ms="filterQuery" placeholder="{{ __('messages.filter_users_placeholder') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--brand-color)] focus:ring focus:ring-[var(--brand-color)]/50 sm:text-sm">
                        </div>
                    </div>

                    {{-- Mobile Card View --}}
                    <div class="lg:hidden space-y-4">
                        <template x-for="client in filteredAndSortedClients" :key="client.id">
                            <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
                                {{-- Card Header --}}
                                <div class="border-b border-gray-200 pb-3 mb-3">
                                    <div class="flex justify-between items-start gap-2">
                                        <div class="flex items-center space-x-2">
                                            <div class="font-medium text-gray-900 break-words" x-text="client.name"></div>
                                            <template x-if="client.is_primary_contact_for_current_user">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    {{ __('messages.primary_contact_badge') }}
                                                </span>
                                            </template>
                                        </div>
                                        <div class="flex shrink-0 space-x-2">
                                            {{-- Manage Button --}}
                                            <a :href="`/admin/users/${client.id}`" class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-[var(--brand-color)] bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)]">
                                                Manage
                                            </a>
                                            {{-- Copy URL Button --}}
                                            <button @click="copyLoginUrl(client)" class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)]">
                                                <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                <span x-show="copiedUrlId !== client.id">{{ __('messages.button_copy_login_url') }}</span>
                                                <span x-show="copiedUrlId === client.id" class="text-green-600">{{ __('messages.copied_confirmation') }}</span>
                                            </button>
                                            {{-- Delete Button --}}
                                            <button @click="deleteUser(client.id)" class="shrink-0 inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 text-sm font-medium rounded-md hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                {{ __('messages.delete_button') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                {{-- Card Body --}}
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">{{ __('messages.label_email') }}:</span>
                                        <span class="font-medium text-gray-900 truncate" x-text="client.email"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">{{ __('messages.mobile_label_created_at') }}</span>
                                        <span class="font-medium text-gray-900" x-text="formatDate(client.created_at)"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                        {{-- No results message for mobile --}}
                        <template x-if="filteredAndSortedClients.length === 0">
                            <div class="text-center text-gray-500 py-4">
                                {{ __('messages.no_users_match_filter') }}
                            </div>
                        </template>
                         @if($clients->isEmpty() && empty(request('page')))
                            <template x-if="clientsData.length === 0 && filterQuery.trim() === ''">
                                <div class="text-center text-gray-500 py-4">
                                     {{ __('messages.no_users_found') }}
                                </div>
                            </template>
                         @endif
                    </div>

                    {{-- Desktop Table View --}}
                    <div class="hidden lg:block">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <template x-if="columns.name">
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" @click="sortBy('name')">
                                                {{ __('messages.label_name') }} <span x-show="sortColumn === 'name'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                            </th>
                                        </template>
                                        <template x-if="columns.email">
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" @click="sortBy('email')">
                                                {{ __('messages.label_email') }} <span x-show="sortColumn === 'email'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                            </th>
                                        </template>
                                        <template x-if="columns.createdAt">
                                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" @click="sortBy('created_at')">
                                                {{ __('messages.column_created_at') }} <span x-show="sortColumn === 'created_at'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                            </th>
                                        </template>
                                        <template x-if="columns.loginUrl">
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.column_login_url') }}</th>
                                        </template>
                                        <template x-if="columns.actions"><th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.column_actions') }}</th></template>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="client in filteredAndSortedClients" :key="client.id">
                                        <tr>
                                            <template x-if="columns.name">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <div class="flex items-center space-x-2">
                                                        <span x-text="client.name"></span>
                                                        <template x-if="client.is_primary_contact_for_current_user">
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                {{ __('messages.primary_contact_badge') }}
                                                            </span>
                                                        </template>
                                                    </div>
                                                </td>
                                            </template>
                                            <template x-if="columns.email"><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="client.email"></td></template>
                                            <template x-if="columns.createdAt"><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(client.created_at)"></td></template>
                                            <template x-if="columns.loginUrl">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <button @click="copyLoginUrl(client)" class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)]">
                                                        <span x-show="copiedUrlId !== client.id">{{ __('messages.button_copy_login_url') }}</span>
                                                        <span x-show="copiedUrlId === client.id" class="text-green-600">{{ __('messages.copied_confirmation') }}</span>
                                                    </button>
                                                </td>
                                            </template>
                                            <template x-if="columns.actions">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                    <a :href="`/admin/users/${client.id}`" class="text-[var(--brand-color)] hover:text-[var(--brand-color)]/80">Manage</a>
                                                    <button @click="deleteUser(client.id)" class="text-red-600 hover:text-red-900">{{ __('messages.delete_button') }}</button>
                                                </td>
                                            </template>
                                        </tr>
                                    </template>
                                    <!-- Message if no clients match filter -->
                                    <template x-if="clientsData.length > 0 && filteredAndSortedClients.length === 0">
                                        <tr>
                                            <td :colspan="Object.values(columns).filter(v => v).length" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                                {{ __('messages.no_users_match_filter') }}
                                            </td>
                                        </tr>
                                    </template>
                                    <!-- Message if no clients at all -->
                                    <template x-if="clientsData.length === 0">
                                        <tr>
                                            <td :colspan="Object.values(columns).filter(v => v).length" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                                {{ __('messages.no_users_found') }}
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-4">
                        {{ $clients->links() }} <!-- Ensure pagination links are displayed -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function adminUsersData() {
            return {
                clientsData: [],
                filterQuery: '',
                sortColumn: 'created_at',
                sortDirection: 'desc',
                columns: Alpine.$persist({
                    name: true,
                    email: true,
                    createdAt: true,
                    loginUrl: true,
                    actions: true
                }).as('adminUserColumns'),
                showDeleteModal: false,
                userToDeleteId: null,
                deleteFilesCheckbox: false,
                copiedUrlId: null,

                get filteredAndSortedClients() {
                    let filtered = this.clientsData;

                    if (this.filterQuery.trim() !== '') {
                        const query = this.filterQuery.trim().toLowerCase();
                        filtered = filtered.filter(client => {
                            return (client.name && client.name.toLowerCase().includes(query)) ||
                                   (client.email && client.email.toLowerCase().includes(query));
                        });
                    }

                    if (this.sortColumn) {
                        filtered.sort((a, b) => {
                            let valA = a[this.sortColumn];
                            let valB = b[this.sortColumn];

                            if (this.sortColumn === 'created_at') {
                                valA = new Date(valA);
                                valB = new Date(valB);
                            } else if (valA && typeof valA === 'string') {
                                valA = valA.toLowerCase();
                                valB = valB.toLowerCase();
                            }

                            let comparison = 0;
                            if (valA > valB) {
                                comparison = 1;
                            } else if (valA < valB) {
                                comparison = -1;
                            }
                            return this.sortDirection === 'asc' ? comparison : -comparison;
                        });
                    }

                    return filtered;
                },

                sortBy(column) {
                    if (this.sortColumn === column) {
                        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        this.sortColumn = column;
                        this.sortDirection = 'asc';
                    }
                },

                formatDate(dateString) {
                    const options = { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' };
                    return new Date(dateString).toLocaleString(undefined, options);
                },

                copyLoginUrl(client) {
                    navigator.clipboard.writeText(client.loginUrl);
                    this.copiedUrlId = client.id;
                    setTimeout(() => {
                        if (this.copiedUrlId === client.id) {
                             this.copiedUrlId = null;
                        }
                    }, 2000);
                },

                deleteUser(userId) {
                     this.userToDeleteId = userId;
                     this.deleteFilesCheckbox = false;
                     this.showDeleteModal = true;
                },

                confirmDeleteUser() {
                    if (!this.userToDeleteId) return;

                    let form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/admin/users/' + this.userToDeleteId;
                    form.style.display = 'none';

                    let csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = '{{ csrf_token() }}';
                    form.appendChild(csrfInput);

                    let methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'DELETE';
                    form.appendChild(methodInput);

                    let deleteFilesInput = document.createElement('input');
                    deleteFilesInput.type = 'hidden';
                    deleteFilesInput.name = 'delete_files';
                    deleteFilesInput.value = this.deleteFilesCheckbox ? '1' : '0';
                    form.appendChild(deleteFilesInput);

                    document.body.appendChild(form);
                    form.submit();

                    this.showDeleteModal = false;
                    this.userToDeleteId = null;
                },

                initData() {
                    this.clientsData = {!! json_encode($clients->items(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
                    this.clientsData = this.clientsData.map(client => {
                        client.loginUrl = client.login_url;
                        client.is_primary_contact_for_current_user = client.is_primary_contact_for_current_user || false;
                        return client;
                    });
                    this.copiedUrlId = null;
                }
            }
        }
    </script>
</x-app-layout>
