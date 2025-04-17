<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\UserRole;

class AdminMiddleware
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
        \Log::info('AdminMiddleware: Executing middleware for request.', ['url' => $request->url()]);

        if (!auth()->check()) {
            \Log::warning('AdminMiddleware: User not authenticated.');
            return redirect('login');
        }

        $user_role = auth()->user()->role;
        \Log::info('AdminMiddleware: User role detected.', ['role' => $user_role]);

        if ($user_role !== UserRole::ADMIN) {
            \Log::warning('AdminMiddleware: User is not an admin.', ['role' => $user_role]);
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
