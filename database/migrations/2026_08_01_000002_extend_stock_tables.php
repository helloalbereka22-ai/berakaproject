<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Add missing columns to stock_levels ──────────────────────────
        Schema::table('stock_levels', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_levels', 'unit_cost'))
                $table->decimal('unit_cost', 15, 4)->default(0)->after('quantity_reserved');
            if (!Schema::hasColumn('stock_levels', 'opening_quantity'))
                $table->decimal('opening_quantity', 15, 4)->default(0)->after('unit_cost');
            if (!Schema::hasColumn('stock_levels', 'total_received'))
                $table->decimal('total_received', 15, 4)->default(0)->after('opening_quantity');
            if (!Schema::hasColumn('stock_levels', 'total_dispatched'))
                $table->decimal('total_dispatched', 15, 4)->default(0)->after('total_received');
            if (!Schema::hasColumn('stock_levels', 'business_id'))
                $table->unsignedBigInteger('business_id')->nullable()->after('id');
            if (!Schema::hasColumn('stock_levels', 'branch_id'))
                $table->unsignedBigInteger('branch_id')->nullable()->after('business_id');
            if (!Schema::hasColumn('stock_levels', 'created_at'))
                $table->timestamp('created_at')->nullable()->after('reorder_level');

            try { $table->index('business_id'); } catch (\Throwable) {}
            try { $table->index('branch_id'); } catch (\Throwable) {}
        });

        // ── Add missing columns to stock_movements ───────────────────────
        Schema::table('stock_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_movements', 'direction'))
                $table->string('direction')->default('in')->after('movement_type');
            if (!Schema::hasColumn('stock_movements', 'unit_cost'))
                $table->decimal('unit_cost', 15, 4)->default(0)->after('quantity');
            if (!Schema::hasColumn('stock_movements', 'total_cost'))
                $table->decimal('total_cost', 15, 4)->default(0)->after('unit_cost');
            if (!Schema::hasColumn('stock_movements', 'notes'))
                $table->text('notes')->nullable()->after('balance_after');
            if (!Schema::hasColumn('stock_movements', 'business_id'))
                $table->unsignedBigInteger('business_id')->nullable()->after('id');
            if (!Schema::hasColumn('stock_movements', 'branch_id'))
                $table->unsignedBigInteger('branch_id')->nullable()->after('business_id');

            try { $table->index('business_id'); } catch (\Throwable) {}
            try { $table->index('branch_id'); } catch (\Throwable) {}
            try { $table->index('movement_type'); } catch (\Throwable) {}
            try { $table->index('direction'); } catch (\Throwable) {}
        });

        // ── Stock thresholds ─────────────────────────────────────────────
        if (!Schema::hasTable('stock_thresholds')) {
            Schema::create('stock_thresholds', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id');
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
                $table->string('item_type');
                $table->unsignedBigInteger('item_id');
                $table->decimal('reorder_point', 15, 4)->default(0);
                $table->decimal('min_stock', 15, 4)->default(0);
                $table->decimal('max_stock', 15, 4)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['location_id', 'item_type', 'item_id'], 'stock_threshold_unique');
                $table->index(['business_id', 'item_type', 'item_id']);
            });
        }

        // ── Stock transfers ───────────────────────────────────────────────
        if (!Schema::hasTable('stock_transfers')) {
            Schema::create('stock_transfers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id');
                $table->string('reference')->unique();
                $table->foreignId('from_location_id')->constrained('locations');
                $table->foreignId('to_location_id')->constrained('locations');
                $table->string('item_type');
                $table->unsignedBigInteger('item_id');
                $table->decimal('quantity', 15, 4);
                $table->decimal('unit_cost', 15, 4)->default(0);
                $table->string('status')->default('pending');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'status']);
                $table->index(['from_location_id', 'to_location_id']);
            });
        }

        // ── Stock adjustments ─────────────────────────────────────────────
        if (!Schema::hasTable('stock_adjustments')) {
            Schema::create('stock_adjustments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id');
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->foreignId('location_id')->constrained('locations');
                $table->string('item_type');
                $table->unsignedBigInteger('item_id');
                $table->string('reference')->unique();
                $table->string('direction');
                $table->decimal('quantity', 15, 4);
                $table->decimal('previous_balance', 15, 4);
                $table->decimal('new_balance', 15, 4);
                $table->string('reason');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('pending');
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'status']);
                $table->index(['location_id', 'item_type', 'item_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_thresholds');

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(array_filter(['direction', 'unit_cost', 'total_cost', 'notes', 'business_id', 'branch_id'],
                fn($c) => Schema::hasColumn('stock_movements', $c)));
        });

        Schema::table('stock_levels', function (Blueprint $table) {
            $table->dropColumn(array_filter(['unit_cost', 'opening_quantity', 'total_received', 'total_dispatched', 'business_id', 'branch_id', 'created_at'],
                fn($c) => Schema::hasColumn('stock_levels', $c)));
        });
    }
};
