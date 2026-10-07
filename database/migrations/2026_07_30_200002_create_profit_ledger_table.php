<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profit_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->decimal('selling_price', 15, 2);
            $table->decimal('cost_price', 15, 2);
            $table->decimal('quantity', 15, 2);
            $table->decimal('revenue', 15, 2);
            $table->decimal('total_cost', 15, 2);
            $table->decimal('profit', 15, 2);
            $table->timestamp('recorded_at')->useCurrent();

            $table->index(['business_id', 'branch_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_ledger');
    }
};
