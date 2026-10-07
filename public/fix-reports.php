<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<pre style='background:#111;color:#0f0;padding:20px;font-size:14px;'>";

// Check if report.view permission exists
$permission = DB::table('permissions')->where('name', 'report.view')->first();
if (!$permission) {
    DB::table('permissions')->insert(['name' => 'report.view', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
    echo "✅ Created report.view permission\n";
    $permission = DB::table('permissions')->where('name', 'report.view')->first();
} else {
    echo "✅ report.view permission exists (id: {$permission->id})\n";
}

// Get Super Admin role
$superAdminRole = DB::table('roles')->where('name', 'Super Admin')->orWhere('name', 'SuperAdmin')->first();
if (!$superAdminRole) {
    $superAdminRole = DB::table('roles')->whereRaw("LOWER(name) LIKE '%super%'")->first();
}

echo "Super Admin role: " . ($superAdminRole ? "id={$superAdminRole->id}, name={$superAdminRole->name}" : "NOT FOUND") . "\n";

// Check all roles
echo "\n=== ALL ROLES ===\n";
$roles = DB::table('roles')->get(['id', 'name']);
foreach ($roles as $r) echo "  id={$r->id} name={$r->name}\n";

// Assign report.view to all admin roles
foreach ($roles as $role) {
    $exists = DB::table('role_has_permissions')
        ->where('permission_id', $permission->id)
        ->where('role_id', $role->id)
        ->exists();
    if (!$exists) {
        DB::table('role_has_permissions')->insert([
            'permission_id' => $permission->id,
            'role_id'       => $role->id,
        ]);
        echo "✅ Assigned report.view to role: {$role->name}\n";
    } else {
        echo "✅ report.view already assigned to: {$role->name}\n";
    }
}

// Clear permission cache
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
echo "\n✅ Permission cache cleared!\n";

echo "\n=== ALL PERMISSIONS ===\n";
$perms = DB::table('permissions')->get(['id','name']);
foreach ($perms as $p) echo "  {$p->name}\n";

echo "</pre>";
echo "<br><a href='/admin/reports' style='font-size:20px;padding:15px 30px;background:#3b82f6;color:white;border-radius:8px;text-decoration:none;'>📊 Go to Reports</a>";
