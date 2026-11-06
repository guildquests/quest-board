<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttpsScheme
{
    /**
     * Treat every request as https in production when APP_URL is https.
     *
     * A CDN that talks plain HTTP to the server (CloudFront to Traefik on Dokploy)
     * makes the proxy send X-Forwarded-Proto: http. URL::forceScheme only covers
     * generated links; the saved "intended" login link comes from the request
     * itself, so the request must be https too. Runs before TrustProxies.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            $request->headers->set('X-Forwarded-Proto', 'https');
        }

        return $next($request);
    }
}
