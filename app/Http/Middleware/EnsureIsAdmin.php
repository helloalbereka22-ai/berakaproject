<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards admin panel routes.
 *
 * Accepts an optional parameter to restrict to Super Admin only:
 *   Route::middleware('admin:super')   → isSuperAdmin() required
 *   Route::middleware('admin')         → isAdmin() accepted (Admin or Super Admin)
 *
 * SECURITY FIX (H1): System-level routes (settings, audit logs, translations,
 * user management) are enforced via permission middleware; this middleware
 * ensures only staff with at minimum an Admin role can enter the panel.
 * The 'admin:super' variant is used for routes that must be Super Admin only.
 */
class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next, string $level = 'any'): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $this->deny($request);
        }

        $allowed = match ($level) {
            'super' => $user->isSuperAdmin(),
            default => $user->isAdmin(),   // isAdmin() = Admin or Super Admin
        };

        if (!$allowed) {
            return $this->deny($request);
        }

        return $next($request);
    }

    private function deny(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'Access Denied.'], 403);
        }
        return redirect()->route('login');
    }
}
