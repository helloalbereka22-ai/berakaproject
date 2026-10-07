<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the legacy stock tracking tables that have been superseded by
     * dynamic stock calculation via the stock_movements ledger.
     *
     * Tables removed:
     *   - stock_levels       → replaced by SUM(stock_movements) aggregate
     *   - stock_adjustments  → replaced by stock_movements with type='adjustment'
     *   - product_images     → replaced by products.image_path column
     */
    public function up(): void
    {
        // Drop FK-constrained tables first
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_levels');
        Schema::dropIfExists('product_images');
    }

    /**
     * Restore the dropped tables (for rollback / development revert only).
     * WARNING: Data will be lost on rollback. This is intentional.
     */
    public function down(): void
    {
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->string('item_type', 50);
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity_on_hand', 15, 4)->default(0);
            $table->decimal('reorder_level', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->timestamps();
            $table->unique(['location_id', 'item_type', 'item_id'], 'uq_stock_level_item');
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_level_id')->constrained('stock_levels')->cascadeOnDelete();
            $table->string('direction', 10);
            $table->decimal('quantity', 15, 4);
            $table->string('reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }
};
