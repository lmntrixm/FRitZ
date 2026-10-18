<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** «فقط مدیر کل»: settings, users and file updates are closed to «ویرایشگر محتوا». */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        abort_unless($request->user()?->role === $role, 403, 'این بخش فقط برای مدیر کل است.');

        return $next($request);
    }
}
