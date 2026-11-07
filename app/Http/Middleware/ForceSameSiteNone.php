<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceSameSiteNone
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Intip semua cookie yang mau dikirim ke browser
        $headers = $response->headers->all();
        
        if (isset($headers['set-cookie'])) {
            foreach ($headers['set-cookie'] as $key => $cookieString) {
                // Ganti paksa tulisan samesite=lax menjadi samesite=none
                $headers['set-cookie'][$key] = preg_replace('/samesite=lax/i', 'samesite=none', $cookieString);
            }
            // Simpan kembali ke response
            $response->headers->replace($headers);
        }

        return $response;
    }
}