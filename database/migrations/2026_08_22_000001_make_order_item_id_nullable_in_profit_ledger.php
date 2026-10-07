<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profit_ledger', function (Blueprint $table) {
            // Drop the existing FK constraint first, then re-add as nullable
            $table->dropForeign(['order_item_id']);
            $table->unsignedBigInteger('order_item_id')->nullable()->change();
            $table->foreign('order_item_id')
                  ->references('id')->on('order_items')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('profit_ledger', function (Blueprint $table) {
            $table->dropForeign(['order_item_id']);
            $table->unsignedBigInteger('order_item_id')->nullable(false)->change();
            $table->foreign('order_item_id')
                  ->references('id')->on('order_items')
                  ->cascadeOnDelete();
        });
    }
};
