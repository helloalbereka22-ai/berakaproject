<?php
// Quick SettingsService functional test
// Run with: php test_settings_run.php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->boot();

/** @var \App\Services\SettingsService $s */
$s = $app->make(\App\Services\SettingsService::class);

$failures = [];
$pass = function(string $label) { echo "  PASS  $label\n"; };
$fail = function(string $label, string $detail) use (&$failures) {
    echo "  FAIL  $label — $detail\n";
    $failures[] = $label;
};

echo "\n=== SettingsService Tests ===\n\n";

// ── Write demo values ─────────────────────────────────────────────────────
$s->set('contact_phone',        '+251 9 11 22 33 44');
$s->set('contact_email',        'info@albareka.com');
$s->set('contact_address',      'Bole Sub-City, Addis Ababa, Ethiopia');
$s->set('contact_office_hours', 'Mon–Sat 8:00 AM – 5:00 PM');
$s->set('org_name',             'Al-Baraka Business Group');
$s->set('org_tagline',          'Premium Oils & Curated Books');
$s->set('social_telegram',      'https://t.me/albareka');
$s->set('social_facebook',      'https://facebook.com/albareka');
$s->set('social_twitter',       'NOT_A_URL');   // invalid — must be excluded

echo "[ Test 1 ] Read individual values\n";
$s->get('contact_phone') === '+251 9 11 22 33 44'
    ? $pass('contact_phone') : $fail('contact_phone', $s->get('contact_phone'));
$s->get('contact_email') === 'info@albareka.com'
    ? $pass('contact_email') : $fail('contact_email', $s->get('contact_email'));
$s->get('org_name') === 'Al-Baraka Business Group'
    ? $pass('org_name') : $fail('org_name', $s->get('org_name'));

echo "\n[ Test 2 ] contact() group returns only contact keys\n";
$contact = $s->contact();
isset($contact['contact_phone'])
    ? $pass('contact group has contact_phone') : $fail('contact group missing contact_phone','');
!isset($contact['social_facebook'])
    ? $pass('contact group excludes social keys') : $fail('contact group leaked social keys','');
!isset($contact['org_name'])
    ? $pass('contact group excludes org keys') : $fail('contact group leaked org keys','');

echo "\n[ Test 3 ] activeSocialLinks() filters invalid URLs\n";
$socials = $s->activeSocialLinks();
isset($socials['social_telegram'])
    ? $pass('telegram included') : $fail('telegram missing','');
isset($socials['social_facebook'])
    ? $pass('facebook included') : $fail('facebook missing','');
!isset($socials['social_twitter'])
    ? $pass('invalid twitter URL excluded') : $fail('invalid URL was not excluded','social_twitter='.$socials['social_twitter']);
count($socials) === 2
    ? $pass('exactly 2 valid social links') : $fail('wrong social count', count($socials));

echo "\n[ Test 4 ] Cache is flushed after write (cache coherence)\n";
$s->set('contact_phone', '+251 9 99 88 77 66');
$fresh = $s->get('contact_phone');
$fresh === '+251 9 99 88 77 66'
    ? $pass('cache flushed on set()') : $fail('stale cache after set()', $fresh);
// Restore
$s->set('contact_phone', '+251 9 11 22 33 44');

echo "\n[ Test 5 ] setMany() writes batch + flushes cache\n";
$s->setMany(['contact_email' => 'support@albareka.com', 'org_tagline' => 'Quality You Can Trust']);
$s->get('contact_email') === 'support@albareka.com'
    ? $pass('setMany email') : $fail('setMany email', $s->get('contact_email'));
$s->get('org_tagline') === 'Quality You Can Trust'
    ? $pass('setMany tagline') : $fail('setMany tagline', $s->get('org_tagline'));
// Restore
$s->setMany(['contact_email' => 'info@albareka.com', 'org_tagline' => 'Premium Oils & Curated Books']);

echo "\n[ Test 6 ] DB has 23 platform keys (seeded)\n";
$count = \App\Models\Setting::whereNull('business_id')->whereNull('branch_id')->count();
$count >= 23
    ? $pass("$count platform settings in DB") : $fail("expected >=23 settings", "got $count");

echo "\n============================\n";
if (empty($failures)) {
    echo "ALL " . (7 + 3) . " TESTS PASSED\n\n";
    echo "Current public values:\n";
    echo "  org_name        : " . $s->get('org_name') . "\n";
    echo "  org_tagline     : " . $s->get('org_tagline') . "\n";
    echo "  contact_phone   : " . $s->get('contact_phone') . "\n";
    echo "  contact_email   : " . $s->get('contact_email') . "\n";
    echo "  contact_address : " . $s->get('contact_address') . "\n";
    echo "  office_hours    : " . $s->get('contact_office_hours') . "\n";
    echo "  active_socials  : " . count($s->activeSocialLinks()) . "\n";
} else {
    echo count($failures) . " TESTS FAILED: " . implode(', ', $failures) . "\n";
    exit(1);
}
