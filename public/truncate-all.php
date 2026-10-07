<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Tables to KEEP (never truncate)
$keep = ['users', 'settings', 'languages', 'roles', 'permissions', 'role_user', 'permission_role', 'migrations', 'password_reset_tokens', 'sessions', 'cache', 'jobs'];

if (isset($_POST['confirm']) && $_POST['confirm'] === 'YES DELETE ALL') {

    DB::statement('SET FOREIGN_KEY_CHECKS=0');

    $tables = DB::select('SHOW TABLES');
    $dbName = DB::getDatabaseName();
    $truncated = [];
    $skipped = [];

    foreach ($tables as $table) {
        $tableName = array_values((array)$table)[0];
        if (in_array($tableName, $keep)) {
            $skipped[] = $tableName;
            continue;
        }
        DB::table($tableName)->truncate();
        $truncated[] = $tableName;
    }

    DB::statement('SET FOREIGN_KEY_CHECKS=1');

    echo "<!DOCTYPE html><html><head><style>
    body{font-family:Arial;background:#0f172a;color:#e2e8f0;padding:30px;}
    .good{color:#34d399;} .skip{color:#fbbf24;}
    .box{background:#1e293b;padding:20px;border-radius:10px;margin:15px 0;}
    </style></head><body>";
    echo "<div class='box'><h2 class='good'>✅ Done! " . count($truncated) . " tables truncated</h2></div>";
    echo "<div class='box'><h3 class='good'>Truncated:</h3><ul>";
    foreach ($truncated as $t) echo "<li class='good'>✓ {$t}</li>";
    echo "</ul></div>";
    echo "<div class='box'><h3 class='skip'>Kept (not truncated):</h3><ul>";
    foreach ($skipped as $t) echo "<li class='skip'>● {$t}</li>";
    echo "</ul></div>";
    echo "<br><a href='/admin/dashboard' style='padding:12px 24px;background:#3b82f6;color:white;border-radius:8px;text-decoration:none;font-weight:bold;'>Go to Dashboard</a>";
    echo "</body></html>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:Arial;background:#0f172a;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.card{background:#1e293b;border:2px solid #f87171;border-radius:16px;padding:40px;max-width:520px;width:100%;text-align:center;}
h1{color:#f87171;font-size:28px;margin-bottom:15px;}
p{color:#94a3b8;margin-bottom:10px;line-height:1.6;}
.warn{background:#3a1a1a;border:1px solid #f87171;border-radius:8px;padding:15px;margin:20px 0;color:#f87171;font-size:14px;}
.keep{background:#1a3a1a;border:1px solid #34d399;border-radius:8px;padding:15px;margin:10px 0;color:#34d399;font-size:13px;text-align:left;}
input{width:100%;padding:12px;border-radius:8px;border:2px solid #475569;background:#0f172a;color:white;font-size:16px;margin:15px 0;text-align:center;}
input:focus{outline:none;border-color:#f87171;}
button{width:100%;padding:15px;background:#dc2626;color:white;border:none;border-radius:10px;font-size:18px;font-weight:bold;cursor:pointer;}
button:hover{background:#b91c1c;}
button:disabled{opacity:0.4;cursor:not-allowed;}
</style>
</head>
<body>
<div class="card">
    <h1>⚠️ TRUNCATE ALL DATA</h1>
    <p>This will <strong>permanently delete</strong> all data from every table.</p>

    <div class="warn">
        ❌ This CANNOT be undone!<br>
        All products, orders, purchases, stock movements,<br>
        branches, businesses, categories will be deleted.
    </div>

    <div class="keep">
        ✅ These tables will be KEPT:<br>
        • users &nbsp;• settings &nbsp;• languages<br>
        • roles &nbsp;• permissions &nbsp;• role_user<br>
        • permission_role &nbsp;• migrations &nbsp;• sessions
    </div>

    <form method="POST" onsubmit="return document.getElementById('inp').value === 'YES DELETE ALL'">
        <p style="color:#fbbf24;font-weight:bold;">Type exactly: YES DELETE ALL</p>
        <input type="text" id="inp" name="confirm" placeholder="YES DELETE ALL" autocomplete="off">
        <button type="submit" onclick="if(document.getElementById('inp').value !== 'YES DELETE ALL'){alert('Type exactly: YES DELETE ALL');return false;}">
            🗑️ TRUNCATE ALL DATA NOW
        </button>
    </form>
</div>
</body>
</html>
