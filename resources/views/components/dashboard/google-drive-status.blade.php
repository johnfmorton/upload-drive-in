@props(['user', 'isAdmin' => false])

<div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-medium text-gray-900">Google Drive</h2>
            <p class="mt-1 text-sm text-gray-500">
                @if($isAdmin)
                    <a href="https://console.cloud.google.com/apis/credentials" target="_blank"
                       class="text-blue-500 hover:text-blue-700">
                        {{ __('messages.configure_google_drive_storage_link_description') }}
                    </a>
                @else
                    <a href="{{ route('employee.cloud-storage.index', ['username' => $user->username]) }}" 
                       class="text-blue-500 hover:text-blue-700">
                        Configure Google Drive storage settings and connection details.
                    </a>
                @endif
            </p>
        </div>
        <div class="flex items-center space-x-4">
            @if($user->hasGoogleDriveConnected())
                <span class="px-3 py-1 text-sm text-green-800 bg-green-100 rounded-full">{{ __('messages.connected') }}</span>
                @if($isAdmin)
                    <form action="{{ route('admin.cloud-storage.google-drive.disconnect') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-red-700 bg-red-100 rounded-md hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            {{ __('messages.disconnect') }}
                        </button>
                    </form>
                @else
                    <form action="{{ route('employee.google-drive.disconnect', ['username' => $user->username]) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-red-700 bg-red-100 rounded-md hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            {{ __('messages.disconnect') }}
                        </button>
                    </form>
                @endif
            @else
                <span class="px-3 py-1 text-sm text-gray-800 bg-gray-100 rounded-full">{{ __('messages.not_connected') }}</span>
                @if($isAdmin)
                    <form action="{{ route('admin.cloud-storage.google-drive.connect') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            {{ __('messages.connect') }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('employee.google-drive.connect', ['username' => $user->username]) }}" 
                       class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        {{ __('messages.connect') }}
                    </a>
                @endif
            @endif
        </div>
    </div>
</div>