<footer class="bg-white border-t border-gray-100">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <p class="text-center text-sm text-gray-500">
            &copy; {{ date('Y') }} Upload Drive-in. All rights reserved. <a href="/privacy-policy" class="text-blue-500 hover:text-blue-700">Privacy Policy</a> | <a href="/terms-and-conditions" class="text-blue-500 hover:text-blue-700">Terms of Service</a>
            @auth
                @if(auth()->user()->isAdmin() || auth()->user()->isEmployee())
                    <span class="ml-4 text-gray-400">v{{ config('app.version') }}</span>
                @endif
            @endauth
        </p>
    </div>
</footer>
