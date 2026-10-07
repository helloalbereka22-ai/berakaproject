<?php

namespace App\Models;

use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Page extends Model
{
    use SoftDeletes, Translatable;

    protected $fillable = [
        'title', 'slug', 'body', 'meta_description',
        'status', 'created_by', 'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published');
    }

    public function author() { return $this->belongsTo(User::class, 'created_by'); }

    public function getTranslatedTitle(?string $locale = null): string  { return $this->getTranslated('title', $locale); }
    public function getTranslatedBody(?string $locale = null): string   { return $this->getTranslated('body', $locale); }
}
