<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('File Upload') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Google Drive Connection Status -->
                    <div class="mb-6 p-4 border rounded-lg">
                        <h3 class="text-lg font-semibold mb-2">Google Drive Connection</h3>

                        @if ($user->hasGoogleDriveConnected())
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center text-green-600">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    Connected to Google Drive
                                </div>
                                <form method="POST"
                                    action="{{ route('employee.google-drive.disconnect', ['username' => $user->username]) }}"
                                    class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                                        Disconnect
                                    </button>
                                </form>
                            </div>

                            <!-- Google Drive Folder Selection -->
                            <div class="border-t pt-4">
                                <h4 class="text-md font-medium mb-3">Upload Destination</h4>
                                <p class="text-sm text-gray-600 mb-3">
                                    Choose where files will be stored in your Google Drive. If no folder is selected, uploads will go to your Google Drive root directory by default.
                                </p>
                                @include('employee.google-drive.google-drive-root-folder')
                            </div>
                        @else
                            <div class="flex items-center justify-between">
                                <div class="flex items-center text-yellow-600">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    Google Drive not connected
                                </div>
                                <a href="{{ route('employee.google-drive.connect', ['username' => $user->username]) }}"
                                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    Connect Google Drive
                                </a>
                            </div>
                            <p class="text-sm text-gray-600 mt-2">
                                Connect your Google Drive to automatically save uploaded files to the cloud.
                            </p>
                        @endif
                    </div>

                    <!-- File Upload Form -->
                    <div class="border rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">Upload Files</h3>

                        <form method="POST" action="{{ route('employee.upload', ['username' => $user->username]) }}"
                            enctype="multipart/form-data">
                            @csrf

                            <div class="mb-4">
                                <label for="files" class="block text-sm font-medium text-gray-700 mb-2">
                                    Select Files
                                </label>
                                <input id="files" name="files[]" type="file" multiple
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                    required>
                                <p class="mt-1 text-sm text-gray-500">
                                    Maximum file size: 10MB per file. Multiple files allowed.
                                </p>
                            </div>

                            <div class="mb-4">
                                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                                    Message (Optional)
                                </label>
                                <textarea id="message" name="message" rows="3"
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500/50"
                                    placeholder="Add a note about these files...">{{ old('message') }}</textarea>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Upload Files
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Recent Uploads -->
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold mb-4">Recent Uploads</h3>
                        <div class="border rounded-lg">
                            @php
                                $recentUploads = \App\Models\FileUpload::where('uploaded_by_user_id', $user->id)
                                    ->orderBy('created_at', 'desc')
                                    ->limit(10)
                                    ->get();
                            @endphp

                            @if ($recentUploads->count() > 0)
                                <div class="divide-y">
                                    @foreach ($recentUploads as $upload)
                                        <div class="p-4 flex items-center justify-between">
                                            <div>
                                                <div class="font-medium">{{ $upload->original_filename }}</div>
                                                <div class="text-sm text-gray-500">
                                                    {{ $upload->created_at->format('M j, Y g:i A') }} •
                                                    {{ format_bytes($upload->file_size) }}
                                                </div>
                                                @if ($upload->message)
                                                    <div class="text-sm text-gray-600 mt-1">{{ $upload->message }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="text-sm">
                                                @if ($upload->google_drive_file_id)
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Uploaded to Drive
                                                    </span>
                                                @else
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        Processing
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-8 text-center text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                        viewBox="0 0 48 48">
                                        <path
                                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No uploads yet</h3>
                                    <p class="mt-1 text-sm text-gray-500">Get started by uploading your first file.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
