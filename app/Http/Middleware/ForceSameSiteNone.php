<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceSameSiteNone
{
    public function handle(Request $request, Closure $next): Response
    {
        // Paksa settingan ini sebelum cookie dibuat oleh Sanctum
        config(['session.same_site' => 'none']);
        config(['session.secure' => true]);
        
        return $next($request);
    }
}