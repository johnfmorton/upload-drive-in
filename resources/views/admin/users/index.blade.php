<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Client User Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
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
                            actions: true
                        }).as('adminUserColumns'),

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
                                        // Dates are usually comparable as strings (ISO format)
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

                        // Delete User Function (similar to file delete)
                        deleteUser(userId) {
                             if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                                let form = document.createElement('form');
                                form.method = 'POST';
                                // Construct the action URL using the route name and user ID
                                form.action = '{{ route("admin.users.destroy", "") }}/' + userId;
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

                                document.body.appendChild(form);
                                form.submit();
                            }
                        }

                    }" class="max-w-full">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">
                        Client Users
                    </h2>

                     @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded">
                            {{ session('success') }}
                        </div>
                     @endif

                    <!-- Column Visibility Controls -->
                    <div class="mb-4 p-4 border rounded bg-gray-50">
                        <h3 class="text-md font-medium text-gray-700 mb-2">Show/Hide Columns:</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-sm">
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.name" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>Name</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.email" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>Email</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.createdAt" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>Created At</span>
                            </label>
                             <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.actions" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>Actions</span>
                            </label>
                        </div>
                    </div>

                    <!-- Filter Input -->
                    <div class="mb-4">
                        <label for="userFilter" class="sr-only">Filter users</label>
                        <input type="text" id="userFilter" x-model.debounce.300ms="filterQuery" placeholder="Filter by name or email..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm">
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <template x-if="columns.name">
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" @click="sortBy('name')">
                                            Name <span x-show="sortColumn === 'name'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                        </th>
                                    </template>
                                    <template x-if="columns.email">
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" @click="sortBy('email')">
                                            Email <span x-show="sortColumn === 'email'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                        </th>
                                    </template>
                                    <template x-if="columns.createdAt">
                                         <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" @click="sortBy('created_at')">
                                            Created At <span x-show="sortColumn === 'created_at'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                        </th>
                                    </template>
                                    <template x-if="columns.actions"><th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th></template>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="client in filteredAndSortedClients" :key="client.id">
                                    <tr>
                                        <template x-if="columns.name"><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="client.name"></td></template>
                                        <template x-if="columns.email"><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="client.email"></td></template>
                                         <template x-if="columns.createdAt"><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(client.created_at)"></td></template>
                                        <template x-if="columns.actions">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                {{-- Add Edit Link later when edit functionality is built --}}
                                                {{-- <a :href="`/admin/users/${client.id}/edit`" class="text-indigo-600 hover:text-indigo-900">Edit</a> --}}
                                                <button @click="deleteUser(client.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                            </td>
                                        </template>
                                    </tr>
                                </template>
                                <!-- Message if no clients match filter -->
                                <template x-if="filteredAndSortedClients.length === 0">
                                    <tr>
                                        <td :colspan="Object.values(columns).filter(v => v).length" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            No client users match your filter criteria.
                                        </td>
                                    </tr>
                                </template>
                                <!-- Message if no clients at all -->
                                @if($clients->isEmpty() && empty(request('page')))
                                    <template x-if="clientsData.length === 0 && filterQuery.trim() === ''">
                                        <tr>
                                            <td :colspan="Object.values(columns).filter(v => v).length" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                                No client users found.
                                            </td>
                                        </tr>
                                    </template>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $clients->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
