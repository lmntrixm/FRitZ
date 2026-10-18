<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/** Sign-in (A-Login). No public sign-up: users join by invitation from a «مدیر کل». */
class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const LOCK_SECONDS = 900; // 15 minutes, as in the design

    public function show(Request $request)
    {
        if (User::query()->doesntExist()) {
            return redirect()->route('admin.setup');
        }

        return view('admin.login', [
            'products' => Product::count(),
            'lockedFor' => RateLimiter::tooManyAttempts($this->key($request, (string) old('email')), self::MAX_ATTEMPTS)
                ? RateLimiter::availableIn($this->key($request, (string) old('email'))) : 0,
        ]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string', 'max:190'],
        ], [], ['email' => 'ایمیل', 'password' => 'رمز عبور']);

        $key = $this->key($request, $data['email']);
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return back()->withInput($request->only('email'));
        }

        $ok = Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'status' => 'active'], $request->boolean('remember'));
        if (! $ok) {
            RateLimiter::hit($key, self::LOCK_SECONDS);
            $left = self::MAX_ATTEMPTS - RateLimiter::attempts($key);

            return back()->withInput($request->only('email', 'remember'))->withErrors(['password' => 'رمز عبور اشتباه است.'])
                ->with('left', max(0, $left));
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('admin.overview'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function key(Request $request, string $email): string
    {
        return 'admin-login:'.Str::lower(trim($email)).'|'.$request->ip();
    }
}
