<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        // 1. Wajib untuk Railway (Karena di balik Proxy HTTPS)
        $middleware->trustProxies(at: '*');

        // 2. Wajib untuk Sanctum SPA Auth
        $middleware->statefulApi();

        // 3. Bebaskan endpoint login dari cek CSRF
        $middleware->validateCsrfTokens(except: [
            'api/login',
             'api/logout',
        ]);

        // 4. Daftarkan Middleware pamungkas kita di urutan paling akhir
        // (Ini akan menimpa header Set-Cookie tepat sebelum dikirim ke browser)
        $middleware->append(\App\Http\Middleware\ForceSameSiteNone::class);

        // 5. Alias untuk Role
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();