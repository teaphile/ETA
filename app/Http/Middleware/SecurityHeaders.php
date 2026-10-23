<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds conservative security headers to every web response.
 *
 * The Content-Security-Policy is intentionally explicit rather than permissive. Inline
 * scripts are allowed only through a per-request nonce, so a stray injected <script>
 * without the nonce will not run. Google Maps origins are allowed because the centre
 * map is embedded from there. `object-src` is disabled and the page cannot be framed
 * by third parties.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Generated before the response so Blade can put it on inline <script> tags.
        $nonce = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
        $request->attributes->set('csp_nonce', $nonce);

        $response = $next($request);

        if (! $response instanceof Response) {
            return $response;
        }

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(self), payment=()',
        ];

        foreach ($headers as $key => $value) {
            if (! $response->headers->has($key)) {
                $response->headers->set($key, $value);
            }
        }

        // Only apply CSP to HTML documents; JSON/asset responses do not need it.
        $contentType = (string) $response->headers->get('Content-Type', '');

        if (! $response->headers->has('Content-Security-Policy')
            && ($contentType === '' || str_contains($contentType, 'text/html'))) {
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'self'",
                "object-src 'none'",
                "img-src 'self' data: https://maps.gstatic.com https://maps.googleapis.com",
                // Inline scripts must carry the nonce; 'unsafe-inline' is deliberately absent.
                "script-src 'self' 'nonce-".$nonce."' https://maps.googleapis.com https://maps.gstatic.com",
                // Inline style *attributes* (e.g. a computed meter width) are not covered
                // by nonces, so style-src keeps 'unsafe-inline'.
                "style-src 'self' 'unsafe-inline'",
                "font-src 'self' data:",
                "connect-src 'self'",
                "frame-src 'self' https://www.google.com https://maps.google.com",
            ]));
        }

        // HSTS is only meaningful (and safe) over HTTPS.
        if ($request->isSecure() && ! $response->headers->has('Strict-Transport-Security')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
