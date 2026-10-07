<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;
use App\Services\AuditService;

class CustomerService
{
    /**
     * Get all active customers for a business.
     */
    public function getBusinessCustomers(int $businessId): Collection
    {
        return Customer::where('business_id', $businessId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a new customer for a business.
     */
    public function createCustomer(int $businessId, array $data): Customer
    {
        $customer = Customer::create(array_merge($data, [
            'business_id' => $businessId,
            'is_active' => true,
        ]));

        // Log this action
        AuditService::log('crm', 'create', "Created new customer {$customer->name} (ID: {$customer->id}) for business ID {$businessId}");

        return $customer;
    }

    /**
     * Get a specific customer with their recent orders.
     */
    public function getCustomerProfile(int $customerId): ?Customer
    {
        return Customer::with(['orders' => function ($query) {
            $query->orderBy('created_at', 'desc')->take(10);
        }, 'orders.items'])->find($customerId);
    }
}
