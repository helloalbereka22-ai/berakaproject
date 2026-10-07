<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('manufacturing_quantity', 15, 4)->default(0)->after('min_stock');
            $table->timestamp('manufacturing_added_at')->nullable()->after('manufacturing_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('manufacturing_quantity');
            $table->dropColumn('manufacturing_added_at');
        });
    }
};