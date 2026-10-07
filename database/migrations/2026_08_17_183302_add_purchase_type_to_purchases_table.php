<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'purchase_type')) {
                // raw_material | packing_material | product
                $table->string('purchase_type')->default('raw_material')->after('product_id');
            }
        });

        // Back-fill existing rows: derive purchase_type from the linked product's type
        DB::statement("
            UPDATE purchases p
            JOIN products pr ON pr.id = p.product_id
            SET p.purchase_type = pr.type
            WHERE p.purchase_type = 'raw_material'
              AND pr.type IN ('packing_material', 'sale_product', 'finished_product', 'book', 'bundle', 'general')
        ");
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('purchase_type');
        });
    }
};
