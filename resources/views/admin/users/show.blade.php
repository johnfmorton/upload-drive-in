<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Client Details: {{ $client->name }}
            </h2>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                ← Back to Client List
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if (session('success'))
                <div class="mb-6 p-4 bg-green-100 border border-green-200 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif
            
            @if (session('error'))
                <div class="mb-6 p-4 bg-red-100 border border-red-200 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Tab Navigation -->
            <div x-data="{ 
                activeTab: 'info',
                copiedLoginUrl: false,
                
                copyLoginUrl() {
                    navigator.clipboard.writeText('{{ $client->login_url }}');
                    this.copiedLoginUrl = true;
                    setTimeout(() => {
                        this.copiedLoginUrl = false;
                    }, 2000);
                }
            }" class="bg-white shadow sm:rounded-lg">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                        <button @click="activeTab = 'info'" 
                                :class="activeTab === 'info' ? 'border-[var(--brand-color)] text-[var(--brand-color)]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Client Information
                        </button>
                        <button @click="activeTab = 'team'" 
                                :class="activeTab === 'team' ? 'border-[var(--brand-color)] text-[var(--brand-color)]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Team Access
                        </button>
                        <button @click="activeTab = 'uploads'" 
                                :class="activeTab === 'uploads' ? 'border-[var(--brand-color)] text-[var(--brand-color)]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Upload History
                        </button>
                        <button @click="activeTab = 'settings'" 
                                :class="activeTab === 'settings' ? 'border-[var(--brand-color)] text-[var(--brand-color)]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Settings
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    
                    <!-- Client Information Tab -->
                    <div x-show="activeTab === 'info'" x-transition>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                                <dl class="space-y-3">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                                        <dd class="text-sm text-gray-900">{{ $client->name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                                        <dd class="text-sm text-gray-900">{{ $client->email }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                                        <dd class="text-sm text-gray-900">{{ $client->created_at->format('M j, Y g:i A') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                        <dd class="text-sm text-gray-900">{{ $client->updated_at->format('M j, Y g:i A') }}</dd>
                                    </div>
                                </dl>
                            </div>
                            
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Access Information</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Login URL</label>
                                        <div class="mt-1 flex">
                                            <input type="text" readonly value="{{ $client->login_url }}" 
                                                   class="flex-1 rounded-l-md border-gray-300 bg-gray-50 text-sm">
                                            <button @click="copyLoginUrl()" 
                                                    class="px-3 py-2 border border-l-0 border-gray-300 rounded-r-md bg-gray-50 text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-[var(--brand-color)] focus:ring-offset-2 transition-colors duration-200">
                                                <span x-show="!copiedLoginUrl">Copy</span>
                                                <span x-show="copiedLoginUrl" class="text-green-600">Copied!</span>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Two-Factor Authentication</label>
                                        <div class="mt-1">
                                            @if($client->two_factor_enabled)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Enabled
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    Disabled
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Team Access Tab -->
                    <div x-show="activeTab === 'team'" x-transition 
                         x-data="{
                             showPrimaryContactConfirmation: false,
                             newPrimaryContact: '',
                             newPrimaryContactId: null,
                             
                             openPrimaryContactConfirmation(memberName, memberId) {
                                 this.newPrimaryContact = memberName;
                                 this.newPrimaryContactId = memberId;
                                 this.showPrimaryContactConfirmation = true;
                             },
                             
                             confirmPrimaryContactChange() {
                                 // Update the radio button selection
                                 const radioButton = document.getElementById('primary_' + this.newPrimaryContactId);
                                 if (radioButton) {
                                     radioButton.checked = true;
                                 }
                                 this.closePrimaryContactConfirmation();
                             },
                             
                             closePrimaryContactConfirmation() {
                                 this.showPrimaryContactConfirmation = false;
                                 this.newPrimaryContact = '';
                                 this.newPrimaryContactId = null;
                             }
                         }">
                        
                        <!-- {{ __('messages.primary_contact_explanation_title') }} -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">{{ __('messages.primary_contact_explanation_title') }}</h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <p>{{ __('messages.primary_contact_explanation_text') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- {{ __('messages.current_primary_contact') }} -->
                        @if($client->primaryCompanyUser())
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-green-800">{{ __('messages.current_primary_contact') }}</h3>
                                        <p class="text-sm text-green-700">
                                            <strong>{{ $client->primaryCompanyUser()->name }}</strong> ({{ $client->primaryCompanyUser()->email }})
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.users.team.update', $client->id) }}">
                            @csrf
                            
                            <div class="space-y-6">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Assign Team Members</h3>
                                    <p class="text-sm text-gray-600 mb-4">
                                        Select which team members should have access to this client's files and information.
                                    </p>
                                </div>

                                <div class="space-y-4">
                                    @foreach($availableTeamMembers as $member)
                                        <div class="border rounded-lg p-4 {{ $client->companyUsers->where('pivot.is_primary', true)->contains($member->id) ? 'border-green-300 bg-green-50' : 'border-gray-200' }}">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center">
                                                    <input type="checkbox" 
                                                           name="team_members[]" 
                                                           value="{{ $member->id }}"
                                                           id="member_{{ $member->id }}"
                                                           {{ $client->companyUsers->contains($member->id) ? 'checked' : '' }}
                                                           class="h-4 w-4 text-[var(--brand-color)] focus:ring-[var(--brand-color)] border-gray-300 rounded">
                                                    <label for="member_{{ $member->id }}" class="ml-3">
                                                        <div class="text-sm font-medium text-gray-900">{{ $member->name }}</div>
                                                        <div class="text-sm text-gray-500">{{ $member->email }} • {{ ucfirst($member->role->value) }}</div>
                                                    </label>
                                                </div>
                                                
                                                <div class="flex items-center space-x-4">
                                                    @if($client->companyUsers->where('pivot.is_primary', true)->contains($member->id))
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                            </svg>
                                                            {{ __('messages.primary_contact') }}
                                                        </span>
                                                    @endif
                                                    
                                                    <div class="flex items-center">
                                                        <input type="radio" 
                                                               name="primary_contact" 
                                                               value="{{ $member->id }}"
                                                               id="primary_{{ $member->id }}"
                                                               {{ $client->companyUsers->where('pivot.is_primary', true)->contains($member->id) ? 'checked' : '' }}
                                                               class="h-4 w-4 text-[var(--brand-color)] focus:ring-[var(--brand-color)] border-gray-300"
                                                               @change="openPrimaryContactConfirmation('{{ $member->name }}', '{{ $member->id }}')">
                                                        <label for="primary_{{ $member->id }}" class="ml-2 text-sm text-gray-600">
                                                            Make Primary
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if($availableTeamMembers->isEmpty())
                                    <div class="text-center py-8 text-gray-500">
                                        No team members available for assignment.
                                    </div>
                                @endif

                                <div class="flex justify-end">
                                    <button type="submit" 
                                            class="inline-flex items-center px-4 py-2 bg-[var(--brand-color)] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:brightness-90 focus:outline-none focus:ring-2 focus:ring-[var(--brand-color)] focus:ring-offset-2 transition ease-in-out duration-150">
                                        Update Team Access
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Primary Contact Confirmation Modal -->
                        <x-primary-contact-confirmation-modal />
                    </div>

                    <!-- Upload History Tab -->
                    <div x-show="activeTab === 'uploads'" x-transition>
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Upload History</h3>
                                <p class="text-sm text-gray-600 mb-4">
                                    Files uploaded by {{ $client->name }} ({{ $client->email }})
                                </p>
                            </div>

                            @if($uploads->count() > 0)
                                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-300">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File Name</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($uploads as $upload)
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {{ $upload->original_filename }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ format_bytes($upload->file_size) }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        @if($upload->google_drive_file_id)
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                Uploaded to Drive
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                                Processing
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ $upload->created_at->format('M j, Y g:i A') }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="mt-4">
                                    {{ $uploads->links() }}
                                </div>
                            @else
                                <div class="text-center py-8 text-gray-500">
                                    No files uploaded yet.
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Settings Tab -->
                    <div x-show="activeTab === 'settings'" x-transition>
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Client Settings</h3>
                                <p class="text-sm text-gray-600 mb-4">
                                    Manage client-specific configurations and preferences.
                                </p>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Google Drive Integration</h4>
                                @if($client->google_drive_root_folder_id)
                                    <p class="text-sm text-gray-600">
                                        Files are uploaded to a dedicated folder in Google Drive.
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Folder ID: {{ $client->google_drive_root_folder_id }}
                                    </p>
                                @else
                                    <p class="text-sm text-gray-600">
                                        Files are uploaded to the default Google Drive location.
                                    </p>
                                @endif
                            </div>

                            <div class="bg-red-50 p-4 rounded-lg">
                                <h4 class="text-sm font-medium text-red-900 mb-2">Danger Zone</h4>
                                <p class="text-sm text-red-600 mb-3">
                                    Permanently delete this client and all associated data.
                                </p>
                                <form method="POST" action="{{ route('admin.users.destroy', $client->id) }}" 
                                      onsubmit="return confirm('Are you sure you want to delete this client? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="delete_files" value="1">
                                    <button type="submit" 
                                            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Delete Client
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>