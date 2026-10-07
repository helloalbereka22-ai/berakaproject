<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<pre style='background:#111;color:#0f0;padding:20px;font-size:14px;'>";

// Show current languages
echo "=== CURRENT LANGUAGES IN DB ===\n";
$langs = DB::table('languages')->get();
foreach ($langs as $l) {
    echo "code: {$l->code} | name: {$l->name} | active: " . ($l->is_active ? 'YES' : 'NO') . " | rtl: " . ($l->is_rtl ? 'YES' : 'NO') . "\n";
}

if (isset($_POST['activate'])) {
    // Activate Amharic
    DB::table('languages')->updateOrInsert(
        ['code' => 'am'],
        ['code' => 'am', 'name' => 'አማርኛ', 'native_name' => 'አማርኛ', 'is_active' => 1, 'is_rtl' => 0, 'flag' => '🇪🇹', 'sort_order' => 2]
    );
    // Activate Arabic
    DB::table('languages')->updateOrInsert(
        ['code' => 'ar'],
        ['code' => 'ar', 'name' => 'العربية', 'native_name' => 'العربية', 'is_active' => 1, 'is_rtl' => 1, 'flag' => '🇸🇦', 'sort_order' => 3]
    );
    // Activate Afan Oromo
    DB::table('languages')->updateOrInsert(
        ['code' => 'om'],
        ['code' => 'om', 'name' => 'Afaan Oromoo', 'native_name' => 'Afaan Oromoo', 'is_active' => 1, 'is_rtl' => 0, 'flag' => '🇪🇹', 'sort_order' => 4]
    );
    // Make sure English is active too
    DB::table('languages')->updateOrInsert(
        ['code' => 'en'],
        ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => 1, 'is_rtl' => 0, 'flag' => '🇬🇧', 'sort_order' => 1]
    );

    echo "\n✅ All 4 languages activated!\n\n";
    echo "=== UPDATED LANGUAGES ===\n";
    $langs = DB::table('languages')->get();
    foreach ($langs as $l) {
        echo "code: {$l->code} | name: {$l->name} | active: " . ($l->is_active ? 'YES' : 'NO') . "\n";
    }
}

echo "</pre>";

if (!isset($_POST['activate'])):
?>
<form method="POST" style="padding:20px;">
    <button type="submit" name="activate"
        style="padding:20px 40px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;font-size:20px;font-weight:bold;border:none;border-radius:12px;cursor:pointer;">
        ✅ ACTIVATE ALL LANGUAGES (Amharic, Arabic, Afan Oromo, English)
    </button>
</form>
<?php endif; ?>
