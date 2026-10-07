<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop legacy inventory tables
        Schema::dropIfExists('inventory_histories');
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('inventory_locations');
        Schema::dropIfExists('inventory_thresholds');

        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->string('item_type'); // product, raw_material
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity_on_hand', 15, 2)->default(0);
            $table->decimal('quantity_reserved', 15, 2)->default(0);
            $table->decimal('reorder_level', 15, 2)->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['location_id', 'item_type', 'item_id']);
            $table->unique(['location_id', 'item_type', 'item_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->string('item_type'); // product, raw_material
            $table->unsignedBigInteger('item_id');
            $table->string('movement_type'); // purchase, production, distribution, transfer, sale, return, adjustment, damage, loss
            $table->decimal('quantity', 15, 2);
            $table->string('reference_type')->nullable(); // Order, ProductionBatch, Distribution, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->timestamp('moved_at')->useCurrent();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['location_id', 'item_type', 'item_id', 'moved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_levels');
    }
};
