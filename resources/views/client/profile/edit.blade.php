<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('messages.profile_title') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('client.profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('client.profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('client.profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>

    <script>
        async function sendPasswordReset() {
            const statusDiv = document.getElementById('password-reset-status');
            const userEmail = '{{ auth()->user()->email }}';
            
            // Show loading state
            statusDiv.style.display = 'block';
            statusDiv.className = 'mt-2 text-sm text-blue-600';
            statusDiv.textContent = 'Sending password reset email...';
            
            try {
                const response = await fetch('{{ route("profile.password.reset") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        email: userEmail
                    })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    // Success
                    statusDiv.className = 'mt-2 text-sm text-green-600';
                    statusDiv.textContent = 'Password reset link sent to your email address!';
                } else {
                    // Error
                    statusDiv.className = 'mt-2 text-sm text-red-600';
                    statusDiv.textContent = data.message || 'Failed to send password reset email. Please try again.';
                }
            } catch (error) {
                // Network error
                statusDiv.className = 'mt-2 text-sm text-red-600';
                statusDiv.textContent = 'Network error. Please try again.';
            }
            
            // Hide the message after 5 seconds
            setTimeout(() => {
                statusDiv.style.display = 'none';
            }, 5000);
        }
    </script>
</x-app-layout>