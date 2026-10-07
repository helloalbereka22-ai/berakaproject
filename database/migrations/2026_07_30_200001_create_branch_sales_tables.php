<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('production_batch_id')->nullable()->constrained('production_batches')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->date('sale_date');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['production_batch_id', 'branch_id', 'sale_date']);
        });

        Schema::create('batch_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained('production_batches')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->text('reason')->nullable();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('batch_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained('production_batches')->cascadeOnDelete();
            $table->decimal('produced_quantity', 15, 2)->default(0);
            $table->decimal('distributed_quantity', 15, 2)->default(0);
            $table->decimal('sold_quantity', 15, 2)->default(0);
            $table->decimal('returned_quantity', 15, 2)->default(0);
            $table->decimal('remaining_quantity', 15, 2)->default(0);
            $table->timestamp('reconciled_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_reconciliations');
        Schema::dropIfExists('batch_returns');
        Schema::dropIfExists('branch_sales');
    }
};
