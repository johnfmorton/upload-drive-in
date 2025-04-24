<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('messages.employee_management_title') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Create Employee Form Section -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ __('messages.create_employee_title') }}
                </h2>

                @if ($errors->hasBag('createEmployee'))
                    <div class="mb-4 p-4 bg-red-100 border border-red-200 text-red-700 rounded">
                        <p class="font-bold">{{ __('messages.create_user_error_intro') }}</p>
                        <ul>
                            @foreach ($errors->createEmployee->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.employees.store') }}" class="space-y-4">
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
                            {{ __('messages.button_create_employee') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Employees Table Section -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div x-data="{
                        employeesData: {{ json_encode($employees->items()) }},
                        filterQuery: '',
                        sortColumn: 'created_at',
                        sortDirection: 'desc',
                        columns: $persist({
                            name: true,
                            email: true,
                            createdAt: true,
                            loginUrl: true,
                            resetUrl: true,
                            actions: true
                        }).as('adminEmployeeColumns'),
                        showDeleteModal: false,
                        employeeToDeleteId: null,
                        deleteFilesCheckbox: false,
                        copiedLoginUrlId: null,
                        copiedResetUrlId: null,
                        init() {
                            this.employeesData = this.employeesData.map(emp => {
                                emp.loginUrl = emp.login_url;
                                emp.resetUrl = emp.reset_url;
                                return emp;
                            });
                            this.copiedLoginUrlId = null;
                            this.copiedResetUrlId = null;
                        },
                        get filteredAndSortedEmployees() {
                            return this.employeesData;
                        },
                        deleteEmployee(id) {
                            this.employeeToDeleteId = id;
                            this.showDeleteModal = true;
                        },
                        confirmDeleteEmployee() {
                            // ... form submission logic similar to client users ...
                        },
                        copyLoginUrl(emp) {
                            navigator.clipboard.writeText(emp.loginUrl);
                            this.copiedLoginUrlId = emp.id;
                            setTimeout(() => {
                                if (this.copiedLoginUrlId === emp.id) {
                                    this.copiedLoginUrlId = null;
                                }
                            }, 2000);
                        },
                        copyResetUrl(emp) {
                            navigator.clipboard.writeText(emp.resetUrl);
                            this.copiedResetUrlId = emp.id;
                            setTimeout(() => {
                                if (this.copiedResetUrlId === emp.id) {
                                    this.copiedResetUrlId = null;
                                }
                            }, 2000);
                        }
                    }" x-init="init()" class="max-w-full">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">
                        {{ __('messages.employees_list_title') }}
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

                    <!-- Table with Login & Reset URL columns -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.label_name') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.label_email') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.column_created_at') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.column_login_url') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.column_reset_url') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.column_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="emp in filteredAndSortedEmployees" :key="emp.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="emp.name"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="emp.email"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="new Date(emp.created_at).toLocaleString()"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button @click="copyLoginUrl(emp)" class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)]">
                                                <span x-show="copiedLoginUrlId !== emp.id">{{ __('messages.button_copy_login_url') }}</span>
                                                <span x-show="copiedLoginUrlId === emp.id" class="text-green-600">{{ __('messages.copied_confirmation') }}</span>
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button @click="copyResetUrl(emp)" class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)]">
                                                <span x-show="copiedResetUrlId !== emp.id">{{ __('messages.button_copy_reset_url') }}</span>
                                                <span x-show="copiedResetUrlId === emp.id" class="text-green-600">{{ __('messages.copied_confirmation') }}</span>
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <button @click="deleteEmployee(emp.id)" class="text-red-600 hover:text-red-900">{{ __('messages.action_delete') }}</button>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="employeesData.length > 0 && filteredAndSortedEmployees.length === 0">
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">{{ __('messages.no_employees_match_filter') }}</td>
                                    </tr>
                                </template>
                                <template x-if="employeesData.length === 0">
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">{{ __('messages.no_employees_found') }}</td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Delete Confirmation Modal -->
                    <!-- similar modal markup as client users -->
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
