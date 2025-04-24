<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmployeeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Ensure the user is authenticated, is an employee, and that the
     * {username} URL segment matches their username.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        if (! $user->isEmployee()) {
            abort(403, 'Unauthorized action.');
        }

        $routeUsername = $request->route('username');
        if ($routeUsername !== $user->username) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
