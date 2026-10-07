<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the remaining platform roles and ensures the Super Admin user exists.
 *
 * Roles created:
 *   - Business Manager
 *   - Branch Seller
 *   - Factory Supervisor
 *   - Warehouse Staff
 *   - Sales Staff
 *
 * (Super Admin and Admin roles are created by PermissionSeeder)
 *
 * Safe to re-run — uses updateOrCreate so no duplicates.
 */
class IdentitySeeder extends Seeder
{
    public function run(): void
    {
        // ── Additional Roles ──────────────────────────────────────────────
        $roles = [
            'Business Manager'  => 'Manages a specific business and all its branches.',
            'Branch Seller'     => 'Accesses and sells products available at their authorized branch.',
            'Factory Supervisor'=> 'Supervises manufacturing operations within the Oil Factory.',
            'Warehouse Staff'   => 'Handles stock and warehouse operations.',
            'Sales Staff'       => 'Handles point-of-sale and customer-facing sales.',
        ];

        foreach ($roles as $name => $description) {
            Role::updateOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
            $this->command->line("  <info>Role:</info> $name");
        }

        $this->command->info('Identity roles seeded.');
    }
}
