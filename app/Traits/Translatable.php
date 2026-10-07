<?php

namespace App\Traits;

use App\Models\Translation;

/**
 * Adds polymorphic translation support to any Eloquent model.
 *
 * Usage in model:
 *   use Translatable;
 *   protected array $translatable = ['name', 'description'];
 *
 * Then call:
 *   $model->getTranslated('name')           — uses app locale, falls back to 'en', then raw column
 *   $model->getTranslated('name', 'ar')     — explicit locale
 *   $model->setTranslation('name', 'ar', 'Arabic name')
 *   $model->translations()                  — hasMany relationship
 */
trait Translatable
{
    // ── Relationship ──────────────────────────────────────────────────────

    public function translations()
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    /**
     * Get a translated field value for the given (or current app) locale.
     * Falls back: requested locale → English → raw column value.
     */
    public function getTranslated(string $field, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        // 1. Try the requested locale
        $value = $this->getTranslationValue($field, $locale);
        if ($value !== null && $value !== '') {
            return $value;
        }

        // 2. Fall back to English
        if ($locale !== 'en') {
            $value = $this->getTranslationValue($field, 'en');
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        // 3. Fall back to raw column
        return (string) ($this->getAttribute($field) ?? '');
    }

    /**
     * Convenience proxies – override in the model if the field names differ.
     */
    public function getTranslatedName(?string $locale = null): string
    {
        return $this->getTranslated('name', $locale);
    }

    public function getTranslatedTitle(?string $locale = null): string
    {
        return $this->getTranslated('title', $locale);
    }

    public function getTranslatedDescription(?string $locale = null): string
    {
        return $this->getTranslated('description', $locale);
    }

    // ── Mutations ─────────────────────────────────────────────────────────

    /**
     * Upsert a translation value for a specific locale and field.
     */
    public function setTranslation(string $field, string $locale, string $value): void
    {
        Translation::updateOrCreate(
            [
                'translatable_type' => static::class,
                'translatable_id'   => $this->getKey(),
                'locale'            => $locale,
                'field'             => $field,
            ],
            ['value' => $value]
        );
    }

    /**
     * Set multiple fields at once for one locale.
     * $data = ['name' => 'Arabic name', 'description' => 'Arabic desc']
     */
    public function setTranslations(string $locale, array $data): void
    {
        foreach ($data as $field => $value) {
            $this->setTranslation($field, $locale, $value);
        }
    }

    /**
     * Remove all translations for a locale and field combination.
     */
    public function deleteTranslation(string $field, string $locale): void
    {
        Translation::where('translatable_type', static::class)
            ->where('translatable_id', $this->getKey())
            ->where('locale', $locale)
            ->where('field', $field)
            ->delete();
    }

    // ── Internal ──────────────────────────────────────────────────────────

    private function getTranslationValue(string $field, string $locale): ?string
    {
        // If translations were eager-loaded, search in memory to avoid N+1
        if ($this->relationLoaded('translations')) {
            $t = $this->translations
                ->where('locale', $locale)
                ->where('field', $field)
                ->first();
            return $t?->value;
        }

        return Translation::where('translatable_type', static::class)
            ->where('translatable_id', $this->getKey())
            ->where('locale', $locale)
            ->where('field', $field)
            ->value('value');
    }
}
