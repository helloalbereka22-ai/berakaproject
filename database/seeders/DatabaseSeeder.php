<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed order matters:
     * 1. Permissions & Roles    — must exist before users
     * 2. Identity               — creates Super Admin user + additional roles
     * 3. Foundation             — creates 2 businesses, branches, warehouses
     * 4. Users                  — creates Admin, Managers, Sales Staff
     * 5. Catalog                — sample products and categories
     * 6. Settings               — platform-wide contact/org settings
     */
    public function run(): void
    {
        $this->call([
            LanguageSeeder::class,      // must come first — no dependencies
            PermissionSeeder::class,
            IdentitySeeder::class,
            FoundationSeeder::class,
            UsersSeeder::class,
            CatalogSeeder::class,
            SettingsSeeder::class,
        ]);
    }
}
