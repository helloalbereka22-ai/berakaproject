<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Defines the 4 business roles and their permission sets.
 *
 * ROLES:
 *   Super Admin         — platform owner, unrestricted access to everything
 *   Business Manager    — manages a business: products, purchases, staff, reports
 *   Seller              — operates a branch: orders, payment approval, sales
 *   Manufacturing Staff — full production workflow: orders, batches, consumption, output, sell
 *
 * Safe to re-run — uses updateOrCreate, syncs permissions each run.
 */
class PermissionSeeder extends Seeder
{
    // ── All permissions in the system ────────────────────────────────────
    // Format: 'permission.name' => ['desc' => '...', 'group' => '...']
    private array $permissions = [

        // Organisation
        'business.view'          => ['desc' => 'View Businesses',                    'group' => 'business'],
        'business.create'        => ['desc' => 'Create Businesses',                  'group' => 'business'],
        'business.update'        => ['desc' => 'Update Businesses',                  'group' => 'business'],
        'business.delete'        => ['desc' => 'Delete Businesses',                  'group' => 'business'],

        'branch.view'            => ['desc' => 'View Branches',                      'group' => 'branch'],
        'branch.create'          => ['desc' => 'Create Branches',                    'group' => 'branch'],
        'branch.update'          => ['desc' => 'Update Branches',                    'group' => 'branch'],
        'branch.delete'          => ['desc' => 'Delete Branches',                    'group' => 'branch'],

        // Catalog
        'product.view'           => ['desc' => 'View Products',                      'group' => 'product'],
        'product.create'         => ['desc' => 'Create Products',                    'group' => 'product'],
        'product.update'         => ['desc' => 'Update Products',                    'group' => 'product'],
        'product.delete'         => ['desc' => 'Delete Products',                    'group' => 'product'],

        'category.view'          => ['desc' => 'View Categories',                    'group' => 'category'],
        'category.create'        => ['desc' => 'Create Categories',                  'group' => 'category'],
        'category.update'        => ['desc' => 'Update Categories',                  'group' => 'category'],
        'category.delete'        => ['desc' => 'Delete Categories',                  'group' => 'category'],

        // Inventory
        'inventory.view'         => ['desc' => 'View Inventory & Stock Levels',      'group' => 'inventory'],
        'inventory.update'       => ['desc' => 'Adjust & Update Inventory',          'group' => 'inventory'],

        // Purchases
        'purchase.view'          => ['desc' => 'View Purchases',                     'group' => 'purchase'],
        'purchase.create'        => ['desc' => 'Create Purchases',                   'group' => 'purchase'],
        'purchase.update'        => ['desc' => 'Update Purchases',                   'group' => 'purchase'],
        'purchase.delete'        => ['desc' => 'Delete Purchases',                   'group' => 'purchase'],

        // Manufacturing
        'manufacturing.view'     => ['desc' => 'View Manufacturing Orders & Batches', 'group' => 'manufacturing'],
        'manufacturing.manage'   => ['desc' => 'Create & Manage Manufacturing Orders','group' => 'manufacturing'],
        'manufacturing.produce'  => ['desc' => 'Record Consumption & Production Output','group' => 'manufacturing'],

        // Sales
        'order.view'             => ['desc' => 'View Orders',                        'group' => 'order'],
        'order.create'           => ['desc' => 'Create Orders',                      'group' => 'order'],
        'order.update'           => ['desc' => 'Update Orders',                      'group' => 'order'],
        'order.delete'           => ['desc' => 'Delete Orders',                      'group' => 'order'],
        'order.process'          => ['desc' => 'Process & Fulfil Orders',            'group' => 'order'],

        'sale.create'            => ['desc' => 'Create Direct / POS Sales',          'group' => 'sale'],
        'sale.view'              => ['desc' => 'View Direct / POS Sales',            'group' => 'sale'],

        // Payments
        'payment.view'           => ['desc' => 'View Payments',                      'group' => 'payment'],
        'payment.approve'        => ['desc' => 'Manually Approve Customer Payments', 'group' => 'payment'],

        // CRM
        'customer.view'          => ['desc' => 'View Customers',                     'group' => 'customer'],
        'customer.create'        => ['desc' => 'Create Customers',                   'group' => 'customer'],
        'customer.update'        => ['desc' => 'Update Customers',                   'group' => 'customer'],

        // Users & Staff
        'user.view'              => ['desc' => 'View Users',                         'group' => 'user'],
        'user.create'            => ['desc' => 'Create Users',                       'group' => 'user'],
        'user.update'            => ['desc' => 'Update Users',                       'group' => 'user'],
        'user.delete'            => ['desc' => 'Delete Users',                       'group' => 'user'],

        'staff.view'             => ['desc' => 'View Staff',                         'group' => 'staff'],
        'staff.create'           => ['desc' => 'Create Staff',                       'group' => 'staff'],
        'staff.update'           => ['desc' => 'Update Staff',                       'group' => 'staff'],

        // Roles
        'role.view'              => ['desc' => 'View Roles & Permissions',           'group' => 'role'],
        'role.create'            => ['desc' => 'Create Roles',                       'group' => 'role'],
        'role.update'            => ['desc' => 'Update Roles',                       'group' => 'role'],
        'role.delete'            => ['desc' => 'Delete Roles',                       'group' => 'role'],

        // Content / CMS
        'content.view'           => ['desc' => 'View CMS Content',                   'group' => 'content'],
        'content.create'         => ['desc' => 'Create CMS Content',                 'group' => 'content'],
        'content.update'         => ['desc' => 'Update CMS Content',                 'group' => 'content'],
        'content.delete'         => ['desc' => 'Delete CMS Content',                 'group' => 'content'],

        // Reports
        'report.view'            => ['desc' => 'View Reports & Analytics',           'group' => 'report'],

        // System
        'system.settings.view'   => ['desc' => 'View System Settings',              'group' => 'system'],
        'system.settings.update' => ['desc' => 'Update System Settings',            'group' => 'system'],

        // Audit
        'audit-log.view'         => ['desc' => 'View Audit Logs',                   'group' => 'audit-log'],
    ];

    // ── Permission sets per role ─────────────────────────────────────────
    private array $roleDefinitions = [

        'Super Admin' => [
            'description' => 'Platform owner with unrestricted access to all businesses and features.',
            'permissions'  => '*', // all
        ],

        'Business Manager' => [
            'description' => 'Manages a specific business: products, purchases, staff, branches, and reports.',
            'permissions'  => [
                'business.view', 'business.update',
                'branch.view', 'branch.create', 'branch.update',
                'product.view', 'product.create', 'product.update', 'product.delete',
                'category.view', 'category.create', 'category.update', 'category.delete',
                'inventory.view', 'inventory.update',
                'purchase.view', 'purchase.create', 'purchase.update', 'purchase.delete',
                'manufacturing.view', 'manufacturing.manage', 'manufacturing.produce',
                'order.view', 'order.create', 'order.update', 'order.process',
                'sale.view', 'sale.create',
                'payment.view', 'payment.approve',
                'customer.view', 'customer.create', 'customer.update',
                'user.view', 'user.create', 'user.update',
                'staff.view', 'staff.create', 'staff.update',
                'role.view',
                'content.view', 'content.create', 'content.update', 'content.delete',
                'report.view',
                'audit-log.view',
            ],
        ],

        'Seller' => [
            'description' => 'Operates an assigned branch: receives orders, approves payments, processes sales.',
            'permissions'  => [
                'product.view',
                'inventory.view',
                'order.view', 'order.create', 'order.update', 'order.process',
                'sale.view', 'sale.create',
                'payment.view', 'payment.approve',
                'customer.view', 'customer.create', 'customer.update',
                'report.view',
            ],
        ],

        'Manufacturing Staff' => [
            'description' => 'Runs the full production workflow: manufacturing orders, batches, consumption, output, and sales of finished goods.',
            'permissions'  => [
                'product.view',
                'category.view',
                'inventory.view', 'inventory.update',
                'purchase.view', 'purchase.create',
                'manufacturing.view', 'manufacturing.manage', 'manufacturing.produce',
                'sale.view', 'sale.create',
                'order.view',
                'report.view',
            ],
        ],
    ];

    public function run(): void
    {
        $this->command->info('Seeding permissions and roles...');

        // 1. Upsert all permissions
        $permissionMap = []; // name => Permission model
        foreach ($this->permissions as $name => $meta) {
            $permissionMap[$name] = Permission::updateOrCreate(
                ['name' => $name],
                ['description' => $meta['desc'], 'group' => $meta['group']]
            );
        }
        $this->command->line('  ' . count($permissionMap) . ' permissions upserted.');

        $allPermissionIds = collect($permissionMap)->pluck('id')->values()->toArray();

        // 2. Upsert roles and sync permissions
        foreach ($this->roleDefinitions as $roleName => $def) {
            $role = Role::updateOrCreate(
                ['name' => $roleName],
                ['description' => $def['description']]
            );

            if ($def['permissions'] === '*') {
                $role->permissions()->sync($allPermissionIds);
                $this->command->line("  <info>{$roleName}</info> → ALL " . count($allPermissionIds) . ' permissions');
            } else {
                $ids = collect($def['permissions'])
                    ->map(fn($perm) => $permissionMap[$perm]->id ?? null)
                    ->filter()
                    ->values()
                    ->toArray();

                $role->permissions()->sync($ids);
                $this->command->line("  <info>{$roleName}</info> → " . count($ids) . ' permissions');
            }
        }

        $this->command->info('Done. ' . count($this->roleDefinitions) . ' roles seeded.');
    }
}
