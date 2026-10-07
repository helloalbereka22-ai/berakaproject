<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Customers
 * Customer management. Business-scoped.
 */
class CustomerController extends ApiController
{
    public function __construct(private readonly AuthorizationService $authService) {}

    /**
     * List customers
     *
     * @authenticated
     * @queryParam business_id int Filter by business.
     * @queryParam status string Filter by status (active|inactive).
     * @queryParam search string Search by name or email.
     * @queryParam per_page int Items per page (default 25, max 100).
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();

        // Customers don't directly carry business_id — scope via their orders' business
        // For admin-level CRM: filter by customers who have ordered from user's businesses
        $query = Customer::query()
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $businessIds = $user->assignedBusinessIds();
                $q->whereHas('orders', fn($oq) => $oq->whereIn('business_id', $businessIds));
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->search;
                $q->where(fn($sq) =>
                    $sq->where('name', 'like', "%{$term}%")
                       ->orWhere('email', 'like', "%{$term}%")
                       ->orWhere('phone', 'like', "%{$term}%")
                );
            })
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderBy('name');

        $perPage = min((int) $request->input('per_page', 25), 100);

        return $this->paginated(CustomerResource::collection($query->paginate($perPage)));
    }

    /**
     * Create customer
     *
     * @authenticated
     * @bodyParam name string required.
     * @bodyParam email string optional. Must be unique.
     * @bodyParam phone string optional.
     * @bodyParam status string optional (active|inactive).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'nullable|email|max:255|unique:customers,email',
            'phone'  => 'nullable|string|max:30',
            'status' => 'nullable|in:active,inactive',
        ]);

        $customer = Customer::create($data);

        AuditService::logCreate('App\\Models\\Customer', $customer);

        return $this->created(new CustomerResource($customer), 'Customer created.');
    }

    /**
     * Get customer
     *
     * @authenticated
     * @urlParam customer int required The customer ID.
     */
    public function show(Request $request, Customer $customer): JsonResponse
    {
        // Verify this customer belongs to the user's business via their orders
        if (!$request->user()->isSuperAdmin()) {
            $businessIds = $request->user()->assignedBusinessIds();
            $hasAccess   = $customer->orders()->whereIn('business_id', $businessIds)->exists();
            if (!$hasAccess) {
                return $this->forbidden('Access denied.');
            }
        }

        return $this->ok(new CustomerResource($customer->load('addresses')));
    }

    /**
     * Update customer
     *
     * @authenticated
     * @urlParam customer int required The customer ID.
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        if (!$request->user()->isSuperAdmin()) {
            $businessIds = $request->user()->assignedBusinessIds();
            $hasAccess   = $customer->orders()->whereIn('business_id', $businessIds)->exists();
            if (!$hasAccess) {
                return $this->forbidden('Access denied.');
            }
        }

        $data = $request->validate([
            'name'   => 'sometimes|string|max:255',
            'email'  => "sometimes|email|max:255|unique:customers,email,{$customer->id}",
            'phone'  => 'nullable|string|max:30',
            'status' => 'nullable|in:active,inactive',
        ]);

        $original = $customer->toArray();
        $customer->update($data);

        AuditService::logUpdate('App\\Models\\Customer', $customer, $original);

        return $this->ok(new CustomerResource($customer));
    }

    /**
     * Delete customer
     *
     * @authenticated
     * @urlParam customer int required The customer ID.
     */
    public function destroy(Request $request, Customer $customer): JsonResponse
    {
        if (!$request->user()->isSuperAdmin()) {
            return $this->forbidden('Only Super Administrators can delete customers.');
        }

        AuditService::logDelete('App\\Models\\Customer', $customer);
        $customer->delete();

        return $this->ok(null, 'Customer deleted.');
    }
}
