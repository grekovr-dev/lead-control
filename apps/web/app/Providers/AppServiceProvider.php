<?php

namespace App\Providers;

use App\Support\BackofficePermissions;
use Illuminate\Support\Facades\Blade;
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
        Blade::if('backofficeCan', static function (string $permission): bool {
            return BackofficePermissions::can($permission);
        });
    }
}
