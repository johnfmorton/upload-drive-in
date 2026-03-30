<footer class="border-t border-cream-200">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-xs text-warm-400 tracking-wide">
                &copy; {{ date('Y') }} Upload Drive-In
                @auth
                    @if(auth()->user()->isAdmin() || auth()->user()->isEmployee())
                        <span class="ml-2 text-warm-300">v{{ config('app.version') }}</span>
                    @endif
                @endauth
            </p>
            <div class="flex items-center gap-6">
                <a href="/privacy-policy" class="text-xs text-warm-400 hover:text-warm-600 transition-colors duration-200">Privacy</a>
                <a href="/terms-and-conditions" class="text-xs text-warm-400 hover:text-warm-600 transition-colors duration-200">Terms</a>
            </div>
        </div>
    </div>
</footer>
