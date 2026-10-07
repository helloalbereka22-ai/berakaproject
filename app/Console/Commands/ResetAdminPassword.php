<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;

class ResetAdminPassword extends Command
{
    protected $signature = 'app:reset-admin-password';
    protected $description = 'Resets the admin@albareka.com password and assigns Super Admin role';

    public function handle()
    {
        $user = User::firstOrNew(
            ['email' => 'admin@albareka.com']
        );
        $user->name = 'Admin User';
        $user->password = bcrypt('Zed!@123');
        $user->save();

        $role = Role::firstOrCreate(['name' => 'Super Admin']);
        $user->roles()->syncWithoutDetaching([$role->id]);

        $this->info("Successfully updated the password and assigned the Super Admin role to admin@albareka.com.");
    }
}
