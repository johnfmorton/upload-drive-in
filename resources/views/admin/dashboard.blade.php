<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Google Drive Connection Section -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h2 class="text-lg font-medium text-gray-900">
                        Google Drive Connection
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        Connect to Google Drive to enable automatic file uploads.
                    </p>

                    <div class="mt-6">
                        @if (Storage::exists('google-credentials.json'))
                            <div class="flex items-center gap-4">
                                <p class="text-sm text-green-600">Google Drive is connected</p>
                                <form action="{{ route('google-drive.disconnect') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Disconnect Google Drive
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="flex items-center gap-4">
                                <p class="text-sm text-red-600">Google Drive is not connected</p>
                                <a href="{{ route('google-drive.connect') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Connect Google Drive
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Files Table Section -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div x-data="{
                        columns: $persist({
                            fileName: true,
                            user: true,
                            size: true,
                            status: true,
                            message: true,
                            uploadedAt: true,
                            actions: true
                        }).as('adminFileColumns')
                    }" class="max-w-full">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">
                        Uploaded Files
                    </h2>

                    <!-- Column Visibility Controls -->
                    <div class="mb-4 p-4 border rounded bg-gray-50">
                        <h3 class="text-md font-medium text-gray-700 mb-2">Show/Hide Columns:</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-sm">
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.fileName" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>File Name</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.user" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>User</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.size" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>Size</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.status" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>Status</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.message" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>Message</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.uploadedAt" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>Uploaded At</span>
                            </label>
                             <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.actions" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>Actions</span>
                            </label>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <template x-if="columns.fileName"><th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File Name</th></template>
                                    <template x-if="columns.user"><th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th></template>
                                    <template x-if="columns.size"><th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th></template>
                                    <template x-if="columns.status"><th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th></template>
                                    <template x-if="columns.message"><th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th></template>
                                    <template x-if="columns.uploadedAt"><th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded At</th></template>
                                    <template x-if="columns.actions"><th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th></template>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($files as $file)
                                    <tr>
                                        <template x-if="columns.fileName"><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $file->original_filename }}</td></template>
                                        <template x-if="columns.user"><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $file->email }}</td></template>
                                        <template x-if="columns.size"><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($file->file_size / 1024, 2) }} KB</td></template>
                                        <template x-if="columns.status">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if ($file->google_drive_file_id)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        Uploaded to Drive
                                                    </span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        Pending
                                                    </span>
                                                @endif
                                            </td>
                                        </template>
                                        <template x-if="columns.message"><td class="px-6 py-4 whitespace-normal text-sm text-gray-500">{{ $file->message }}</td></template>
                                        <template x-if="columns.uploadedAt"><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $file->created_at->format('Y-m-d H:i:s') }}</td></template>
                                        <template x-if="columns.actions">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                @if ($file->google_drive_file_id)
                                                    <a href="https://drive.google.com/file/d/{{ $file->google_drive_file_id }}/view" target="_blank" class="text-indigo-600 hover:text-indigo-900">View in Drive</a>
                                                @endif
                                                <form action="{{ route('admin.files.destroy', $file) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this file?')">Delete</button>
                                                </form>
                                            </td>
                                        </template>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $files->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
