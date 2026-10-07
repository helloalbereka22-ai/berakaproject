<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── manufacturing_orders ──────────────────────────────────────────
        Schema::table('manufacturing_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('manufacturing_orders', 'package_size'))
                $table->string('package_size')->nullable()->after('product_id');
            if (!Schema::hasColumn('manufacturing_orders', 'supervisor_id'))
                $table->foreignId('supervisor_id')->nullable()->after('created_by')
                      ->constrained('users')->nullOnDelete();
            if (!Schema::hasColumn('manufacturing_orders', 'notes'))
                $table->text('notes')->nullable()->after('supervisor_id');
        });

        // ── production_batches ────────────────────────────────────────────
        Schema::table('production_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('production_batches', 'oil_quantity_produced'))
                $table->decimal('oil_quantity_produced', 15, 4)->default(0)->after('produced_quantity');
            if (!Schema::hasColumn('production_batches', 'oil_unit'))
                $table->string('oil_unit', 10)->default('ml')->after('oil_quantity_produced');
            if (!Schema::hasColumn('production_batches', 'raw_material_cost'))
                $table->decimal('raw_material_cost', 15, 4)->default(0)->after('oil_unit');
            if (!Schema::hasColumn('production_batches', 'packing_cost'))
                $table->decimal('packing_cost', 15, 4)->default(0)->after('raw_material_cost');
            if (!Schema::hasColumn('production_batches', 'revenue'))
                $table->decimal('revenue', 15, 4)->default(0)->after('packing_cost');
            if (!Schema::hasColumn('production_batches', 'profit'))
                $table->decimal('profit', 15, 4)->default(0)->after('revenue');
            if (!Schema::hasColumn('production_batches', 'supervisor_id'))
                $table->foreignId('supervisor_id')->nullable()->after('profit')
                      ->constrained('users')->nullOnDelete();
            if (!Schema::hasColumn('production_batches', 'notes'))
                $table->text('notes')->nullable()->after('supervisor_id');
            if (!Schema::hasColumn('production_batches', 'business_id'))
                $table->unsignedBigInteger('business_id')->nullable()->after('id');
            if (!Schema::hasColumn('production_batches', 'branch_id'))
                $table->unsignedBigInteger('branch_id')->nullable()->after('business_id');

            try { $table->index(['business_id', 'status']); } catch (\Throwable) {}
            try { $table->index('batch_number'); } catch (\Throwable) {}
        });

        // ── batch_material_consumption ────────────────────────────────────
        Schema::table('batch_material_consumption', function (Blueprint $table) {
            if (!Schema::hasColumn('batch_material_consumption', 'quantity_used'))
                $table->decimal('quantity_used', 15, 4)->default(0)->after('raw_material_id');
            if (!Schema::hasColumn('batch_material_consumption', 'lot_number'))
                $table->string('lot_number')->nullable()->after('quantity_used');
            if (!Schema::hasColumn('batch_material_consumption', 'warehouse_id'))
                $table->unsignedBigInteger('warehouse_id')->nullable()->after('lot_number');
            if (!Schema::hasColumn('batch_material_consumption', 'consumption_type'))
                $table->string('consumption_type')->default('raw_material')->after('warehouse_id');
            if (!Schema::hasColumn('batch_material_consumption', 'created_at'))
                $table->timestamp('created_at')->nullable();
        });

        // ── quality_checks ────────────────────────────────────────────────
        Schema::table('quality_checks', function (Blueprint $table) {
            if (!Schema::hasColumn('quality_checks', 'inspection_date'))
                $table->date('inspection_date')->nullable()->after('status');
            if (!Schema::hasColumn('quality_checks', 'approval_date'))
                $table->date('approval_date')->nullable()->after('inspection_date');
            if (!Schema::hasColumn('quality_checks', 'rejected_reason'))
                $table->text('rejected_reason')->nullable()->after('remarks');
            if (!Schema::hasColumn('quality_checks', 'created_at'))
                $table->timestamp('created_at')->nullable();
            if (!Schema::hasColumn('quality_checks', 'updated_at'))
                $table->timestamp('updated_at')->nullable();
        });

        // ── finished_goods ────────────────────────────────────────────────
        Schema::table('finished_goods', function (Blueprint $table) {
            if (!Schema::hasColumn('finished_goods', 'warehouse_location_id'))
                $table->unsignedBigInteger('warehouse_location_id')->nullable()->after('status');
            if (!Schema::hasColumn('finished_goods', 'quantity_available'))
                $table->decimal('quantity_available', 15, 4)->default(0)->after('quantity');
            if (!Schema::hasColumn('finished_goods', 'quantity_distributed'))
                $table->decimal('quantity_distributed', 15, 4)->default(0)->after('quantity_available');
            if (!Schema::hasColumn('finished_goods', 'quantity_sold'))
                $table->decimal('quantity_sold', 15, 4)->default(0)->after('quantity_distributed');
            if (!Schema::hasColumn('finished_goods', 'quantity_returned'))
                $table->decimal('quantity_returned', 15, 4)->default(0)->after('quantity_sold');
        });
    }

    public function down(): void
    {
        Schema::table('finished_goods', function (Blueprint $table) {
            $cols = ['warehouse_location_id','quantity_available','quantity_distributed','quantity_sold','quantity_returned'];
            $table->dropColumn(array_filter($cols, fn($c) => Schema::hasColumn('finished_goods', $c)));
        });
        Schema::table('quality_checks', function (Blueprint $table) {
            $cols = ['inspection_date','approval_date','rejected_reason','created_at','updated_at'];
            $table->dropColumn(array_filter($cols, fn($c) => Schema::hasColumn('quality_checks', $c)));
        });
        Schema::table('batch_material_consumption', function (Blueprint $table) {
            $cols = ['quantity_used','lot_number','warehouse_id','consumption_type','created_at'];
            $table->dropColumn(array_filter($cols, fn($c) => Schema::hasColumn('batch_material_consumption', $c)));
        });
        Schema::table('production_batches', function (Blueprint $table) {
            $cols = ['oil_quantity_produced','oil_unit','raw_material_cost','packing_cost','revenue','profit','supervisor_id','notes','business_id','branch_id'];
            $table->dropColumn(array_filter($cols, fn($c) => Schema::hasColumn('production_batches', $c)));
        });
        Schema::table('manufacturing_orders', function (Blueprint $table) {
            $cols = ['package_size','supervisor_id','notes'];
            $table->dropColumn(array_filter($cols, fn($c) => Schema::hasColumn('manufacturing_orders', $c)));
        });
    }
};
