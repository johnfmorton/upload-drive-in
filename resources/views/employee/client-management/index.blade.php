<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('messages.client_management_title') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Google Drive Connection Section -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ __('messages.google_drive_connection') }}
                </h2>
                
                @if(auth()->user()->googleDriveToken)
                    <div class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-green-800">{{ __('messages.google_drive_connected') }}</p>
                                <p class="text-sm text-green-600">{{ __('messages.client_uploads_will_go_to_your_drive') }}</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('employee.google-drive.disconnect', ['username' => auth()->user()->username]) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                {{ __('messages.disconnect') }}
                            </button>
                        </form>
                    </div>
                @else
                    <div class="flex items-center justify-between p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-yellow-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-yellow-800">{{ __('messages.google_drive_not_connected') }}</p>
                                <p class="text-sm text-yellow-600">{{ __('messages.connect_drive_to_receive_uploads') }}</p>
                            </div>
                        </div>
                        <a href="{{ route('employee.google-drive.connect', ['username' => auth()->user()->username]) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-[var(--brand-color)] hover:brightness-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)]">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/>
                            </svg>
                            {{ __('messages.connect_google_drive') }}
                        </a>
                    </div>
                @endif

                @if(auth()->user()->googleDriveToken)
                    <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h3 class="text-sm font-medium text-blue-800 mb-2">{{ __('messages.your_upload_page') }}</h3>
                        <div class="flex items-center justify-between">
                            <code class="text-sm bg-white px-2 py-1 rounded border">{{ url('/upload/' . explode('@', auth()->user()->email)[0]) }}</code>
                            <button onclick="copyUploadUrl()" class="inline-flex items-center px-3 py-1 border border-blue-300 shadow-sm text-xs font-medium rounded text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <span id="copy-text">{{ __('messages.copy_url') }}</span>
                            </button>
                        </div>
                        <p class="text-xs text-blue-600 mt-2">{{ __('messages.share_this_url_with_clients') }}</p>
                    </div>
                @endif
            </div>

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

                @if (session('status') === 'client-created')
                    <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded">
                        {{ __('messages.client_created_success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('employee.clients.store', ['username' => auth()->user()->username]) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">{{ __('messages.label_name') }}</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--brand-color)] focus:ring focus:ring-[var(--brand-color)]/50 sm:text-sm">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">{{ __('messages.label_email') }}</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--brand-color)] focus:ring focus:ring-[var(--brand-color)]/50 sm:text-sm">
                    </div>
                    <div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-[var(--brand-color)] hover:brightness-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)]">
                            {{ __('messages.create_and_invite_button') }}
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

    <script>
        function copyUploadUrl() {
            const url = '{{ url('/upload/' . explode('@', auth()->user()->email)[0]) }}';
            navigator.clipboard.writeText(url).then(function() {
                const copyText = document.getElementById('copy-text');
                const originalText = copyText.textContent;
                copyText.textContent = '{{ __('messages.copied') }}';
                copyText.classList.add('text-green-600');
                
                setTimeout(function() {
                    copyText.textContent = originalText;
                    copyText.classList.remove('text-green-600');
                }, 2000);
            });
        }
    </script>
</x-app-layout>