<footer class="border-t border-cream-200 bg-cream-50/50">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <p class="text-center text-sm text-warm-500">
            &copy; {{ date('Y') }} Upload Drive-in. All rights reserved. <a href="/privacy-policy" class="text-warm-600 hover:text-warm-800 underline underline-offset-2 decoration-cream-300 hover:decoration-warm-400 transition-colors duration-200">Privacy Policy</a> | <a href="/terms-and-conditions" class="text-warm-600 hover:text-warm-800 underline underline-offset-2 decoration-cream-300 hover:decoration-warm-400 transition-colors duration-200">Terms of Service</a>
            @auth
                @if(auth()->user()->isAdmin() || auth()->user()->isEmployee())
                    <span class="ml-4 text-warm-400">v{{ config('app.version') }}</span>
                @endif
            @endauth
        </p>
    </div>
</footer>
