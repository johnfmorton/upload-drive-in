@props(['user', 'isAdmin' => false])

<div class="bg-white overflow-hidden shadow rounded-lg h-full flex flex-col justify-between">
    <div class="p-4 sm:p-5">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-medium text-gray-900">{{ __('messages.client_relationships_title') }}</h2>
                    <p class="text-sm text-gray-600">{{ __('messages.client_relationships_description') }}</p>
                </div>
            </div>
        </div>

        @php
            $clientCount = $user->clientUsers->count();
        @endphp
        
        <div class="flex items-center justify-between">
            <div>
                <div class="text-xl sm:text-2xl font-bold text-gray-900">
                    {{ $clientCount }}
                    <span class="text-base sm:text-lg font-medium text-gray-600">
                        {{ $clientCount === 1 ? 'Client' : 'Clients' }}
                    </span>
                </div>
                <div class="text-sm text-gray-600 mt-1">
                    Total client relationships
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-gray-50 px-4 sm:px-5 py-3">
        <div class="text-sm">
            @if($isAdmin)
                <a href="{{ route('admin.users.index') }}" 
                   class="font-medium text-blue-600 hover:text-blue-500 transition-colors duration-150 flex items-center">
                    Manage all clients
                    <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            @else
                <a href="{{ route('employee.clients.index', ['username' => $user->username]) }}" 
                   class="font-medium text-blue-600 hover:text-blue-500 transition-colors duration-150 flex items-center">
                    Manage my clients
                    <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            @endif
        </div>
    </div>
</div>