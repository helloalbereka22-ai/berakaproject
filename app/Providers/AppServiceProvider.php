<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\BusinessContextService::class, function ($app) {
            return new \App\Services\BusinessContextService();
        });
    }

    public function boot(): void
    {
        // ── Morph map ─────────────────────────────────────────────────────
        // Short aliases stored in DB resolve to full model class names.
        // This fixes "Class 'product' not found" errors in polymorphic relations.
        Relation::enforceMorphMap([
            'product'             => \App\Models\Product::class,
            'category'            => \App\Models\Category::class,
            'user'                => \App\Models\User::class,
            'business'            => \App\Models\Business::class,
            'branch'              => \App\Models\Branch::class,
            'warehouse'           => \App\Models\Warehouse::class,
            'location'            => \App\Models\Location::class,
            'order'               => \App\Models\Order::class,
            'order_item'          => \App\Models\OrderItem::class,
            'invoice'             => \App\Models\Invoice::class,
            'payment'             => \App\Models\Payment::class,
            'delivery'            => \App\Models\Delivery::class,
            'customer'            => \App\Models\Customer::class,
            'manufacturing_order' => \App\Models\ManufacturingOrder::class,
            'production_batch'    => \App\Models\ProductionBatch::class,
            'finished_good'       => \App\Models\FinishedGood::class,
            'stock_transfer'      => \App\Models\StockTransfer::class,
            'stock_adjustment'    => \App\Models\StockAdjustment::class,
            'purchase'            => \App\Models\Purchase::class,
            'production_batch'    => \App\Models\ProductionBatch::class,
        ]);

        // ── Gates ─────────────────────────────────────────────────────────
        // Super Admin bypasses all gates.
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }
            return null;
        });

        Gate::define('permission', function (User $user, string $permission) {
            return $user->hasPermission($permission);
        });

        // Named permission gates used by @can() in Blade and middleware
        $gates = [
            'business.view', 'business.create', 'business.update', 'business.delete',
            'branch.view',   'branch.create',   'branch.update',   'branch.delete',
            'product.view',  'product.create',  'product.update',  'product.delete',
            'category.view', 'category.create', 'category.update', 'category.delete',
            'inventory.view', 'inventory.update',
            'manufacturing.view', 'manufacturing.manage', 'manufacturing.produce',
            'purchase.view', 'purchase.create', 'purchase.update', 'purchase.delete',
            'order.view',    'order.create',    'order.update',    'order.delete',   'order.process',
            'sale.view',     'sale.create',
            'customer.view', 'customer.create', 'customer.update',
            'payment.view',  'payment.approve',
            'user.view',     'user.create',     'user.update',     'user.delete',
            'staff.view',    'staff.create',    'staff.update',
            'role.view',     'role.create',     'role.update',     'role.delete',
            'content.view',  'content.create',  'content.update',  'content.delete',
            'report.view',
            'system.settings.view', 'system.settings.update',
            'audit-log.view',
        ];

        foreach ($gates as $gate) {
            Gate::define($gate, function (User $user) use ($gate) {
                return $user->hasPermission($gate);
            });
        }
    }
}
