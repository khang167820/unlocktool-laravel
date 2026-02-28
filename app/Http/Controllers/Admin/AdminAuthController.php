<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AdminAuthController extends Controller
{
    /**
     * Max login attempts before lockout
     */
    const MAX_ATTEMPTS = 5;
    const LOCKOUT_MINUTES = 5;

    /**
     * Show admin login form
     */
    public function showLoginForm()
    {
        if (session('admin_logged_in') === true) {
            return redirect()->route('admin.dashboard');
        }
        
        return view('admin.login');
    }
    
    /**
     * Handle admin login with security hardening:
     * - Rate limiting (5 attempts / 5 min lockout)
     * - No username enumeration (generic error)
     * - Bcrypt only (no plain text fallback)
     * - Session regeneration
     * - Login attempt logging
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);
        
        // Rate limiting by IP
        $rateLimitKey = 'admin_login:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $minutes = ceil($seconds / 60);
            
            Log::warning("Admin login BLOCKED (rate limit)", [
                'ip' => $request->ip(),
                'username' => $request->username,
                'lockout_seconds' => $seconds,
            ]);
            
            return back()->withErrors([
                'username' => "Too many attempts. Please wait {$minutes} minutes.",
            ])->withInput();
        }
        
        // Query admin table
        $admin = DB::table('admin')
            ->where('username', $request->username)
            ->first();
        
        // Verify password - bcrypt ONLY (no plain text fallback)
        $passwordValid = false;
        if ($admin && password_verify($request->password, $admin->password)) {
            $passwordValid = true;
        }
        
        if (!$passwordValid) {
            // Record failed attempt
            RateLimiter::hit($rateLimitKey, self::LOCKOUT_MINUTES * 60);
            
            Log::warning("Admin login FAILED", [
                'ip' => $request->ip(),
                'username' => $request->username,
                'attempts' => RateLimiter::attempts($rateLimitKey),
            ]);
            
            // Generic error - don't reveal if username exists or not
            return back()->withErrors([
                'username' => 'Invalid username or password.',
            ])->withInput();
        }
        
        // Login successful
        RateLimiter::clear($rateLimitKey);
        
        // Regenerate session to prevent session fixation
        $request->session()->regenerate();
        
        session([
            'admin_logged_in' => true,
            'admin_id' => $admin->id,
            'admin_username' => $admin->username,
            'admin_login_ip' => $request->ip(),
            'admin_login_at' => now()->toDateTimeString(),
        ]);
        
        Log::info("Admin login SUCCESS", [
            'ip' => $request->ip(),
            'username' => $admin->username,
        ]);
        
        return redirect()->route('admin.dashboard')->with('success', 'Login successful!');
    }
    
    /**
     * Handle admin logout
     */
    public function logout(Request $request)
    {
        Log::info("Admin logout", [
            'ip' => $request->ip(),
            'username' => session('admin_username'),
        ]);
        
        session()->forget(['admin_logged_in', 'admin_id', 'admin_username', 'admin_login_ip', 'admin_login_at']);
        $request->session()->regenerate();
        
        return redirect()->route('admin.login')->with('success', 'Logged out!');
    }
}
