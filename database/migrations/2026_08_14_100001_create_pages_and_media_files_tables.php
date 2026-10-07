<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── pages ─────────────────────────────────────────────────────────
        if (!Schema::hasTable('pages')) {
            Schema::create('pages', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->longText('body')->nullable();
                $table->string('meta_description')->nullable();
                $table->string('status')->default('draft'); // draft | published
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // ── media_files ───────────────────────────────────────────────────
        if (!Schema::hasTable('media_files')) {
            Schema::create('media_files', function (Blueprint $table) {
                $table->id();
                $table->string('file_name');
                $table->string('file_path');
                $table->string('disk')->default('local');
                $table->string('file_type')->nullable();   // image | document | video
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('file_size')->default(0); // bytes
                $table->string('alt_text')->nullable();
                $table->string('title')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
        Schema::dropIfExists('pages');
    }
};
