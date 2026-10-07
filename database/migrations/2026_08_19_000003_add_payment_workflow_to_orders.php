<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the manual payment approval workflow columns to orders.
 *
 * payment_status:        pending | approved | rejected
 * payment_approved_at:   timestamp when seller approved payment
 * payment_approved_by:   user id of seller who approved
 * branch_id:             the branch this order is assigned to for fulfilment
 *
 * Also fixes order_items.quantity from integer → decimal(15,4) so fractional
 * quantities (e.g. 0.5 kg) work correctly for all product types.
 *
 * Back-fills existing orders: sets payment_status = 'approved' for any order
 * that is already confirmed/processing/completed (they pre-date this workflow).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── orders ────────────────────────────────────────────────────────
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status', 20)
                    ->default('pending')
                    ->after('status')
                    ->comment('pending | approved | rejected');
            }
            if (!Schema::hasColumn('orders', 'payment_approved_at')) {
                $table->timestamp('payment_approved_at')
                    ->nullable()
                    ->after('payment_status');
            }
            if (!Schema::hasColumn('orders', 'payment_approved_by')) {
                $table->unsignedBigInteger('payment_approved_by')
                    ->nullable()
                    ->after('payment_approved_at')
                    ->comment('FK to users — the seller who approved the payment');
            }
            if (!Schema::hasColumn('orders', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')
                    ->nullable()
                    ->after('business_id')
                    ->comment('Fulfilling branch for this order');
            }
        });

        // ── order_items.quantity: integer → decimal ───────────────────────
        // MySQL requires MODIFY COLUMN with full definition.
        if (Schema::hasColumn('order_items', 'quantity')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->decimal('quantity', 15, 4)->default(1)->change();
            });
        }

        // ── Back-fill: existing non-pending orders are already approved ───
        \Illuminate\Support\Facades\DB::table('orders')
            ->whereIn('status', ['confirmed', 'processing', 'completed'])
            ->whereNull('payment_approved_at')
            ->update(['payment_status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $toDrop = [];
            foreach (['payment_status', 'payment_approved_at', 'payment_approved_by', 'branch_id'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $toDrop[] = $col;
                }
            }
            if ($toDrop) $table->dropColumn($toDrop);
        });

        if (Schema::hasColumn('order_items', 'quantity')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->integer('quantity')->default(1)->change();
            });
        }
    }
};
