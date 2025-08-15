<x-setup-layout 
    :title="'Welcome'" 
    :current-step="1" 
    :total-steps="5" 
    :steps="['Welcome', 'Database', 'Admin User', 'Cloud Storage', 'Complete']">

    <div class="p-8" data-setup-step="welcome">
        <!-- Progress Indicator -->
        <x-setup-progress-indicator 
            :current-step="$currentStep ?? 'welcome'" 
            :progress="$progress ?? 20" />

        <!-- Step Completion Feedback -->
        @if(session('step_completed'))
            @php $stepData = session('step_completed'); @endphp
            <x-setup-step-completion 
                :step="$stepData['step']"
                :title="$stepData['details']['title']"
                :message="$stepData['details']['message']"
                :details="$stepData['details']['details']"
                :next-step="$stepData['next_step']"
                :progress="$stepData['progress']"
                :auto-advance="true"
                :auto-advance-delay="3000" />
        @endif

        <!-- Success Message -->
        @if(session('success'))
            <x-setup-success-display 
                :message="session('success')" 
                :show-progress="true"
                :progress="$progress ?? 20" />
        @endif

        <!-- Error Display -->
        @if(session('setup_error'))
            <x-setup-error-display 
                :error="session('setup_error')" 
                title="System Check Error" />
        @endif

        <!-- Welcome Header -->
        <div class="text-center mb-8">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Welcome to {{ config('app.name', 'Upload Drive-In') }}!</h2>
            <p class="text-gray-600 max-w-md mx-auto">
                Your application has been successfully installed. Let's get it configured so you can start receiving files from your clients.
            </p>
        </div>

        <!-- System Requirements Check -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">System Requirements</h3>
            <div class="space-y-3">
                @foreach($systemChecks as $check)
                    <div class="flex items-center justify-between p-3 rounded-lg border 
                        {{ $check['status'] === 'pass' ? 'bg-green-50 border-green-200' : '' }}
                        {{ $check['status'] === 'warning' ? 'bg-yellow-50 border-yellow-200' : '' }}
                        {{ $check['status'] === 'fail' ? 'bg-red-50 border-red-200' : '' }}">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 mr-3">
                                @if($check['status'] === 'pass')
                                    <svg class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                @elseif($check['status'] === 'warning')
                                    <svg class="h-5 w-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                @else
                                    <svg class="h-5 w-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-medium 
                                    {{ $check['status'] === 'pass' ? 'text-green-800' : '' }}
                                    {{ $check['status'] === 'warning' ? 'text-yellow-800' : '' }}
                                    {{ $check['status'] === 'fail' ? 'text-red-800' : '' }}">
                                    {{ $check['name'] }}
                                </p>
                                @if(isset($check['description']))
                                    <p class="text-xs 
                                        {{ $check['status'] === 'pass' ? 'text-green-600' : '' }}
                                        {{ $check['status'] === 'warning' ? 'text-yellow-600' : '' }}
                                        {{ $check['status'] === 'fail' ? 'text-red-600' : '' }}">
                                        {{ $check['description'] }}
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="text-sm font-medium 
                            {{ $check['status'] === 'pass' ? 'text-green-800' : '' }}
                            {{ $check['status'] === 'warning' ? 'text-yellow-800' : '' }}
                            {{ $check['status'] === 'fail' ? 'text-red-800' : '' }}">
                            {{ $check['value'] ?? ucfirst($check['status']) }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Critical Issues Warning -->
        @if($hasCriticalIssues)
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Critical Issues Detected</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>Please resolve the failed system requirements before continuing with the setup.</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Help Panel -->
        <div class="mb-8">
            <x-setup-help-panel step="welcome" />
        </div>

        <!-- Navigation -->
        <div class="flex justify-between items-center pt-6 border-t border-gray-200">
            <div class="text-sm text-gray-500">
                Ready to get started?
            </div>
            <div class="flex space-x-3">
                @if($hasCriticalIssues)
                    <button type="button" 
                            onclick="window.location.reload()" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Recheck System
                    </button>
                @else
                    <a href="{{ route('setup.database') }}" 
                       class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Continue Setup
                        <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                @endif
            </div>
        </div>
    </div>

</x-setup-layout>