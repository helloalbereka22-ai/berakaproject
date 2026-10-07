<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

/**
 * Sets the application locale from the session or cookie.
 *
 * SECURITY FIX (H6): Validates the locale against the allowed list before
 * calling App::setLocale(). Prevents locale injection via session tampering.
 * 
 * Checks both session (preferred) and cookie (fallback) for redundancy.
 */
class SetLocale
{
    /** Supported locale codes — must match lang/ directory and Language table */
    private const ALLOWED_LOCALES = ['en', 'ar', 'am', 'om'];

    public function handle(Request $request, Closure $next)
    {
        $locale = null;
        
        // Try session first
        if (Session::has('locale')) {
            $locale = Session::get('locale');
        }
        // Fallback to cookie if no session
        elseif ($request->hasCookie('locale')) {
            $locale = $request->cookie('locale');
        }
        
        // Validate and set locale
        if ($locale && in_array($locale, self::ALLOWED_LOCALES, true)) {
            App::setLocale($locale);
        } else {
            // Invalid locale or not found — clear both and fall back to default
            if ($locale) {
                Session::forget('locale');
                Session::forget('is_rtl');
            }
        }

        return $next($request);
    }
}
