<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;

class ForceSameSiteNone
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Ambil semua cookie yang mau dikirim
        $cookies = $response->headers->getCookies();
        
        foreach ($cookies as $cookie) {
            // Hancurkan cookie lama
            $response->headers->clearCookie(
                $cookie->getName(),
                $cookie->getPath(),
                $cookie->getDomain()
            );

            // Buat cookie baru dengan paksaan SameSite=None
            $response->headers->setCookie(new Cookie(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain(),
                true, // Secure (Wajib true untuk cross-site)
                $cookie->isHttpOnly(),
                $cookie->isRaw(),
                'none' // Paksa SameSite None
            ));
        }

        return $response;
    }
}