<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This migration cleans up products where manufacturing_added_at was incorrectly set
        // We'll reset it only for today's actually manufactured items
        
        // Get today's date
        $today = now()->toDateString();
        
        // Get all finished/bottle products with metadata
        $products = \App\Models\Product::whereIn('type', ['finished_product', 'bottle_product'])
            ->where('status', 'active')
            ->whereNotNull('metadata')
            ->get();
        
        foreach ($products as $product) {
            $meta = $product->metadata ?? [];
            
            // Check if this product has manufacturing_daily_qty set
            // If it has manufacturing_daily_qty but manufacturing_added_at is not today,
            // it means it was a previous day's manufacture and shouldn't be shown today
            
            if (isset($meta['manufacturing_added_at'])) {
                try {
                    $addedDate = \Carbon\Carbon::parse($meta['manufacturing_added_at'])->toDateString();
                    
                    // If the date is not today and there's a daily_qty, it's leftover data
                    if ($addedDate !== $today && isset($meta['manufacturing_daily_qty'])) {
                        // Only keep manufacturing_daily_qty for items actually added today
                        // For older items, remove or clear the daily_qty
                        if ($addedDate < $today) {
                            // This is an old product, remove manufacturing_daily_qty
                            unset($meta['manufacturing_daily_qty']);
                            $product->metadata = $meta;
                            $product->save();
                        }
                    }
                } catch (\Exception $e) {
                    // Skip if date parsing fails
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed
    }
};
