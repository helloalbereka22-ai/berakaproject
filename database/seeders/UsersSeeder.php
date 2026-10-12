<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Seeds demo staff users against the 4 business roles.
 *
 * ┌─────────────────────────────────────────┬──────────────────────┬──────────────────────────────┐
 * │ Email                                   │ Role                 │ Scope                        │
 * ├─────────────────────────────────────────┼──────────────────────┼──────────────────────────────┤
 * │ superadmin@albareka.com                 │ Super Admin          │ Platform-wide (no scope)     │
 * │ manager@albareka.com                    │ Business Manager     │ Both businesses              │
 * │ oilfactory.manager@albareka.com         │ Business Manager     │ Oil Factory business only    │
 * │ bookstore.manager@albareka.com          │ Business Manager     │ Bookstore business only      │
 * │ seller@albareka.com                     │ Seller               │ Main Factory branch          │
 * │ bookstore.seller@albareka.com           │ Seller               │ Bookstore Branch 1           │
 * │ manufacturing@albareka.com              │ Manufacturing Staff  │ Oil Factory business         │
 * └─────────────────────────────────────────┴──────────────────────┴──────────────────────────────┘
 *
 * Passwords: SuperAdmin@1234 / Manager@1234 / Seller@1234 / Manufacturing@1234
 * Safe to re-run — uses updateOrCreate, no duplicates.
 */
class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::pluck('id', 'name');

        // Abort early if PermissionSeeder has not run yet
        foreach (['Super Admin', 'Business Manager', 'Seller', 'Manufacturing Staff'] as $required) {
            if (!$roles->has($required)) {
                $this->command->error("Role '{$required}' not found. Run PermissionSeeder first.");
                return;
            }
        }

        // Load business/branch anchors (created by FoundationSeeder)
        $oilBusiness   = Business::where('code', 'OIL-FACTORY')->first();
        $bookBusiness  = Business::where('code', 'BOOKSTORE')->first();
        $factoryBranch = Branch::where('code', 'OIL-HQ')->first();
        $bookBranch1   = Branch::where('code', 'BOOK-BR1')->first();

        if (!$oilBusiness || !$bookBusiness) {
            $this->command->warn('Foundation businesses not found — run FoundationSeeder first.');
            $this->command->warn('Scoped users will be skipped.');
        }

        $this->command->newLine();
        $this->command->line('<comment>Seeding users...</comment>');

        // ── 1. Super Admin ────────────────────────────────────────────────
        $superAdmin = $this->upsertUser(
            email:    'superadmin@albareka.com',
            name:     'Super Administrator',
            password: 'SuperAdmin@1234',
        );
        // Super Admin has no business/branch scope — sees everything
        $this->attachRole($superAdmin, $roles['Super Admin']);
        $this->log($superAdmin->email, 'Super Admin', 'platform-wide');

        // ── 2. Platform Business Manager (both businesses) ────────────────
        $manager = $this->upsertUser(
            email:    'manager@albareka.com',
            name:     'General Manager',
            password: 'Manager@1234',
        );
        if ($oilBusiness) {
            // Assign to Oil Factory business AND to the main factory branch
            $this->attachRole($manager, $roles['Business Manager'], $oilBusiness->id, $factoryBranch?->id);
        }
        if ($bookBusiness) {
            // Assign to Bookstore business AND to bookstore branch 1
            $this->attachRole($manager, $roles['Business Manager'], $bookBusiness->id, $bookBranch1?->id);
        }
        $this->log($manager->email, 'Business Manager', 'Oil Factory (Main Branch) + Bookstore (Branch 1)');

        // ── 3. Oil Factory Business Manager ──────────────────────────────
        if ($oilBusiness) {
            $oilManager = $this->upsertUser(
                email:    'oilfactory.manager@albareka.com',
                name:     'Oil Factory Manager',
                password: 'Manager@1234',
            );
            $this->attachRole($oilManager, $roles['Business Manager'], $oilBusiness->id);
            $this->log($oilManager->email, 'Business Manager', 'Oil Factory');
        }

        // ── 4. Bookstore Business Manager ────────────────────────────────
        if ($bookBusiness) {
            $bookManager = $this->upsertUser(
                email:    'bookstore.manager@albareka.com',
                name:     'Bookstore Manager',
                password: 'Manager@1234',
            );
            $this->attachRole($bookManager, $roles['Business Manager'], $bookBusiness->id);
            $this->log($bookManager->email, 'Business Manager', 'Bookstore');
        }

        // ── 5. Factory Branch Seller ──────────────────────────────────────
        if ($oilBusiness && $factoryBranch) {
            $seller = $this->upsertUser(
                email:    'seller@albareka.com',
                name:     'Factory Seller',
                password: 'Seller@1234',
            );
            $this->attachRole($seller, $roles['Seller'], $oilBusiness->id, $factoryBranch->id);
            $this->log($seller->email, 'Seller', 'Main Factory branch');
        }

        // ── 6. Bookstore Branch Seller ────────────────────────────────────
        if ($bookBusiness && $bookBranch1) {
            $bookSeller = $this->upsertUser(
                email:    'bookstore.seller@albareka.com',
                name:     'Bookstore Seller',
                password: 'Seller@1234',
            );
            $this->attachRole($bookSeller, $roles['Seller'], $bookBusiness->id, $bookBranch1->id);
            $this->log($bookSeller->email, 'Seller', 'Bookstore Branch 1');
        }

        // ── 7. Manufacturing Staff ────────────────────────────────────────
        if ($oilBusiness) {
            $mfgStaff = $this->upsertUser(
                email:    'manufacturing@albareka.com',
                name:     'Manufacturing Staff',
                password: 'Manufacturing@1234',
            );
            $this->attachRole($mfgStaff, $roles['Manufacturing Staff'], $oilBusiness->id);
            $this->log($mfgStaff->email, 'Manufacturing Staff', 'Oil Factory');
        }

        $this->command->newLine();
        $this->command->info('Users seeded successfully.');
        $this->command->warn('Change all passwords before deploying to production.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function upsertUser(string $email, string $name, string $password): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => $password, 'status' => 'active']
        );
    }

    /**
     * Attach a role to a user with optional business/branch scope.
     * Skips silently if the exact same role+scope is already attached.
     */
    private function attachRole(
        User $user,
        int  $roleId,
        ?int $businessId = null,
        ?int $branchId   = null
    ): void {
        $exists = $user->roles()
            ->wherePivot('role_id', $roleId)
            ->when($businessId !== null,
                fn($q) => $q->where('role_user.business_id', $businessId),
                fn($q) => $q->whereNull('role_user.business_id')
            )
            ->when($branchId !== null,
                fn($q) => $q->where('role_user.branch_id', $branchId),
                fn($q) => $q->whereNull('role_user.branch_id')
            )
            ->exists();

        if (!$exists) {
            $user->roles()->attach($roleId, [
                'business_id' => $businessId,
                'branch_id'   => $branchId,
            ]);
        }

        $user->flushPermissionCache();
    }

    private function log(string $email, string $role, string $scope): void
    {
        $this->command->line(
            sprintf('  <info>%-42s</info> %-22s %s', $email, $role, $scope)
        );
    }
}
