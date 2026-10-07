<?php

namespace App\Http\Middleware;

use App\Services\AuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reads optional X-Business-Id and X-Branch-Id headers and stores them in
 * Laravel's Context bag for the current request.
 *
 * SECURITY: The supplied IDs are verified against the authenticated user's
 * assigned businesses/branches before being accepted. A non-super-admin
 * supplying a business ID they are not assigned to receives a 403.
 */
class BusinessScopeMiddleware
{
    public function __construct(private readonly AuthorizationService $authService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // ── Business ID ───────────────────────────────────────────────────
        if ($request->hasHeader('X-Business-Id')) {
            $businessId = (int) $request->header('X-Business-Id');

            if ($businessId > 0) {
                // Verify the user actually has access to the supplied business
                if ($user && !$user->isSuperAdmin()) {
                    if (!$this->authService->canAccessBusiness($user, $businessId)) {
                        abort(403, 'You do not have access to business #' . $businessId . '.');
                    }
                }

                Context::add('business_id', $businessId);
            }
        }

        // ── Branch ID ─────────────────────────────────────────────────────
        if ($request->hasHeader('X-Branch-Id')) {
            $branchId = (int) $request->header('X-Branch-Id');

            if ($branchId > 0) {
                if ($user && !$user->isSuperAdmin()) {
                    if (!$this->authService->canAccessBranch($user, $branchId)) {
                        abort(403, 'You do not have access to branch #' . $branchId . '.');
                    }
                }

                Context::add('branch_id', $branchId);
            }
        }

        return $next($request);
    }
}
