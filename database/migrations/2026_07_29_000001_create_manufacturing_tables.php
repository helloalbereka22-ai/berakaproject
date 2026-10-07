<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturing_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('planned_quantity', 15, 2);
            $table->string('status')->default('pending'); // pending, in_progress, completed, cancelled
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'branch_id']);
        });

        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturing_order_id')->constrained('manufacturing_orders')->cascadeOnDelete();
            $table->string('batch_number')->unique();
            $table->decimal('planned_quantity', 15, 2);
            $table->decimal('produced_quantity', 15, 2)->default(0);
            $table->string('status')->default('pending'); // pending, in_progress, completed, cancelled
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('batch_material_consumption', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained('production_batches')->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity_consumed', 15, 2);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->timestamp('consumed_at')->nullable();

            $table->index(['production_batch_id', 'raw_material_id'], 'bmc_batch_material_index');
        });

        Schema::create('quality_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained('production_batches')->cascadeOnDelete();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending'); // pending, passed, failed
            $table->integer('quantity_checked')->default(0);
            $table->integer('quantity_passed')->default(0);
            $table->integer('quantity_failed')->default(0);
            $table->text('remarks')->nullable();
            $table->timestamp('checked_at')->nullable();
        });

        Schema::create('finished_goods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained('production_batches')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->string('status')->default('available'); // available, distributed, reserved
            $table->timestamps();
        });

        Schema::create('distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finished_good_id')->constrained('finished_goods')->cascadeOnDelete();
            $table->foreignId('source_location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('destination_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('destination_location_id')->constrained('locations')->cascadeOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->string('status')->default('pending'); // pending, in_transit, completed
            $table->timestamp('distributed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // Drop old bill_of_materials if it existed
        Schema::dropIfExists('bill_of_materials');
    }

    public function down(): void
    {
        Schema::dropIfExists('distributions');
        Schema::dropIfExists('finished_goods');
        Schema::dropIfExists('quality_checks');
        Schema::dropIfExists('batch_material_consumption');
        Schema::dropIfExists('production_batches');
        Schema::dropIfExists('manufacturing_orders');
    }
};
