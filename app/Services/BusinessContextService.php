<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Facades\Auth;

/**
 * Holds the resolved business context for the current request lifecycle.
 *
 * SECURITY: setBusinessId() validates the ID is a real, active business
 * before accepting it. The middleware layer must still perform the
 * user-ownership assertion before calling this service.
 */
class BusinessContextService
{
    protected ?int $currentBusinessId = null;

    /**
     * Set the active business context.
     *
     * @throws \InvalidArgumentException if the business does not exist or is inactive.
     */
    public function setBusinessId(int $businessId): void
    {
        // Validate the business actually exists and is active
        $exists = Business::where('id', $businessId)
            ->where('status', 'active')
            ->exists();

        if (!$exists) {
            abort(404, "Business #{$businessId} not found or is inactive.");
        }

        $this->currentBusinessId = $businessId;
    }

    public function getBusinessId(): ?int
    {
        return $this->currentBusinessId;
    }

    public function hasBusinessContext(): bool
    {
        return $this->currentBusinessId !== null;
    }

    public function clearBusinessContext(): void
    {
        $this->currentBusinessId = null;
    }

    /**
     * Assert that the current authenticated user has access to the stored business.
     * Call this as a last-resort guard inside services that depend on this context.
     */
    public function assertCurrentUserHasAccess(): void
    {
        if (!$this->currentBusinessId) {
            return;
        }

        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        if (!$user->isSuperAdmin()) {
            $allowed = $user->assignedBusinessIds();
            if (!in_array($this->currentBusinessId, $allowed)) {
                abort(403, 'Business context mismatch — access denied.');
            }
        }
    }
}
