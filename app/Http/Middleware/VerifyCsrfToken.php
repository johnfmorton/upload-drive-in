<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'setup/ajax/refresh-csrf-token',
        'setup/admin',
        'setup/database',
        'setup/storage',
        'setup/complete',
        'setup/ajax/*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        \Log::info('CSRF Middleware Handle called', [
            'path' => $request->path(),
            'method' => $request->method(),
            'is_setup_route' => str_starts_with($request->path(), 'setup/'),
            'has_token' => $request->has('_token'),
            'token_value' => $request->input('_token') ? 'present' : 'missing'
        ]);

        // Skip CSRF verification for setup routes entirely
        if (str_starts_with($request->path(), 'setup/')) {
            \Log::info('CSRF completely bypassed for setup route');
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    /**
     * Determine if the session and input CSRF tokens match.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function tokensMatch($request)
    {
        \Log::info('CSRF tokensMatch called', [
            'path' => $request->path(),
            'method' => $request->method(),
            'is_setup_route' => str_starts_with($request->path(), 'setup/')
        ]);

        // Always allow setup routes to bypass CSRF verification
        if (str_starts_with($request->path(), 'setup/')) {
            \Log::info('CSRF bypassed for setup route');
            return true;
        }

        return parent::tokensMatch($request);
    }
}
