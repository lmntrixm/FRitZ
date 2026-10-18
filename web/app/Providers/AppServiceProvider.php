<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // utf8mb4 needs 4 bytes a character: 191 keeps every indexed string under the
        // 1000-byte key limit of MyISAM / old MySQL that shared hosts still run
        Schema::defaultStringLength(191);
    }
}
