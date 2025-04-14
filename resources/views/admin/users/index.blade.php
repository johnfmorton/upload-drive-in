<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('messages.user_management_title') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Create User Form Section -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ __('messages.create_user_title') }}
                </h2>

                @if ($errors->hasBag('createUser'))
                    <div class="mb-4 p-4 bg-red-100 border border-red-200 text-red-700 rounded">
                        <p class="font-bold">{{ __('messages.create_user_error_intro') }}</p>
                        <ul>
                            @foreach ($errors->createUser->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">{{ __('messages.label_name') }}</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">{{ __('messages.label_email') }}</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm">
                    </div>
                    <div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('messages.button_create_user') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Users Table Section -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div x-data="{
                        clientsData: {{ json_encode($clients->items()) }},
                        filterQuery: '',
                        sortColumn: 'created_at', // Default sort column
                        sortDirection: 'desc', // Default sort direction ('asc' or 'desc')
                        columns: $persist({
                            name: true,
                            email: true,
                            createdAt: true,
                            loginUrl: true, // Added Login URL column
                            actions: true
                        }).as('adminUserColumns'),
                        showDeleteModal: false,
                        userToDeleteId: null,
                        deleteFilesCheckbox: false,
                        copiedUrlId: null, // Track which URL was just copied

                        // Add loginUrl property to each client object
                        init() {
                            this.clientsData = this.clientsData.map(client => {
                                client.loginUrl = client.login_url; // Assuming 'login_url' is passed from controller
                                return client;
                            });
                            // Reset copied state on init
                            this.copiedUrlId = null;
                        },

                        get filteredAndSortedClients() {
                            let filtered = this.clientsData;

                            // Apply filter
                            if (this.filterQuery.trim() !== '') {
                                const query = this.filterQuery.trim().toLowerCase();
                                filtered = filtered.filter(client => {
                                    return (client.name && client.name.toLowerCase().includes(query)) ||
                                           (client.email && client.email.toLowerCase().includes(query));
                                });
                            }

                            // Apply sort
                            if (this.sortColumn) {
                                filtered.sort((a, b) => {
                                    let valA = a[this.sortColumn];
                                    let valB = b[this.sortColumn];

                                    // Handle specific types if needed
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
                                this.sortDirection = 'asc'; // Default to ascending when changing column
                            }
                        },

                        // Helper to format date
                         formatDate(dateString) {
                            const options = { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit' };
                            return new Date(dateString).toLocaleString(undefined, options);
                        },

                        // Copy login URL to clipboard using Alpine.clipboard()
                        copyLoginUrl(client) {
                            // Use the globally available Alpine object to access clipboard
                            navigator.clipboard.writeText(client.loginUrl);
                            this.copiedUrlId = client.id;
                            // Hide 'Copied!' message after 2 seconds
                            setTimeout(() => {
                                if (this.copiedUrlId === client.id) {
                                     this.copiedUrlId = null;
                                }
                            }, 2000);
                        },

                        // Initiate User Deletion - Show Modal
                        deleteUser(userId) {
                             this.userToDeleteId = userId;
                             this.deleteFilesCheckbox = false; // Reset checkbox state
                             this.showDeleteModal = true;
                        },

                        // Confirm and Submit Deletion
                        confirmDeleteUser() {
                            if (!this.userToDeleteId) return;

                            let form = document.createElement('form');
                            form.method = 'POST';
                            // Construct the action URL manually
                            form.action = '/admin/users/' + this.userToDeleteId;
                            form.style.display = 'none';

                            let csrfInput = document.createElement('input');
                            csrfInput.type = 'hidden';
                            csrfInput.name = '_token';
                            csrfInput.value = '{{ csrf_token() }}'; // Get CSRF token
                            form.appendChild(csrfInput);

                            let methodInput = document.createElement('input');
                            methodInput.type = 'hidden';
                            methodInput.name = '_method';
                            methodInput.value = 'DELETE';
                            form.appendChild(methodInput);

                            // Add the delete_files input
                            let deleteFilesInput = document.createElement('input');
                            deleteFilesInput.type = 'hidden';
                            deleteFilesInput.name = 'delete_files';
                            deleteFilesInput.value = this.deleteFilesCheckbox ? '1' : '0';
                            form.appendChild(deleteFilesInput);

                            document.body.appendChild(form);
                            form.submit();

                            // Hide modal after submission attempt
                            this.showDeleteModal = false;
                            this.userToDeleteId = null;
                        }

                    }" x-init="init()" class="max-w-full">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">
                        {{ __('messages.users_list_title') }}
                    </h2>

                     @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded">
                            {{ session('success') }}
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
                                    <input type="checkbox" x-model="deleteFilesCheckbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">{{ __('messages.delete_user_modal_checkbox') }}</span>
                                </label>
                            </div>
                            <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse space-y-2 sm:space-y-0 sm:space-x-3 sm:space-x-reverse">
                                <button @click="confirmDeleteUser()" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                                    {{ __('messages.delete_user_modal_confirm_button') }}
                                </button>
                                <button @click="showDeleteModal = false; userToDeleteId = null;" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
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
                                <input type="checkbox" x-model="columns.name" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>{{ __('messages.label_name') }}</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.email" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>{{ __('messages.label_email') }}</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.createdAt" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>{{ __('messages.column_created_at') }}</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.loginUrl" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>{{ __('messages.column_login_url') }}</span>
                            </label>
                             <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.actions" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>{{ __('messages.column_actions') }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Filter Input -->
                    <div class="mb-4">
                        <label for="userFilter" class="sr-only">{{ __('messages.filter_users_label') }}</label>
                        <input type="text" id="userFilter" x-model.debounce.300ms="filterQuery" placeholder="{{ __('messages.filter_users_placeholder') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm">
                    </div>

                    {{-- Mobile Card View --}}
                    <div class="lg:hidden space-y-4">
                        <template x-for="client in filteredAndSortedClients" :key="client.id">
                            <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
                                {{-- Card Header --}}
                                <div class="border-b border-gray-200 pb-3 mb-3">
                                    <div class="flex justify-between items-start gap-2">
                                        <div class="font-medium text-gray-900 break-words" x-text="client.name"></div>
                                        <div class="flex shrink-0 space-x-2">
                                             {{-- Copy URL Button --}}
                                            <button @click="copyLoginUrl(client)" class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
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
                                            <template x-if="columns.name"><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="client.name"></td></template>
                                            <template x-if="columns.email"><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="client.email"></td></template>
                                             <template x-if="columns.createdAt"><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(client.created_at)"></td></template>
                                            <template x-if="columns.loginUrl">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <button @click="copyLoginUrl(client)" class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                        <span x-show="copiedUrlId !== client.id">{{ __('messages.button_copy_login_url') }}</span>
                                                        <span x-show="copiedUrlId === client.id" class="text-green-600">{{ __('messages.copied_confirmation') }}</span>
                                                    </button>
                                                </td>
                                            </template>
                                            <template x-if="columns.actions">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                    {{-- Add Edit Link later when edit functionality is built --}}
                                                    {{-- <a :href="`/admin/users/${client.id}/edit`" class="text-indigo-600 hover:text-indigo-900">Edit</a> --}}
                                                    <button @click="deleteUser(client.id)" class="text-red-600 hover:text-red-900">{{ __('messages.delete_button') }}</button>
                                                </td>
                                            </template>
                                        </tr>
                                    </template>
                                    <!-- Message if no clients match filter -->
                                    <template x-if="filteredAndSortedClients.length === 0">
                                        <tr>
                                            <td :colspan="Object.values(columns).filter(v => v).length" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                                {{ __('messages.no_users_match_filter') }}
                                            </td>
                                        </tr>
                                    </template>
                                    <!-- Message if no clients at all -->
                                    @if($clients->isEmpty() && empty(request('page')))
                                        <template x-if="clientsData.length === 0 && filterQuery.trim() === ''">
                                            <tr>
                                                <td :colspan="Object.values(columns).filter(v => v).length" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                                    {{ __('messages.no_users_found') }}
                                                </td>
                                            </tr>
                                        </template>
                                    @endif
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
</x-app-layout>
