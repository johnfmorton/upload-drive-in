<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->isAdmin() && $user->two_factor_enabled) {
            $verifiedAt = session('two_factor_verified');
            $timeout = config('admin-2fa.code_timeout', 300);
            $isVerified = $verifiedAt && (now()->timestamp - $verifiedAt) < $timeout;

            if (!$isVerified) {
                session()->forget('two_factor_verified');
                return redirect()->route('admin.2fa.verify');
            }
        }

        return $next($request);
    }
}
