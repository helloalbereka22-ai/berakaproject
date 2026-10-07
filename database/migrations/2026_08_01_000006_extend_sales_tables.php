<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── orders ────────────────────────────────────────────────────────
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'business_id'))
                $table->unsignedBigInteger('business_id')->nullable()->after('id');
            if (!Schema::hasColumn('orders', 'notes'))
                $table->text('notes')->nullable()->after('total_amount');
            if (!Schema::hasColumn('orders', 'currency'))
                $table->string('currency', 3)->default('USD')->after('notes');

            try { $table->index('business_id'); } catch (\Throwable) {}
            try { $table->index(['customer_id', 'status']); } catch (\Throwable) {}
        });

        // ── order_items ───────────────────────────────────────────────────
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'business_id'))
                $table->unsignedBigInteger('business_id')->nullable()->after('order_id');
            if (!Schema::hasColumn('order_items', 'warehouse_location_id'))
                $table->unsignedBigInteger('warehouse_location_id')->nullable()->after('fulfilling_branch_id');
            if (!Schema::hasColumn('order_items', 'original_price'))
                $table->decimal('original_price', 15, 4)->default(0)->after('unit_price');
            if (!Schema::hasColumn('order_items', 'discount_type'))
                $table->string('discount_type')->nullable()->after('discount_amount');
            if (!Schema::hasColumn('order_items', 'notes'))
                $table->text('notes')->nullable()->after('discount_type');

            try { $table->index('business_id'); } catch (\Throwable) {}
        });

        // ── cart_items ────────────────────────────────────────────────────
        Schema::table('cart_items', function (Blueprint $table) {
            if (!Schema::hasColumn('cart_items', 'business_id'))
                $table->unsignedBigInteger('business_id')->nullable()->after('cart_id');
            if (!Schema::hasColumn('cart_items', 'original_price'))
                $table->decimal('original_price', 15, 4)->default(0)->after('unit_price');
            if (!Schema::hasColumn('cart_items', 'discount_amount'))
                $table->decimal('discount_amount', 15, 4)->default(0)->after('original_price');
            if (!Schema::hasColumn('cart_items', 'final_price'))
                $table->decimal('final_price', 15, 4)->default(0)->after('discount_amount');
            if (!Schema::hasColumn('cart_items', 'discount_type'))
                $table->string('discount_type')->nullable()->after('final_price');
        });

        // ── invoices ──────────────────────────────────────────────────────
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'customer_id'))
                $table->unsignedBigInteger('customer_id')->nullable()->after('order_id');
            if (!Schema::hasColumn('invoices', 'notes'))
                $table->text('notes')->nullable()->after('status');
        });

        // ── payments ──────────────────────────────────────────────────────
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'order_id'))
                $table->unsignedBigInteger('order_id')->nullable()->after('invoice_id');
            if (!Schema::hasColumn('payments', 'notes'))
                $table->text('notes')->nullable()->after('transaction_reference');
            if (!Schema::hasColumn('payments', 'payment_date'))
                $table->date('payment_date')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $cols = ['order_id', 'notes', 'payment_date'];
            $table->dropColumn(array_filter($cols, fn($c) => Schema::hasColumn('payments', $c)));
        });
        Schema::table('invoices', function (Blueprint $table) {
            $cols = ['customer_id', 'notes'];
            $table->dropColumn(array_filter($cols, fn($c) => Schema::hasColumn('invoices', $c)));
        });
        Schema::table('cart_items', function (Blueprint $table) {
            $cols = ['business_id','original_price','discount_amount','final_price','discount_type'];
            $table->dropColumn(array_filter($cols, fn($c) => Schema::hasColumn('cart_items', $c)));
        });
        Schema::table('order_items', function (Blueprint $table) {
            $cols = ['business_id','warehouse_location_id','original_price','discount_type','notes'];
            $table->dropColumn(array_filter($cols, fn($c) => Schema::hasColumn('order_items', $c)));
        });
        Schema::table('orders', function (Blueprint $table) {
            $cols = ['business_id', 'notes', 'currency'];
            $table->dropColumn(array_filter($cols, fn($c) => Schema::hasColumn('orders', $c)));
        });
    }
};
