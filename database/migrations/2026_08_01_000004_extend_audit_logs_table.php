<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'business_id'))
                $table->unsignedBigInteger('business_id')->nullable()->after('user_agent');
            if (!Schema::hasColumn('audit_logs', 'branch_id'))
                $table->unsignedBigInteger('branch_id')->nullable()->after('business_id');
            if (!Schema::hasColumn('audit_logs', 'description'))
                $table->text('description')->nullable()->after('branch_id');

            try { $table->index('business_id'); } catch (\Throwable) {}
            try { $table->index('action'); } catch (\Throwable) {}
            try { $table->index('created_at'); } catch (\Throwable) {}
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $cols = ['business_id', 'branch_id', 'description'];
            $table->dropColumn(array_filter($cols, fn($c) => Schema::hasColumn('audit_logs', $c)));
        });
    }
};
