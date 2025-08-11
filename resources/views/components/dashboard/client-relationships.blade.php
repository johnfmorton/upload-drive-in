@props(['user', 'isAdmin' => false])

<div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-lg font-medium text-gray-900">{{ __('messages.client_relationships_title') }}</h2>
            <p class="mt-1 text-sm text-gray-600">{{ __('messages.client_relationships_description') }}</p>
        </div>
        <div class="flex items-center space-x-3">
            @php
                $clientCount = $user->clientUsers->count();
            @endphp
            
            <div class="text-sm text-gray-500">
                <span class="font-medium">{{ $clientCount }}</span> {{ $clientCount === 1 ? 'client' : 'clients' }}
            </div>
            
            @if($isAdmin)
                <a href="{{ route('admin.users.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                    Manage Clients
                </a>
            @else
                <a href="{{ route('employee.clients.index', ['username' => $user->username]) }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                    Manage Clients
                </a>
            @endif
        </div>
    </div>

    <div class="mt-6">
        @foreach($user->clientUsers as $clientUser)
            <div class="border-b border-gray-200 py-4 last:border-b-0">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-900">{{ $clientUser->name }}</h3>
                        <p class="text-sm text-gray-500">{{ $clientUser->email }}</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        @if($clientUser->pivot->is_primary)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ __('messages.primary_client') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        @if($user->clientUsers->isEmpty())
            <p class="text-sm text-gray-500">{{ __('messages.no_client_relationships') }}</p>
        @endif
    </div>
</div>