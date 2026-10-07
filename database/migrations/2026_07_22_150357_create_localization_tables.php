<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g. 'en', 'ar'
            $table->string('name'); // e.g. 'English', 'Arabic'
            $table->boolean('is_rtl')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('translatable_type'); // e.g. App\Models\Product
            $table->unsignedBigInteger('translatable_id');
            $table->string('locale'); // e.g. 'en', 'ar'
            $table->string('field'); // e.g. 'name', 'description'
            $table->text('value');
            $table->timestamps();

            $table->unique(['translatable_type', 'translatable_id', 'locale', 'field'], 'translatable_unique');
            $table->index(['translatable_type', 'translatable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
        Schema::dropIfExists('languages');
    }
};
