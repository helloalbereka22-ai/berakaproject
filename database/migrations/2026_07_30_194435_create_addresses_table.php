<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('address_type');
            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('sub_city')->nullable();
            $table->string('street')->nullable();
            $table->string('postal_code')->nullable();
            $table->text('additional_information')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('addresses'); }
};