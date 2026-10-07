<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the Manufacturing Manager and Manufacturing User roles together with
 * all new permissions required by the business restructure:
 *
 *   manufacturing.view     — view production orders / batches
 *   manufacturing.manage   — create & manage manufacturing orders and batches
 *   manufacturing.produce  — record material consumption, oil output, complete batch
 *   production.sell        — sell manufactured finished products (Manufacturing User)
 *   payment.approve        — manually approve customer payments (Branch Seller / Owner)
 *   order.process          — process and fulfil orders after payment approval
 *
 * Also assigns the correct permission sets to every operational role that
 * currently has 0 permissions:
 *
 *   Business Manager  — full business-level operational access
 *   Branch Seller     — order management + payment approval + inventory view
 *   Manufacturing Manager — manufacturing + inventory view
 *   Manufacturing User    — sell finished goods + inventory view
 *   Warehouse Staff   — inventory view and update
 *   Sales Staff       — order view + customer view + inventory view
 *   Factory Supervisor — manufacturing view + inventory view (read-only oversight)
 *
 * This migration is idempotent — safe to run multiple times.
 */
return new class extends Migration
{
    // ── All permissions in the system after this migration ──────────────
    private array $allPermissions = [
        // Organisation
        'business.view'   => ['desc' => 'View Businesses',          'group' => 'business'],
        'business.create' => ['desc' => 'Create Businesses',        'group' => 'business'],
        'business.update' => ['desc' => 'Update Businesses',        'group' => 'business'],
        'business.delete' => ['desc' => 'Delete Businesses',        'group' => 'business'],
        'branch.view'     => ['desc' => 'View Branches',            'group' => 'branch'],
        'branch.create'   => ['desc' => 'Create Branches',          'group' => 'branch'],
        'branch.update'   => ['desc' => 'Update Branches',          'group' => 'branch'],
        'branch.delete'   => ['desc' => 'Delete Branches',          'group' => 'branch'],
        // Catalog
        'product.view'    => ['desc' => 'View Products',            'group' => 'product'],
        'product.create'  => ['desc' => 'Create Products',          'group' => 'product'],
        'product.update'  => ['desc' => 'Update Products',          'group' => 'product'],
        'product.delete'  => ['desc' => 'Delete Products',          'group' => 'product'],
        'category.view'   => ['desc' => 'View Categories',          'group' => 'category'],
        'category.create' => ['desc' => 'Create Categories',        'group' => 'category'],
        'category.update' => ['desc' => 'Update Categories',        'group' => 'category'],
        'category.delete' => ['desc' => 'Delete Categories',        'group' => 'category'],
        // Inventory
        'inventory.view'  => ['desc' => 'View Inventory',           'group' => 'inventory'],
        'inventory.update'=> ['desc' => 'Update Inventory',         'group' => 'inventory'],
        // Purchases
        'purchase.view'   => ['desc' => 'View Purchases',           'group' => 'purchase'],
        'purchase.create' => ['desc' => 'Create Purchases',         'group' => 'purchase'],
        'purchase.update' => ['desc' => 'Update Purchases',         'group' => 'purchase'],
        'purchase.delete' => ['desc' => 'Delete Purchases',         'group' => 'purchase'],
        // Manufacturing
        'manufacturing.view'   => ['desc' => 'View Manufacturing Orders & Batches',     'group' => 'manufacturing'],
        'manufacturing.manage' => ['desc' => 'Create & Manage Manufacturing Orders',    'group' => 'manufacturing'],
        'manufacturing.produce'=> ['desc' => 'Record Production, Consumption & Output', 'group' => 'manufacturing'],
        'production.sell'      => ['desc' => 'Sell Manufactured Finished Products',     'group' => 'production'],
        // Sales
        'order.view'      => ['desc' => 'View Orders',              'group' => 'order'],
        'order.create'    => ['desc' => 'Create Orders',            'group' => 'order'],
        'order.update'    => ['desc' => 'Update Orders',            'group' => 'order'],
        'order.delete'    => ['desc' => 'Delete Orders',            'group' => 'order'],
        'order.process'   => ['desc' => 'Process & Fulfil Orders',  'group' => 'order'],
        // Payments
        'payment.view'    => ['desc' => 'View Payments',            'group' => 'payment'],
        'payment.approve' => ['desc' => 'Manually Approve Payments','group' => 'payment'],
        // CRM
        'customer.view'   => ['desc' => 'View Customers',           'group' => 'customer'],
        'customer.create' => ['desc' => 'Create Customers',         'group' => 'customer'],
        'customer.update' => ['desc' => 'Update Customers',         'group' => 'customer'],
        // Users / Staff
        'user.view'       => ['desc' => 'View Users',               'group' => 'user'],
        'user.create'     => ['desc' => 'Create Users',             'group' => 'user'],
        'user.update'     => ['desc' => 'Update Users',             'group' => 'user'],
        'user.delete'     => ['desc' => 'Delete Users',             'group' => 'user'],
        'staff.view'      => ['desc' => 'View Staff',               'group' => 'staff'],
        'staff.create'    => ['desc' => 'Create Staff',             'group' => 'staff'],
        'staff.update'    => ['desc' => 'Update Staff',             'group' => 'staff'],
        // Roles
        'role.view'       => ['desc' => 'View Roles',               'group' => 'role'],
        'role.create'     => ['desc' => 'Create Roles',             'group' => 'role'],
        'role.update'     => ['desc' => 'Update Roles',             'group' => 'role'],
        'role.delete'     => ['desc' => 'Delete Roles',             'group' => 'role'],
        // Content
        'content.view'    => ['desc' => 'View Content',             'group' => 'content'],
        'content.create'  => ['desc' => 'Create Content',           'group' => 'content'],
        'content.update'  => ['desc' => 'Update Content',           'group' => 'content'],
        'content.delete'  => ['desc' => 'Delete Content',           'group' => 'content'],
        // Reports
        'report.view'     => ['desc' => 'View Reports',             'group' => 'report'],
        // System
        'system.settings.view'   => ['desc' => 'View System Settings',   'group' => 'system'],
        'system.settings.update' => ['desc' => 'Update System Settings', 'group' => 'system'],
        // Audit
        'audit-log.view'  => ['desc' => 'View Audit Logs',          'group' => 'audit-log'],
    ];

    // ── Permission sets per role ─────────────────────────────────────────
    private array $rolePermissions = [

        // Super Admin — all permissions (synced separately)

        'Admin' => [
            'business.view',
            'branch.view', 'branch.update',
            'product.view', 'product.create', 'product.update',
            'category.view', 'category.create', 'category.update',
            'inventory.view', 'inventory.update',
            'purchase.view', 'purchase.create', 'purchase.update',
            'manufacturing.view', 'manufacturing.manage', 'manufacturing.produce',
            'production.sell',
            'order.view', 'order.create', 'order.update', 'order.process',
            'payment.view', 'payment.approve',
            'customer.view', 'customer.create', 'customer.update',
            'user.view', 'user.create', 'user.update',
            'staff.view', 'staff.create', 'staff.update',
            'content.view', 'content.create', 'content.update', 'content.delete',
            'report.view',
        ],

        'Business Manager' => [
            'business.view',
            'branch.view', 'branch.update',
            'product.view', 'product.create', 'product.update',
            'category.view', 'category.create', 'category.update',
            'inventory.view', 'inventory.update',
            'purchase.view', 'purchase.create',
            'manufacturing.view', 'manufacturing.manage', 'manufacturing.produce',
            'production.sell',
            'order.view', 'order.create', 'order.update', 'order.process',
            'payment.view', 'payment.approve',
            'customer.view', 'customer.create', 'customer.update',
            'staff.view', 'staff.create', 'staff.update',
            'report.view',
        ],

        'Branch Seller' => [
            'product.view',
            'inventory.view',
            'order.view', 'order.create', 'order.update', 'order.process',
            'payment.view', 'payment.approve',
            'customer.view', 'customer.create', 'customer.update',
        ],

        // The Factory Supervisor role is being kept as a read-only oversight role.
        // Full production authority is with Manufacturing Manager.
        'Factory Supervisor' => [
            'manufacturing.view',
            'inventory.view',
            'product.view',
            'report.view',
        ],

        'Manufacturing Manager' => [
            'product.view',
            'category.view',
            'inventory.view', 'inventory.update',
            'purchase.view', 'purchase.create',
            'manufacturing.view', 'manufacturing.manage', 'manufacturing.produce',
            'production.sell',
            'report.view',
        ],

        'Manufacturing User' => [
            'product.view',
            'inventory.view',
            'production.sell',
            'order.view',
        ],

        'Warehouse Staff' => [
            'inventory.view', 'inventory.update',
            'product.view',
            'purchase.view',
        ],

        'Sales Staff' => [
            'product.view',
            'inventory.view',
            'order.view', 'order.create', 'order.update',
            'payment.view',
            'customer.view', 'customer.create', 'customer.update',
        ],
    ];

    public function up(): void
    {
        // ── 1. Upsert all permissions ─────────────────────────────────────
        $now = now()->toDateTimeString();
        foreach ($this->allPermissions as $name => $meta) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                [
                    'description' => $meta['desc'],
                    'group'       => $meta['group'],
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]
            );
        }

        // ── 2. Build a name→id lookup ─────────────────────────────────────
        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_keys($this->allPermissions))
            ->pluck('id', 'name');

        // ── 3. Upsert roles and sync permissions ──────────────────────────
        $roleDescriptions = [
            'Admin'                  => 'Business administrator with broad operational access.',
            'Business Manager'       => 'Manages a specific business: products, purchases, staff, reports.',
            'Branch Seller'          => 'Operates a branch: receives orders, approves payments, processes sales.',
            'Manufacturing Manager'  => 'Manages the full production workflow: orders, batches, consumption, output.',
            'Manufacturing User'     => 'Sells manufactured finished products and manages authorised transfers.',
            'Factory Supervisor'     => 'Read-only oversight of manufacturing and inventory operations.',
            'Warehouse Staff'        => 'Manages physical stock: receives, counts, and adjusts inventory.',
            'Sales Staff'            => 'Handles customer orders and payments at a branch.',
        ];

        foreach ($this->rolePermissions as $roleName => $permNames) {
            // Create or update the role
            DB::table('roles')->updateOrInsert(
                ['name' => $roleName],
                [
                    'description' => $roleDescriptions[$roleName] ?? $roleName,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]
            );

            $role = DB::table('roles')->where('name', $roleName)->first();

            // Sync: remove old assignments for this role, re-insert
            DB::table('permission_role')->where('role_id', $role->id)->delete();

            $pivotRows = [];
            foreach ($permNames as $permName) {
                if (isset($permissionIds[$permName])) {
                    $pivotRows[] = [
                        'permission_id' => $permissionIds[$permName],
                        'role_id'       => $role->id,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }
            }

            if (!empty($pivotRows)) {
                DB::table('permission_role')->insert($pivotRows);
            }
        }

        // ── 4. Super Admin gets ALL permissions ───────────────────────────
        $superAdmin = DB::table('roles')->where('name', 'Super Admin')->first();
        if ($superAdmin) {
            DB::table('permission_role')->where('role_id', $superAdmin->id)->delete();
            $allIds = DB::table('permissions')->pluck('id');
            $pivotRows = $allIds->map(fn($id) => [
                'permission_id' => $id,
                'role_id'       => $superAdmin->id,
                'created_at'    => $now,
                'updated_at'    => $now,
            ])->toArray();
            DB::table('permission_role')->insert($pivotRows);
        }
    }

    public function down(): void
    {
        // Remove only the newly added roles and permissions — leave existing ones
        $newRoles = ['Manufacturing Manager', 'Manufacturing User'];
        foreach ($newRoles as $name) {
            $role = DB::table('roles')->where('name', $name)->first();
            if ($role) {
                DB::table('permission_role')->where('role_id', $role->id)->delete();
                DB::table('role_user')->where('role_id', $role->id)->delete();
                DB::table('roles')->where('id', $role->id)->delete();
            }
        }

        $newPermissions = [
            'manufacturing.view',
            'manufacturing.manage',
            'manufacturing.produce',
            'production.sell',
            'payment.approve',
            'order.process',
            'purchase.view',
            'purchase.create',
            'purchase.update',
            'purchase.delete',
        ];
        DB::table('permissions')->whereIn('name', $newPermissions)->delete();
    }
};
