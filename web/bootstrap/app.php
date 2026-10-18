<?php

use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // the deploy hook is called by the CI workflow, which has no session or CSRF token
        $middleware->validateCsrfTokens(except: ['_deploy']);
        $middleware->alias([
            'role' => EnsureRole::class,
            'active' => EnsureActiveUser::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.overview'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

// DirectAdmin layout: this app in domains/<site>/fritz-app, the web root beside it in public_html.
// Lets artisan (cron, deploy hook) resolve public_path() the same way the web request does.
if (is_dir($web = dirname(__DIR__).'/../public_html')) {
    $app->usePublicPath(realpath($web));
}

return $app;
