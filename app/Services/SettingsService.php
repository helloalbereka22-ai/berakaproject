<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;


class SettingsService
{
    private const CACHE_KEY = 'platform_settings';
    private const TTL       = 600; // 10 minutes

    // ─── Key groups ───────────────────────────────────────────────────────

    public const ORGANIZATION_KEYS = [
        'org_name',
        'org_tagline',
        'org_description',
        'org_logo_path',
        'org_website',
    ];

    public const CONTACT_KEYS = [
        'contact_phone',
        'contact_phone_secondary',
        'contact_email',
        'contact_email_secondary',
        'contact_address',
        'contact_address_postal',
        'contact_office_hours',
        'contact_maps_url',
    ];

    public const SOCIAL_KEYS = [
        'social_facebook',
        'social_telegram',
        'social_youtube',
        'social_linkedin',
        'social_instagram',
        'social_twitter',
        'social_whatsapp',
        'social_tiktok',
    ];

    public const LEGAL_KEYS = [
        'legal_privacy_policy',
        'legal_terms',
    ];

    public const PAGE_CONTENT_KEYS = [
        'about_us_content',
        'contact_us_content',
        'vision_text',
        'mission_text',
        'values_text',
        'home_hero_title',
        'home_hero_subtitle',
        'blog_title',
        'blog_subtitle',
    ];

    public const SEO_KEYS = [
        'seo_meta_title',
        'seo_meta_description',
        'seo_meta_keywords',
    ];

    public const BRANDING_KEYS = [
        'system_logo_path',
        'login_bg_path',
    ];

    public const LOCATION_KEYS = [
        'org_latitude',
        'org_longitude',
    ];

    // ─── Read ─────────────────────────────────────────────────────────────

    /**
     * Get all platform settings as key → value array (cached).
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::TTL, function () {
            return Setting::whereNull('business_id')
                ->whereNull('branch_id')
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    /**
     * Get a single setting value with an optional default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Get all settings in a specific group.
     */
    public function group(array $keys): array
    {
        $all = $this->all();
        return array_intersect_key($all, array_flip($keys));
    }

    public function organization(): array  { return $this->group(self::ORGANIZATION_KEYS); }
    public function contact(): array       { return $this->group(self::CONTACT_KEYS); }
    public function social(): array        { return array_filter($this->group(self::SOCIAL_KEYS)); }
    public function seo(): array           { return $this->group(self::SEO_KEYS); }
    public function branding(): array      { return $this->group(self::BRANDING_KEYS); }
    public function location(): array      { return $this->group(self::LOCATION_KEYS); }
    public function pageContent(): array   { return $this->group(self::PAGE_CONTENT_KEYS); }

    /**
     * Get active social links (non-empty, valid URLs).
     */
    public function activeSocialLinks(): array
    {
        return array_filter(
            $this->social(),
            fn($url) => !empty(trim((string) $url)) && filter_var(trim($url), FILTER_VALIDATE_URL)
        );
    }

    // ─── Write ────────────────────────────────────────────────────────────

    /**
     * Upsert a single platform-global setting.
     */
    public function set(string $key, mixed $value, string $type = 'string'): void
    {
        Setting::updateOrCreate(
            ['business_id' => null, 'branch_id' => null, 'key' => $key],
            ['value' => (string) $value, 'type' => $type]
        );
        $this->flush();
    }

    /**
     * Upsert multiple settings at once.
     * $data = ['key' => 'value', ...]
     */
    public function setMany(array $data, string $type = 'string'): void
    {
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['business_id' => null, 'branch_id' => null, 'key' => $key],
                ['value' => (string) $value, 'type' => $type]
            );
        }
        $this->flush();
    }

    /**
     * Flush the settings cache. Called after any write.
     */
    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
