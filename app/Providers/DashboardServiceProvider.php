<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\WidgetRegistry;

class DashboardServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(WidgetRegistry::class, function ($app) {
            return new WidgetRegistry();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(WidgetRegistry $registry): void
    {
        // 1. Super Admin Widgets
        $registry->register('admin.global-kpis', 'admin.widgets.global-kpi-cards', ['Super Admin', 'Admin'], [], 10, 'col-span-1 md:col-span-2 xl:col-span-4');
        $registry->register('admin.pending-approvals', 'admin.widgets.pending-approvals-queue', ['Super Admin', 'Admin'], [], 20, 'col-span-1 md:col-span-2 xl:col-span-2');
        $registry->register('admin.inventory-trends', 'admin.widgets.inventory-trends-chart', ['Super Admin', 'Admin'], [], 30, 'col-span-1 md:col-span-2 xl:col-span-2');
        $registry->register('admin.user-management', 'admin.widgets.user-management', ['Super Admin', 'Admin'], [], 40, 'col-span-1 md:col-span-2 xl:col-span-4');
        $registry->register('admin.global-sales', 'admin.widgets.global-sales-monitor', ['Super Admin', 'Admin'], [], 50, 'col-span-1 md:col-span-2 xl:col-span-2');
        $registry->register('admin.business-overview', 'admin.widgets.business-overview', ['Super Admin', 'Admin'], [], 60, 'col-span-1 md:col-span-2 xl:col-span-2');
        $registry->register('admin.audit-logs', 'admin.widgets.platform-audit-logs', ['Super Admin', 'Admin'], [], 70, 'col-span-1 md:col-span-2 xl:col-span-4');

        // 2. Manufacturing Manager Widgets
        $registry->register('mfg.raw-materials', 'admin.widgets.raw-material-availability', ['Manufacturing Manager'], ['manufacturing'], 10, 'col-span-1 md:col-span-2');
        $registry->register('mfg.production-alerts', 'admin.widgets.production-alerts', ['Manufacturing Manager'], ['manufacturing'], 20, 'col-span-1 md:col-span-2');

        // 3. Book Store Manager Widgets
        $registry->register('bookstore.branch-inventory', 'admin.widgets.branch-book-inventory', ['Book Store Manager'], ['book_store'], 10, 'col-span-1 md:col-span-2');
        $registry->register('bookstore.low-stock', 'admin.widgets.low-stock-books', ['Book Store Manager'], ['book_store'], 20, 'col-span-1 md:col-span-2');

        // 4. Generic Business Manager Widgets (Applies to all business types)
        $registry->register('business.kpi-cards', 'admin.widgets.business-kpi-cards', ['Business Manager'], [], 10, 'col-span-1 md:col-span-2 xl:col-span-4');
        $registry->register('business.recent-requests', 'admin.widgets.business-recent-requests', ['Business Manager'], [], 20, 'col-span-1 md:col-span-2');

        // 5. Shared Widgets
        $registry->register('shared.recent-activity', 'admin.widgets.recent-activity-timeline', ['Super Admin', 'Admin', 'Manufacturing Manager', 'Book Store Manager', 'Business Manager'], [], 100, 'col-span-1 md:col-span-2 xl:col-span-4');
    }
}
