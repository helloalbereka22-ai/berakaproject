<?php

namespace App\Http\Middleware;

use App\Services\AuthorizationService;
use App\Services\BusinessContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reads the active business context from:
 *   1. Session  → 'active_business_id'
 *   2. Header   → X-Business-ID
 *
 * SECURITY: The business ID is verified against the authenticated user's
 * assigned businesses before being stored in BusinessContextService.
 * Supplying an unauthorized business ID returns 403.
 *
 * Used on web (admin) routes. API routes use BusinessScopeMiddleware instead.
 */
class EnforceBusinessContext
{
    public function __construct(
        private readonly BusinessContextService $businessContext,
        private readonly AuthorizationService   $authService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $businessId = session('active_business_id')
            ?? $request->header('X-Business-ID');

        if ($businessId) {
            $businessId = (int) $businessId;
            $user       = $request->user();

            if ($user && !$user->isSuperAdmin()) {
                // Enforce that the user actually has access to this business
                if (!$this->authService->canAccessBusiness($user, $businessId)) {
                    // Clear the invalid session value so it doesn't persist
                    session()->forget('active_business_id');
                    abort(403, 'You do not have access to this business.');
                }
            }

            $this->businessContext->setBusinessId($businessId);
        } else {
            // Business context is optional on non-business routes
            // Only block access if explicitly on a business-specific route
            if ($request->is('business/*')) {
                return redirect()->route('admin.dashboard')
                    ->with('error', 'Please select a business to manage.');
            }
        }

        return $next($request);
    }
}
