<?php

namespace App\Models;

use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Model;

class FAQ extends Model
{
    use Translatable;

    protected $table = 'faqs';

    protected $fillable = [
        'question', 'answer', 'category',
        'is_active', 'status', 'sort_order',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────
    public function scopeActive($q) { return $q->where('is_active', true); }

    // ── Translation helpers ───────────────────────────────────────────────
    public function getTranslatedQuestion(?string $locale = null): string
    {
        return $this->getTranslated('question', $locale);
    }

    public function getTranslatedAnswer(?string $locale = null): string
    {
        return $this->getTranslated('answer', $locale);
    }
}
