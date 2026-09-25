<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        if (app()->isProduction() && $request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        // 'unsafe-inline' is required until inline <script> blocks and onclick handlers move to Vite modules.
        $script = ["'self'", "'unsafe-inline'", 'https://cdn.jsdelivr.net'];
        $style = ["'self'", "'unsafe-inline'", 'https://fonts.bunny.net'];
        $connect = ["'self'"];

        $hotFile = public_path('hot');
        if (! app()->isProduction() && is_file($hotFile)) {
            $vite = rtrim(trim(file_get_contents($hotFile)), '/');
            $script[] = $vite;
            $style[] = $vite;
            $connect[] = $vite;
            $connect[] = preg_replace('#^http#', 'ws', $vite);
        }

        return implode('; ', [
            "default-src 'self'",
            'script-src '.implode(' ', $script),
            'style-src '.implode(' ', $style),
            "font-src 'self' https://fonts.bunny.net",
            "img-src 'self' data: blob:",
            'connect-src '.implode(' ', $connect),
            "frame-ancestors 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
    }
}
