<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Final role cleanup — removes any roles that are NOT one of the canonical 4.
 * The correct roles are:
 *   Super Admin, Business Manager, Seller, Manufacturing Staff
 *
 * Any users previously assigned to removed roles are re-mapped:
 *   Admin, Branch Seller, Sales Staff      → Seller
 *   Factory Supervisor, Warehouse Staff,
 *   Manufacturing Manager, Manufacturing User → Manufacturing Staff
 *
 * After cleanup, runs PermissionSeeder to ensure all 4 roles have
 * the correct permission sets.
 */
return new class extends Migration
{
    private array $keepRoles = ['Super Admin', 'Business Manager', 'Seller', 'Manufacturing Staff'];

    private array $remapTo = [
        'Admin'                => 'Business Manager',
        'Branch Seller'        => 'Seller',
        'Sales Staff'          => 'Seller',
        'Factory Supervisor'   => 'Manufacturing Staff',
        'Warehouse Staff'      => 'Manufacturing Staff',
        'Manufacturing Manager'=> 'Manufacturing Staff',
        'Manufacturing User'   => 'Manufacturing Staff',
    ];

    public function up(): void
    {
        $now = now()->toDateTimeString();

        // Build keep-role id map
        $keepIds = DB::table('roles')
            ->whereIn('name', $this->keepRoles)
            ->pluck('id', 'name');

        // Re-map users and delete excess roles
        foreach ($this->remapTo as $removeName => $replaceName) {
            $removeRole  = DB::table('roles')->where('name', $removeName)->first();
            if (!$removeRole) continue;

            $replaceId = $keepIds[$replaceName] ?? null;
            if (!$replaceId) continue;

            // Move role_user rows
            $rows = DB::table('role_user')->where('role_id', $removeRole->id)->get();
            foreach ($rows as $row) {
                $exists = DB::table('role_user')
                    ->where('role_id', $replaceId)
                    ->where('user_id', $row->user_id)
                    ->where(fn($q) => $row->business_id
                        ? $q->where('business_id', $row->business_id)
                        : $q->whereNull('business_id'))
                    ->where(fn($q) => $row->branch_id
                        ? $q->where('branch_id', $row->branch_id)
                        : $q->whereNull('branch_id'))
                    ->exists();

                if (!$exists) {
                    DB::table('role_user')->insert([
                        'role_id'     => $replaceId,
                        'user_id'     => $row->user_id,
                        'business_id' => $row->business_id,
                        'branch_id'   => $row->branch_id,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);
                }
            }

            DB::table('role_user')->where('role_id', $removeRole->id)->delete();
            DB::table('permission_role')->where('role_id', $removeRole->id)->delete();
            DB::table('roles')->where('id', $removeRole->id)->delete();
        }

        // Re-sync permissions directly (no Artisan command context needed)
        $this->syncPermissions($keepIds, $now);
    }

    public function down(): void
    {
        // Not reversible — re-run FoundationSeeder + PermissionSeeder instead
    }

    // ── Inline permission sync (no Artisan context needed) ───────────────

    private function syncPermissions(\Illuminate\Support\Collection $keepIds, string $now): void
    {
        $allPermIds = DB::table('permissions')->pluck('id');

        // Super Admin gets everything
        $saId = $keepIds['Super Admin'] ?? null;
        if ($saId) {
            DB::table('permission_role')->where('role_id', $saId)->delete();
            $rows = $allPermIds->map(fn($id) => ['permission_id'=>$id,'role_id'=>$saId,'created_at'=>$now,'updated_at'=>$now])->toArray();
            foreach (array_chunk($rows, 200) as $chunk) DB::table('permission_role')->insert($chunk);
        }

        // Defined permission sets for the other 3 roles
        $sets = [
            'Business Manager' => [
                'business.view','business.update','branch.view','branch.create','branch.update',
                'product.view','product.create','product.update','product.delete',
                'category.view','category.create','category.update','category.delete',
                'inventory.view','inventory.update',
                'purchase.view','purchase.create','purchase.update','purchase.delete',
                'manufacturing.view','manufacturing.manage','manufacturing.produce',
                'order.view','order.create','order.update','order.process',
                'sale.view','sale.create','payment.view','payment.approve',
                'customer.view','customer.create','customer.update',
                'user.view','user.create','user.update',
                'staff.view','staff.create','staff.update','role.view',
                'content.view','content.create','content.update','content.delete',
                'report.view','audit-log.view',
            ],
            'Seller' => [
                'product.view','inventory.view',
                'order.view','order.create','order.update','order.process',
                'sale.view','sale.create','payment.view','payment.approve',
                'customer.view','customer.create','customer.update','report.view',
            ],
            'Manufacturing Staff' => [
                'product.view','category.view','inventory.view','inventory.update',
                'purchase.view','purchase.create',
                'manufacturing.view','manufacturing.manage','manufacturing.produce',
                'sale.view','sale.create','order.view','report.view',
            ],
        ];

        foreach ($sets as $roleName => $permNames) {
            $roleId = $keepIds[$roleName] ?? null;
            if (!$roleId) continue;

            $permIds = DB::table('permissions')->whereIn('name', $permNames)->pluck('id');
            DB::table('permission_role')->where('role_id', $roleId)->delete();
            $rows = $permIds->map(fn($id) => ['permission_id'=>$id,'role_id'=>$roleId,'created_at'=>$now,'updated_at'=>$now])->toArray();
            if ($rows) foreach (array_chunk($rows, 200) as $chunk) DB::table('permission_role')->insert($chunk);
        }
    }
};
