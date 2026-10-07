<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevents browser caching of Livewire-related content
 * to avoid stale component state errors
 */
class NoCacheLivewire
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Always prevent caching for admin pages to avoid Livewire state issues
        if ($request->is('admin/*') || $request->is('livewire*')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, proxy-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
