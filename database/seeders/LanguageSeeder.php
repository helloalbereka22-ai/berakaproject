<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

/**
 * Seeds the three required application languages.
 * Safe to re-run — uses updateOrCreate so no duplicates.
 */
class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            [
                'code'      => 'en',
                'name'      => 'English',
                'is_rtl'    => false,
                'is_active' => true,
            ],
            [
                'code'      => 'am',
                'name'      => 'አማርኛ',
                'is_rtl'    => false,
                'is_active' => true,
            ],
            [
                'code'      => 'ar',
                'name'      => 'العربية',
                'is_rtl'    => true,
                'is_active' => true,
            ],
        ];

        foreach ($languages as $lang) {
            Language::updateOrCreate(
                ['code' => $lang['code']],
                $lang
            );
            $this->command->line("  <info>Language:</info> {$lang['code']} — {$lang['name']}");
        }

        $this->command->info('Languages seeded.');
    }
}
