<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fulfilling_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('delivery_address_id')->constrained('addresses');
            $table->date('delivery_date')->nullable();
            $table->string('status')->default('pending');
            $table->string('tracking_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('deliveries'); }
};