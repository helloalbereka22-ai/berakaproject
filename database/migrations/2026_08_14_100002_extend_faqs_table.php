<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            if (!Schema::hasColumn('faqs', 'category'))
                $table->string('category')->nullable()->after('answer');
            if (!Schema::hasColumn('faqs', 'is_active'))
                $table->boolean('is_active')->default(true)->after('category');
        });

        // articles: add author_name and article_type if missing
        Schema::table('articles', function (Blueprint $table) {
            if (!Schema::hasColumn('articles', 'article_type'))
                $table->string('article_type')->default('article')->after('status'); // article | guide
            if (!Schema::hasColumn('articles', 'excerpt'))
                $table->text('excerpt')->nullable()->after('title');
            if (!Schema::hasColumn('articles', 'cover_image_id'))
                $table->unsignedBigInteger('cover_image_id')->nullable()->after('excerpt');
            if (!Schema::hasColumn('articles', 'view_count'))
                $table->unsignedInteger('view_count')->default(0)->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $cols = ['article_type', 'excerpt', 'cover_image_id', 'view_count'];
            $table->dropColumn(array_filter($cols, fn($c) => Schema::hasColumn('articles', $c)));
        });
        Schema::table('faqs', function (Blueprint $table) {
            $cols = ['category', 'is_active'];
            $table->dropColumn(array_filter($cols, fn($c) => Schema::hasColumn('faqs', $c)));
        });
    }
};
