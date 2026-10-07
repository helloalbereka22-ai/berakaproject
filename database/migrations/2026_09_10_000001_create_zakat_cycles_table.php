<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zakat_cycles', function (Blueprint $table) {
            $table->id();

            // Cycle dates (Gregorian, derived from Hijri)
            $table->date('start_date');                         // Gregorian start
            $table->date('due_date');                          // Gregorian due (approx. +354 days = 1 Hijri year)

            // Hijri dates stored as strings for display
            $table->string('start_date_hijri')->nullable();
            $table->string('due_date_hijri')->nullable();

            // Wealth & Nisab
            $table->decimal('nisab_amount',   18, 2)->default(0);   // Configured nisab threshold
            $table->decimal('zakatable_amount',18, 2)->default(0);  // Calculated zakatable wealth
            $table->decimal('zakat_rate',     8, 4)->default(2.5);  // Default 2.5%
            $table->decimal('zakat_due',      18, 2)->default(0);   // zakatable_amount × rate/100

            // Payment
            $table->decimal('amount_paid',    18, 2)->default(0);
            $table->date('paid_date')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();

            // Status: active | due | paid | overdue
            $table->string('status')->default('active');

            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('status');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zakat_cycles');
    }
};
