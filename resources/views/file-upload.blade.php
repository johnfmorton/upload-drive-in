<x-app-layout>
    <div class="min-h-screen bg-gray-100 py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">File Upload</h1>
                            <p class="text-gray-600">Upload your files securely</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600">Logged in as: {{ auth()->user()->email }}</p>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-900">
                                    Not you? Sign out
                                </button>
                            </form>
                        </div>
                    </div>

                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    {{-- Replace the original form with the Uppy Dashboard container --}}
                    <div id="uppy-dashboard" data-upload-url="{{ route('chunk.upload') }}"></div>
                    <div class="mt-4">
                        <label for="message" class="block text-sm font-medium text-gray-700">Message (optional)</label>
                        <textarea id="message" name="message" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Add any context or instructions for your files..."></textarea>
                    </div>
                    {{-- Uppy handles the upload button, so we remove the original form submit button --}}

                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    {{-- Uppy initialization is now handled in resources/js/app.js --}}
    @endpush
</x-app-layout>
