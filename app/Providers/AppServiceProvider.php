<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <--- Tambahkan baris ini

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
        // Tambahkan kode ini di dalam fungsi boot()
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        config([
        'session.same_site' => 'none',
        'session.secure' => true,
        'session.domain' => null,
    ]);
    }
}