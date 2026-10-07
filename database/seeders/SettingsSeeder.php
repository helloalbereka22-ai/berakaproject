<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Seed default platform settings.
     * Uses firstOrCreate so re-running never overwrites existing values.
     */
    public function run(): void
    {
        $defaults = [
            // ── Organization ─────────────────────────────────────────────
            'org_name'               => 'Al-Baraka Business Group',
            'org_tagline'            => 'Premium Oils & Curated Books',
            'org_description'        => 'A trusted multi-business organization delivering quality lubricating oil products and a comprehensive multi-branch bookstore network.',
            'org_logo_path'          => '',
            'org_website'            => '',

            // ── Contact ──────────────────────────────────────────────────
            'contact_phone'          => '',
            'contact_phone_secondary'=> '',
            'contact_email'          => '',
            'contact_email_secondary'=> '',
            'contact_address'        => '',
            'contact_address_postal' => '',
            'contact_office_hours'   => '',
            'contact_maps_url'       => '',

            // ── Social media ─────────────────────────────────────────────
            'social_facebook'        => '',
            'social_telegram'        => '',
            'social_youtube'         => '',
            'social_linkedin'        => '',
            'social_instagram'       => '',
            'social_twitter'         => '',
            'social_whatsapp'        => '',
            'social_tiktok'          => '',

            // ── Legal pages ───────────────────────────────────────────────
            'legal_privacy_policy'   => '',
            'legal_terms'            => '',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(
                ['business_id' => null, 'branch_id' => null, 'key' => $key],
                ['value' => $value, 'type' => 'string']
            );
        }

        $this->command->info('Platform settings seeded (' . count($defaults) . ' keys).');
    }
}
