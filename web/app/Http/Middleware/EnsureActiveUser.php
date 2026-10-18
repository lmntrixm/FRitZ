<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** A user disabled by a «مدیر کل» loses access at once, not at the next sign-in. */
class EnsureActiveUser
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('admin.login')->withErrors(['email' => 'این حساب غیرفعال شده است.']);
        }

        return $next($request);
    }
}
