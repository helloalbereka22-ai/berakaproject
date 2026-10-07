<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Show current state
echo "<pre style='background:#111;color:#0f0;padding:20px;'>";
echo "=== LANGUAGES TABLE COLUMNS ===\n";
$cols = DB::select("SHOW COLUMNS FROM languages");
foreach ($cols as $c) echo $c->Field . " (" . $c->Type . ")\n";

echo "\n=== CURRENT LANGUAGES ===\n";
$langs = DB::table('languages')->get();
foreach ($langs as $l) {
    echo json_encode((array)$l) . "\n";
}

// Fix - insert/update with ONLY the columns that exist
$toInsert = [
    ['code' => 'en', 'name' => 'English',     'is_rtl' => 0, 'is_active' => 1],
    ['code' => 'am', 'name' => 'አማርኛ',        'is_rtl' => 0, 'is_active' => 1],
    ['code' => 'ar', 'name' => 'العربية',      'is_rtl' => 1, 'is_active' => 1],
    ['code' => 'om', 'name' => 'Afaan Oromoo', 'is_rtl' => 0, 'is_active' => 1],
];

foreach ($toInsert as $lang) {
    $exists = DB::table('languages')->where('code', $lang['code'])->first();
    if ($exists) {
        DB::table('languages')->where('code', $lang['code'])->update([
            'is_active' => 1,
            'name'      => $lang['name'],
            'is_rtl'    => $lang['is_rtl'],
        ]);
        echo "\nUpdated: " . $lang['code'];
    } else {
        DB::table('languages')->insert(array_merge($lang, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        echo "\nInserted: " . $lang['code'];
    }
}

echo "\n\n=== AFTER FIX ===\n";
$langs = DB::table('languages')->get();
foreach ($langs as $l) {
    echo "code={$l->code} | name={$l->name} | is_active={$l->is_active} | is_rtl={$l->is_rtl}\n";
}
echo "</pre>";
echo "<br><a href='/' style='font-size:20px;padding:15px 30px;background:#3b82f6;color:white;border-radius:8px;text-decoration:none;'>✅ Go to Homepage</a>";
