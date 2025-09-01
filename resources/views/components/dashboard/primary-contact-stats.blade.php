@props(['user', 'isAdmin' => false])

@php
    $primaryContactCount = $user->primaryContactClients()->count();
@endphp

<div class="bg-white overflow-hidden shadow rounded-lg h-full flex flex-col justify-between">
    <div class="p-4 sm:p-5">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            </div>
            <div class="ml-4 sm:ml-5 w-0 flex-1">
                <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">{{ __('messages.primary_contact_for') }}</dt>
                    <dd class="text-xl sm:text-2xl font-bold text-gray-900 mt-1">
                        {{ $primaryContactCount }} 
                        <span class="text-base sm:text-lg font-medium text-gray-600">
                            {{ $primaryContactCount === 1 ? __('messages.primary_contact_clients_singular') : __('messages.primary_contact_clients_plural') }}
                        </span>
                    </dd>
                </dl>
            </div>
        </div>
        
        @if($primaryContactCount > 0)
            <div class="mt-4">
                <div class="text-sm text-gray-600">
                    {{ __('messages.primary_contact_responsibility_description') }}
                </div>
            </div>
        @endif
    </div>
    
    <div class="bg-gray-50 px-4 sm:px-5 py-3">
        <div class="text-sm">
            @if($primaryContactCount > 0)
                @if($isAdmin)
                    <a href="{{ route('admin.users.index') }}?filter=primary_contact" 
                       class="font-medium text-blue-600 hover:text-blue-500 transition-colors duration-150 flex items-center">
                        {{ __('messages.view_primary_contact_clients') }}
                        <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                @else
                    @if($user->username)
                        <a href="{{ route('employee.clients.index', ['username' => $user->username]) }}?filter=primary_contact" 
                           class="font-medium text-blue-600 hover:text-blue-500 transition-colors duration-150 flex items-center">
                            {{ __('messages.view_primary_contact_clients') }}
                            <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    @else
                        <span class="text-gray-500">View clients</span>
                    @endif
                @endif
            @else
                <span class="text-gray-500">{{ __('messages.no_primary_contact_assignments') }}</span>
            @endif
        </div>
    </div>
</div>