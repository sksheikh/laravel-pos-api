<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AuthRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'auth_' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) { // 10 attempts per minute
            return response()->json([
                'success' => false,
                'message' => 'Too many authentication attempts. Please try again later.'
            ], 429);
        }

        RateLimiter::hit($key, 60); // 1 minute decay

        return $next($request);
    }
}
