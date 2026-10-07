<?php

namespace App\Models;

use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Article extends Model
{
    use SoftDeletes, Translatable;

    protected $fillable = [
        'category_id', 'title', 'slug', 'excerpt', 'body',
        'status', 'article_type', 'cover_image_id',
        'published_at', 'seo_metadata', 'created_by', 'view_count',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'seo_metadata' => 'json',
        'view_count'   => 'integer',
    ];

    // ── Boot ─────────────────────────────────────────────────────────────
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $article) {
            if (empty($article->slug)) {
                $article->slug = static::generateUniqueSlug($article->title);
            }
        });

        static::updating(function (self $article) {
            if ($article->isDirty('title') && empty($article->slug)) {
                $article->slug = static::generateUniqueSlug($article->title);
            }
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    public static function generateUniqueSlug(string $title): string
    {
        $slug  = Str::slug($title);
        $count = static::where('slug', 'like', "{$slug}%")->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }

    // ── Scopes ────────────────────────────────────────────────────────────
    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published')
                 ->where(fn($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function scopeOfType(Builder $q, string $type): Builder
    {
        return $q->where('article_type', $type);
    }

    // ── Relationships ─────────────────────────────────────────────────────
    public function category()  { return $this->belongsTo(Category::class); }
    public function author()    { return $this->belongsTo(User::class, 'created_by'); }
    public function tags()      { return $this->belongsToMany(Tag::class, 'article_tags'); }
    public function coverImage(){ return $this->belongsTo(MediaFile::class, 'cover_image_id'); }

    // ── Translation helpers ───────────────────────────────────────────────
    public function getTranslatedTitle(?string $locale = null): string
    {
        return $this->getTranslated('title', $locale);
    }

    public function getTranslatedExcerpt(?string $locale = null): string
    {
        return $this->getTranslated('excerpt', $locale) ?: Str::limit($this->getTranslatedBody($locale), 160);
    }

    public function getTranslatedBody(?string $locale = null): string
    {
        return $this->getTranslated('body', $locale);
    }
}
