<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Location;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventory;
    private Location $location;
    private Product $product;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventory = app(InventoryService::class);

        $business = Business::create([
            'name'   => 'Test Business',
            'code'   => 'TEST',
            'status' => 'active',
        ]);

        $branch = Branch::create([
            'business_id' => $business->id,
            'name'        => 'Test Branch',
            'code'        => 'BR1',
            'status'      => 'active',
        ]);

        $this->location = Location::create([
            'branch_id' => $branch->id,
            'name'      => 'Main Warehouse',
            'code'      => 'WH1',
            'type'      => 'warehouse',
            'status'    => 'active',
        ]);

        // Need a category to create a product
        $category = \App\Models\Category::create([
            'business_id' => $business->id,
            'name'        => 'Test Category',
            'slug'        => 'test-category',
            'type'        => 'general',
        ]);

        $this->product = Product::withoutGlobalScopes()->create([
            'business_id'  => $business->id,
            'category_id'  => $category->id,
            'name'         => 'Test Product',
            'sku'          => 'TEST-001',
            'type'         => Product::TYPE_GENERAL,
            'selling_price'=> 10.00,
            'cost_price'   => 5.00,
            'reorder_point'=> 0,
            'status'       => Product::STATUS_ACTIVE,
        ]);

        $role = Role::create(['name' => 'Tester']);
        $this->user = User::create([
            'name'     => 'Test User',
            'email'    => 'test@test.com',
            'password' => bcrypt('password'),
        ]);
        $this->user->roles()->attach($role->id);
        $this->actingAs($this->user);
    }

    private function getBalance(int $locationId, int $productId): float
    {
        return (float) $this->inventory->getBalance($locationId, 'product', $productId);
    }

    /** @test */
    public function purchase_increases_stock(): void
    {
        $movement = $this->inventory->receivePurchase(
            $this->location->id,
            $this->product->id,
            100.0,
            5.0
        );

        $this->assertEquals('in', $movement->direction);
        $this->assertEquals(StockMovement::TYPE_PURCHASE, $movement->movement_type);
        $this->assertEquals(100.0, (float) $movement->balance_after);

        // Verify via dynamic balance
        $balance = $this->getBalance($this->location->id, $this->product->id);
        $this->assertEquals(100.0, $balance);
    }

    /** @test */
    public function sale_decreases_stock(): void
    {
        $this->inventory->receivePurchase($this->location->id, $this->product->id, 50.0, 5.0);

        $movement = $this->inventory->recordSale(
            $this->location->id, $this->product->id, 10.0
        );

        $this->assertEquals('out', $movement->direction);
        $this->assertEquals(40.0, (float) $movement->balance_after);

        $balance = $this->getBalance($this->location->id, $this->product->id);
        $this->assertEquals(40.0, $balance);
    }

    /** @test */
    public function negative_stock_is_prevented(): void
    {
        $this->inventory->receivePurchase($this->location->id, $this->product->id, 10.0, 5.0);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Insufficient stock/');

        $this->inventory->recordSale($this->location->id, $this->product->id, 99.0);
    }

    /** @test */
    public function transfer_moves_stock_between_locations(): void
    {
        $branch2 = Branch::create([
            'business_id' => $this->location->branch->business_id,
            'name'        => 'Branch 2',
            'code'        => 'BR2',
            'status'      => 'active',
        ]);

        $location2 = Location::create([
            'branch_id' => $branch2->id,
            'name'      => 'Warehouse 2',
            'code'      => 'WH2',
            'type'      => 'warehouse',
            'status'    => 'active',
        ]);

        $this->inventory->receivePurchase($this->location->id, $this->product->id, 100.0, 5.0);
        $this->inventory->transfer($this->location->id, $location2->id, 'product', $this->product->id, 30.0);

        $sourceBalance = $this->getBalance($this->location->id, $this->product->id);
        $destBalance   = $this->getBalance($location2->id, $this->product->id);

        $this->assertEquals(70.0, $sourceBalance);
        $this->assertEquals(30.0, $destBalance);

        // Verify both movement records exist
        $this->assertDatabaseHas('stock_movements', [
            'location_id'   => $this->location->id,
            'movement_type' => StockMovement::TYPE_TRANSFER_OUT,
            'item_id'       => $this->product->id,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'location_id'   => $location2->id,
            'movement_type' => StockMovement::TYPE_TRANSFER_IN,
            'item_id'       => $this->product->id,
        ]);
    }

    /** @test */
    public function transfer_is_blocked_when_source_has_insufficient_stock(): void
    {
        $location2 = Location::create([
            'branch_id' => $this->location->branch_id,
            'name'      => 'WH2',
            'code'      => 'WH2B',
            'type'      => 'warehouse',
            'status'    => 'active',
        ]);

        $this->inventory->receivePurchase($this->location->id, $this->product->id, 5.0, 5.0);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Insufficient stock at source/');

        $this->inventory->transfer($this->location->id, $location2->id, 'product', $this->product->id, 99.0);
    }

    /** @test */
    public function adjustment_increase_adds_stock(): void
    {
        $this->inventory->receivePurchase($this->location->id, $this->product->id, 20.0, 5.0);

        $this->inventory->applyAdjustment(
            $this->location->id, 'product', $this->product->id,
            'increase', 5.0, 'Stock count correction'
        );

        $balance = $this->getBalance($this->location->id, $this->product->id);
        $this->assertEquals(25.0, $balance);
    }

    /** @test */
    public function adjustment_decrease_reduces_stock(): void
    {
        $this->inventory->receivePurchase($this->location->id, $this->product->id, 20.0, 5.0);

        $this->inventory->applyAdjustment(
            $this->location->id, 'product', $this->product->id,
            'decrease', 3.0, 'Damaged items written off'
        );

        $balance = $this->getBalance($this->location->id, $this->product->id);
        $this->assertEquals(17.0, $balance);
    }

    /** @test */
    public function low_stock_alert_detects_correctly(): void
    {
        $this->inventory->receivePurchase($this->location->id, $this->product->id, 5.0, 5.0);

        // Set reorder_point higher than current stock directly on the product
        $this->product->update(['reorder_point' => 10.0]);

        $low = $this->inventory->getLowStockItems();
        $this->assertGreaterThan(0, $low->count());
    }

    /** @test */
    public function ledger_and_balance_reconcile(): void
    {
        $this->inventory->receivePurchase($this->location->id, $this->product->id, 100.0, 5.0);
        $this->inventory->recordSale($this->location->id, $this->product->id, 20.0);
        $this->inventory->applyAdjustment($this->location->id, 'product', $this->product->id, 'decrease', 5.0, 'Damaged');

        $result = $this->inventory->reconcileBalance($this->location->id, 'product', $this->product->id);

        $this->assertFalse($result['mismatch'], 'Balance mismatch detected — ledger and stock level are out of sync');
        $this->assertEquals(75.0, $result['recorded_balance']);
        $this->assertEquals(75.0, $result['calculated_balance']);
    }
}
