<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Consolidates the role table to exactly 4 business roles:
 *
 *   Super Admin          — platform owner, all permissions
 *   Business Manager     — manages a business
 *   Seller               — operates a branch (replaces Branch Seller, Sales Staff)
 *   Manufacturing Staff  — production workflow (replaces Factory Supervisor, Warehouse Staff)
 *
 * What this migration does:
 *   1. Renames "Branch Seller" → "Seller"
 *   2. Renames "Factory Supervisor" → "Manufacturing Staff"
 *   3. Re-assigns any role_user rows from removed roles to their replacements
 *   4. Removes the old roles: Admin, Warehouse Staff, Sales Staff
 *      (Manufacturing Manager + Manufacturing User from the previous migration
 *       are also removed if they exist — PermissionSeeder is the single source)
 *   5. Clears all permission_role rows — PermissionSeeder will re-sync them
 *
 * This migration is idempotent: safe to run on a fresh DB or an existing one.
 */
return new class extends Migration
{
    // Old name  =>  new name  (renames)
    private array $renames = [
        'Branch Seller'    => 'Seller',
        'Factory Supervisor' => 'Manufacturing Staff',
    ];

    // Roles to completely remove after re-assigning their users
    private array $removals = [
        'Admin',
        'Warehouse Staff',
        'Sales Staff',
        'Manufacturing Manager',   // may exist from prev migration attempt
        'Manufacturing User',      // may exist from prev migration attempt
    ];

    // For each removed role, which replacement role should its users receive?
    private array $removalMapping = [
        'Admin'               => 'Business Manager',
        'Warehouse Staff'     => 'Manufacturing Staff',
        'Sales Staff'         => 'Seller',
        'Manufacturing Manager' => 'Manufacturing Staff',
        'Manufacturing User'    => 'Manufacturing Staff',
    ];

    public function up(): void
    {
        $now = now()->toDateTimeString();

        // ── Step 1: Rename roles ──────────────────────────────────────────
        foreach ($this->renames as $oldName => $newName) {
            $old = DB::table('roles')->where('name', $oldName)->first();
            $new = DB::table('roles')->where('name', $newName)->first();

            if (!$old) {
                // Already renamed or never existed — nothing to do
                continue;
            }

            if ($new) {
                // Target role already exists — merge: re-point old role's users to new role
                $this->reassignUsers($old->id, $new->id, $now);
                DB::table('roles')->where('id', $old->id)->delete();
            } else {
                // Just rename in place
                DB::table('roles')->where('id', $old->id)->update([
                    'name'       => $newName,
                    'updated_at' => $now,
                ]);
            }
        }

        // ── Step 2: Remove unwanted roles ─────────────────────────────────
        foreach ($this->removals as $removeName) {
            $removeRole = DB::table('roles')->where('name', $removeName)->first();
            if (!$removeRole) {
                continue; // never existed — skip
            }

            // Find the replacement role
            $replacementName = $this->removalMapping[$removeName] ?? null;
            $replacementRole = $replacementName
                ? DB::table('roles')->where('name', $replacementName)->first()
                : null;

            if ($replacementRole) {
                $this->reassignUsers($removeRole->id, $replacementRole->id, $now);
            } else {
                // No replacement — just detach the users from this role
                DB::table('role_user')->where('role_id', $removeRole->id)->delete();
            }

            // Remove all permission assignments for this role
            DB::table('permission_role')->where('role_id', $removeRole->id)->delete();
            DB::table('roles')->where('id', $removeRole->id)->delete();
        }

        // ── Step 3: Clear all permission_role rows ────────────────────────
        // PermissionSeeder will re-sync the correct permissions for all roles.
        DB::table('permission_role')->truncate();
    }

    public function down(): void
    {
        // Reversing a role consolidation would require restoring deleted roles
        // and re-splitting user assignments — this is not safely reversible.
        // Re-run PermissionSeeder + UsersSeeder on a clean DB instead.
    }

    /**
     * Move all role_user rows from $fromRoleId to $toRoleId.
     * Skips duplicates (same user+business+branch already has the target role).
     */
    private function reassignUsers(int $fromRoleId, int $toRoleId, string $now): void
    {
        $rows = DB::table('role_user')->where('role_id', $fromRoleId)->get();

        foreach ($rows as $row) {
            // Check if user already has the target role with the same scope
            $exists = DB::table('role_user')
                ->where('role_id', $toRoleId)
                ->where('user_id', $row->user_id)
                ->where(function ($q) use ($row) {
                    $row->business_id
                        ? $q->where('business_id', $row->business_id)
                        : $q->whereNull('business_id');
                })
                ->where(function ($q) use ($row) {
                    $row->branch_id
                        ? $q->where('branch_id', $row->branch_id)
                        : $q->whereNull('branch_id');
                })
                ->exists();

            if (!$exists) {
                DB::table('role_user')->insert([
                    'role_id'     => $toRoleId,
                    'user_id'     => $row->user_id,
                    'business_id' => $row->business_id,
                    'branch_id'   => $row->branch_id,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }

        // Remove old assignments
        DB::table('role_user')->where('role_id', $fromRoleId)->delete();
    }
};
