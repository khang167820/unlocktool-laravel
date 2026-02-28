<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     * Check if admin is logged in via Laravel session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check Laravel session for admin login
        if (session('admin_logged_in') !== true) {
            return redirect()->route('admin.login')->with('error', 'Vui lòng đăng nhập admin.');
        }
        
        return $next($request);
    }
}
