<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── batch_reconciliations ─────────────────────────────────────────
        Schema::table('batch_reconciliations', function (Blueprint $table) {
            if (!Schema::hasColumn('batch_reconciliations', 'has_mismatch'))
                $table->boolean('has_mismatch')->default(false)->after('remaining_quantity');
            if (!Schema::hasColumn('batch_reconciliations', 'expected_quantity'))
                $table->decimal('expected_quantity', 15, 4)->default(0)->after('has_mismatch');
            if (!Schema::hasColumn('batch_reconciliations', 'actual_quantity'))
                $table->decimal('actual_quantity', 15, 4)->default(0)->after('expected_quantity');
            if (!Schema::hasColumn('batch_reconciliations', 'variance'))
                $table->decimal('variance', 15, 4)->default(0)->after('actual_quantity');
            if (!Schema::hasColumn('batch_reconciliations', 'status'))
                $table->string('status')->default('clean')->after('variance');
            if (!Schema::hasColumn('batch_reconciliations', 'investigation_notes'))
                $table->text('investigation_notes')->nullable()->after('status');
            if (!Schema::hasColumn('batch_reconciliations', 'investigated_by'))
                $table->foreignId('investigated_by')->nullable()->after('investigation_notes')
                      ->constrained('users')->nullOnDelete();
            if (!Schema::hasColumn('batch_reconciliations', 'investigated_at'))
                $table->timestamp('investigated_at')->nullable()->after('investigated_by');
            if (!Schema::hasColumn('batch_reconciliations', 'created_at'))
                $table->timestamp('created_at')->nullable();
            if (!Schema::hasColumn('batch_reconciliations', 'updated_at'))
                $table->timestamp('updated_at')->nullable();

            try { $table->index(['production_batch_id', 'status']); } catch (\Throwable) {}
        });

        // ── distributions ─────────────────────────────────────────────────
        Schema::table('distributions', function (Blueprint $table) {
            if (!Schema::hasColumn('distributions', 'quantity_received'))
                $table->decimal('quantity_received', 15, 4)->default(0)->after('quantity');
            if (!Schema::hasColumn('distributions', 'reference'))
                $table->string('reference')->nullable()->after('quantity_received');
            if (!Schema::hasColumn('distributions', 'notes'))
                $table->text('notes')->nullable()->after('reference');
            if (!Schema::hasColumn('distributions', 'received_at'))
                $table->timestamp('received_at')->nullable()->after('notes');
            if (!Schema::hasColumn('distributions', 'created_at'))
                $table->timestamp('created_at')->nullable();
            if (!Schema::hasColumn('distributions', 'updated_at'))
                $table->timestamp('updated_at')->nullable();
        });

        // ── batch_returns ─────────────────────────────────────────────────
        Schema::table('batch_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('batch_returns', 'status'))
                $table->string('status')->default('pending')->after('reason');
            if (!Schema::hasColumn('batch_returns', 'approved_by'))
                $table->foreignId('approved_by')->nullable()->after('status')
                      ->constrained('users')->nullOnDelete();
            if (!Schema::hasColumn('batch_returns', 'approved_at'))
                $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('batch_returns', function (Blueprint $table) {
            $cols = ['status', 'approved_by', 'approved_at'];
            $table->dropColumn(array_filter($cols, fn($c) => Schema::hasColumn('batch_returns', $c)));
        });
        Schema::table('distributions', function (Blueprint $table) {
            $cols = ['quantity_received', 'reference', 'notes', 'received_at', 'created_at', 'updated_at'];
            $table->dropColumn(array_filter($cols, fn($c) => Schema::hasColumn('distributions', $c)));
        });
        Schema::table('batch_reconciliations', function (Blueprint $table) {
            $cols = ['has_mismatch','expected_quantity','actual_quantity','variance','status','investigation_notes','investigated_by','investigated_at','created_at','updated_at'];
            $table->dropColumn(array_filter($cols, fn($c) => Schema::hasColumn('batch_reconciliations', $c)));
        });
    }
};
