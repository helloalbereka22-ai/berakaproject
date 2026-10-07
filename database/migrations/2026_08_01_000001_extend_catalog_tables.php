<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Extend products ────────────────────────────────────────────────
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'type')) {
                $table->string('type')->default('general')->after('business_id')->index();
            }
            if (!Schema::hasColumn('products', 'unit')) {
                $table->string('unit')->nullable()->after('sku');
            }
            if (!Schema::hasColumn('products', 'package_size')) {
                $table->string('package_size')->nullable()->after('unit');
            }
            if (!Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode')->nullable()->after('package_size');
            }
            if (!Schema::hasColumn('products', 'reorder_point')) {
                $table->decimal('reorder_point', 15, 4)->default(0)->after('cost_price');
            }
            if (!Schema::hasColumn('products', 'min_stock')) {
                $table->decimal('min_stock', 15, 4)->default(0)->after('reorder_point');
            }
            if (!Schema::hasColumn('products', 'max_stock')) {
                $table->decimal('max_stock', 15, 4)->default(0)->after('min_stock');
            }
            if (!Schema::hasColumn('products', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('status');
            }
            if (!Schema::hasColumn('products', 'is_marketplace_visible')) {
                $table->boolean('is_marketplace_visible')->default(false)->after('is_featured');
            }
            if (!Schema::hasColumn('products', 'short_name')) {
                $table->string('short_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('products', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_marketplace_visible');
            }

            // Add indexes only if the columns exist (safe re-run)
            try { $table->index(['business_id', 'type']); } catch (\Throwable) {}
            try { $table->index(['business_id', 'status']); } catch (\Throwable) {}
            try { $table->index(['is_marketplace_visible', 'status']); } catch (\Throwable) {}
        });

        // ── Extend categories ──────────────────────────────────────────────
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'type')) {
                $table->string('type')->default('general')->after('business_id')->index();
            }
            if (!Schema::hasColumn('categories', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_active');
            }
            if (!Schema::hasColumn('categories', 'icon')) {
                $table->string('icon')->nullable()->after('sort_order');
            }
        });

        // ── Extend attribute_groups ────────────────────────────────────────
        Schema::table('attribute_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('attribute_groups', 'description')) {
                $table->string('description')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['type', 'sort_order', 'icon']);
        });

        Schema::table('attribute_groups', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'type']);
            $table->dropIndex(['business_id', 'status']);
            $table->dropIndex(['is_marketplace_visible', 'status']);
            $table->dropColumn([
                'type', 'unit', 'package_size', 'barcode',
                'reorder_point', 'min_stock', 'max_stock',
                'is_featured', 'is_marketplace_visible',
                'short_name', 'sort_order',
            ]);
        });
    }
};
