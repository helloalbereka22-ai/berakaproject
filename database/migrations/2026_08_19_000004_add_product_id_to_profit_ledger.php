<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds product_id to profit_ledger so we can query profit by product
 * without joining through order_items every time.
 * Column is nullable to remain compatible with any legacy rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profit_ledger', function (Blueprint $table) {
            if (!Schema::hasColumn('profit_ledger', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('branch_id');
                $table->index('product_id');
            }
        });

        // Back-fill from order_items for existing rows
        \Illuminate\Support\Facades\DB::statement('
            UPDATE profit_ledger pl
            JOIN order_items oi ON oi.id = pl.order_item_id
            SET pl.product_id = oi.product_id
            WHERE pl.product_id IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('profit_ledger', function (Blueprint $table) {
            if (Schema::hasColumn('profit_ledger', 'product_id')) {
                $table->dropIndex(['product_id']);
                $table->dropColumn('product_id');
            }
        });
    }
};
