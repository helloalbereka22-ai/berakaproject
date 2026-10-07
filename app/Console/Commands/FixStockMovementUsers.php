<?php

namespace App\Console\Commands;

use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixStockMovementUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock-movements:fix-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate created_by field for stock movements that have null created_by by matching against audit logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing stock movement user attribution...');

        // Find all stock movements with null created_by
        // Focus on production-related and internal movements (not purchases or sales which already have users)
        $movements = StockMovement::whereNull('created_by')
            ->whereIn('movement_type', [
                'production_consumption',
                'packing_consumption',
                'production_output',
                'distribution',
            ])
            ->orderByDesc('moved_at')
            ->get();

        $this->info("Found {$movements->count()} stock movements with missing created_by");

        $fixed = 0;
        $notFound = 0;

        foreach ($movements as $movement) {
            // Try to find a user login audit log around the time of this movement
            $auditLog = DB::table('audit_logs')
                ->join('users', 'audit_logs.user_id', '=', 'users.id')
                ->where('audit_logs.action', 'login')
                ->whereBetween('audit_logs.created_at', [
                    $movement->moved_at->copy()->subHours(24),
                    $movement->moved_at->copy()->addHours(1)
                ])
                ->orderByDesc('audit_logs.created_at')
                ->select('audit_logs.user_id', 'users.name')
                ->first();

            if ($auditLog) {
                $movement->update(['created_by' => $auditLog->user_id]);
                $this->line("✓ Movement {$movement->id} ({$movement->movement_type}) → User: {$auditLog->name}");
                $fixed++;
            } else {
                $this->line("✗ Movement {$movement->id} ({$movement->movement_type}) → No matching audit log found");
                $notFound++;
            }
        }

        $this->info("\n📊 Results:");
        $this->info("  ✓ Fixed: {$fixed}");
        $this->info("  ✗ Not found: {$notFound}");

        if ($notFound > 0) {
            $this->warn("  Note: {$notFound} movements could not be attributed to a user (will show as 'System')");
        }

        $this->info("\nDone!");
    }
}
