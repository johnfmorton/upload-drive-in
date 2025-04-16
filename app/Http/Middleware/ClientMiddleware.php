<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClientMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->isAdmin()) {
            if ($request->user() && !$request->user()->hasCompletedTwoFactorAuth()) {
                return redirect()->route('two-factor.login');
            }
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
