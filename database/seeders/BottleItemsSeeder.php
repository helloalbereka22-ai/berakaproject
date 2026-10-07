<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Purchase;

class BottleItemsSeeder extends Seeder
{
    public function run(): void
    {
        // Get Al-Baraka business
        $business = Business::where('name', 'like', '%Al-Baraka%')->first();
        $branch = Branch::where('business_id', $business->id)->first();
        
        if (!$business || !$branch) {
            $this->command->error('Al-Baraka business or branch not found');
            return;
        }

        // Create Bottles category
        $bottleCategory = Category::firstOrCreate([
            'name' => 'Bottles',
            'business_id' => $business->id,
        ], [
            'type' => 'packing_material',
            'is_active' => true,
            'description' => 'Bottle containers for oil products',
            'sort_order' => 1,
        ]);

        $bottles = [
            ['name' => '100ml Bottle', 'sku' => 'BTL-100', 'cost_price' => 2.00, 'stock' => 1000],
            ['name' => '200ml Bottle', 'sku' => 'BTL-200', 'cost_price' => 3.50, 'stock' => 800],
            ['name' => '500ml Bottle', 'sku' => 'BTL-500', 'cost_price' => 5.00, 'stock' => 500],
            ['name' => '1L Bottle', 'sku' => 'BTL-1000', 'cost_price' => 8.00, 'stock' => 300],
        ];

        foreach ($bottles as $bottleData) {
            // Create product
            $product = Product::firstOrCreate([
                'sku' => $bottleData['sku'],
                'business_id' => $business->id,
            ], [
                'name' => $bottleData['name'],
                'category_id' => $bottleCategory->id,
                'type' => 'packing_material',
                'unit' => 'piece',
                'cost_price' => $bottleData['cost_price'],
                'selling_price' => 0,
                'status' => 'active',
                'description' => 'Bottle for oil packaging',
            ]);

            // Add initial stock via purchase
            Purchase::firstOrCreate([
                'business_id' => $business->id,
                'branch_id' => $branch->id,
                'product_id' => $product->id,
            ], [
                'quantity' => $bottleData['stock'],
                'unit_price' => $bottleData['cost_price'],
                'total_cost' => $bottleData['stock'] * $bottleData['cost_price'],
                'purchase_date' => now()->toDateString(),
                'status' => 'completed',
                'notes' => 'Initial stock for ' . $bottleData['name'],
            ]);

            $this->command->info("Created {$bottleData['name']} with stock {$bottleData['stock']}");
        }

        $this->command->info('Bottle items seeder completed successfully!');
    }
}