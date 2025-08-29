<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('messages.client_management_title') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Create Client Form Section -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ __('messages.create_client_user') }}
                </h2>

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 border border-red-200 text-red-700 rounded">
                        <p class="font-bold">{{ __('messages.create_client_error_intro') }}</p>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 p-4 bg-red-100 border border-red-200 text-red-700 rounded">
                        {{ session('error') }}
                    </div>
                @endif

                @if (session('status') === 'employee-client-created')
                    <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded">
                        {{ __('messages.employee_client_created') }}
                    </div>
                @endif

                @if (session('status') === 'employee-client-created-and-invited')
                    <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded">
                        {{ __('messages.employee_client_created_and_invited') }}
                    </div>
                @endif

                @if (session('status') === 'employee-client-created-email-failed')
                    <div class="mb-4 p-4 bg-yellow-100 border border-yellow-200 text-yellow-700 rounded">
                        {{ __('messages.employee_client_created_email_failed') }}
                    </div>
                @endif

                @if (session('warning'))
                    <div class="mb-4 p-4 bg-yellow-100 border border-yellow-200 text-yellow-700 rounded">
                        {{ session('warning') }}
                    </div>
                @endif

                @if (session('status') === 'client-created')
                    <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded">
                        {{ __('messages.client_created') }}
                    </div>
                @endif

                @if (session('status') === 'client-created-and-invited')
                    <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded">
                        {{ __('messages.client_created_and_invited') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('employee.clients.store', ['username' => auth()->user()->username]) }}" class="space-y-4" x-data="{ 
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

            <!-- Clients Table Section -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div x-data="{
                        clientsData: {{ json_encode($clientUsers->items()) }},
                        filterQuery: '',
                        sortColumn: 'created_at',
                        sortDirection: 'desc',
                        columns: $persist({
                            name: true,
                            email: true,
                            createdAt: true,
                            loginUrl: true,
                            actions: true
                        }).as('employeeClientColumns'),
                        copiedUrlId: null,

                        init() {
                            this.clientsData = this.clientsData.map(client => {
                                client.loginUrl = client.login_url;
                                return client;
                            });
                            this.copiedUrlId = null;
                        },

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
                        }

                    }" x-init="init()" class="max-w-full">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">
                        {{ __('messages.my_clients_title') }}
                    </h2>

                    <!-- Column Visibility Controls -->
                    <div class="mb-4 p-4 border rounded bg-gray-50 hidden lg:block">
                        <h3 class="text-md font-medium text-gray-700 mb-2">{{ __('messages.toggle_client_columns_title') }}</h3>
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
                        </div>
                    </div>

                    <!-- Filter Input -->
                    <div class="mb-4">
                        <label for="clientFilter" class="sr-only">{{ __('messages.filter_clients_label') }}</label>
                        <input type="text" id="clientFilter" x-model.debounce.300ms="filterQuery" placeholder="{{ __('messages.filter_clients_placeholder') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--brand-color)] focus:ring focus:ring-[var(--brand-color)]/50 sm:text-sm">
                    </div>

                    {{-- Mobile Card View --}}
                    <div class="lg:hidden space-y-4">
                        <template x-for="client in filteredAndSortedClients" :key="client.id">
                            <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
                                <div class="border-b border-gray-200 pb-3 mb-3">
                                    <div class="flex justify-between items-start gap-2">
                                        <div class="font-medium text-gray-900 break-words" x-text="client.name"></div>
                                        <div class="flex shrink-0 space-x-2">
                                            <button @click="copyLoginUrl(client)" class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)]">
                                                <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                <span x-show="copiedUrlId !== client.id">{{ __('messages.button_copy_login_url') }}</span>
                                                <span x-show="copiedUrlId === client.id" class="text-green-600">{{ __('messages.copied_confirmation') }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
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
                        <template x-if="filteredAndSortedClients.length === 0">
                            <div class="text-center text-gray-500 py-4">
                                {{ __('messages.no_clients_match_filter') }}
                            </div>
                        </template>
                         @if($clientUsers->isEmpty() && empty(request('page')))
                            <template x-if="clientsData.length === 0 && filterQuery.trim() === ''">
                                <div class="text-center text-gray-500 py-4">
                                     {{ __('messages.no_clients_found') }}
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
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="client in filteredAndSortedClients" :key="client.id">
                                        <tr>
                                            <template x-if="columns.name"><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="client.name"></td></template>
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
                                        </tr>
                                    </template>
                                    <template x-if="clientsData.length > 0 && filteredAndSortedClients.length === 0">
                                        <tr>
                                            <td :colspan="Object.values(columns).filter(v => v).length" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                                {{ __('messages.no_clients_match_filter') }}
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-if="clientsData.length === 0">
                                        <tr>
                                            <td :colspan="Object.values(columns).filter(v => v).length" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                                {{ __('messages.no_clients_found') }}
                                            </td>
                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-4">
                        {{ $clientUsers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>


</x-app-layout>