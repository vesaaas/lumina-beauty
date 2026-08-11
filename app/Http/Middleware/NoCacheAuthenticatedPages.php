<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NoCacheAuthenticatedPages
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldDisableCache($request)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }

    private function shouldDisableCache(Request $request): bool
    {
        if ($request->user() || $request->session()->has('login_2fa')) {
            return true;
        }

        foreach ([
            'admin',
            'admin/*',
            'email/verify',
            'email/verify/*',
            'login/2fa',
            'login/2fa/*',
            'checkout',
            'checkout/*',
            'cart',
            'cart/*',
            'favorites',
            'favorites/*',
            'orders/*',
        ] as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
