<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\Location;
use App\Models\ManufacturingOrder;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\QualityCheck;
use App\Models\Role;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\ManufacturingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManufacturingServiceTest extends TestCase
{
    use RefreshDatabase;

    private ManufacturingService $service;
    private InventoryService     $inventory;
    private Business             $business;
    private Branch               $branch;
    private Location             $rawMatLocation;
    private Location             $finishedGoodsLocation;
    private Product              $rawMaterial;
    private Product              $packingMaterial;
    private Product              $finishedOil;
    private ManufacturingOrder   $order;
    private User                 $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventory = app(InventoryService::class);
        $this->service   = app(ManufacturingService::class);

        $this->business = Business::create(['name' => 'Test Factory', 'code' => 'FACT', 'status' => 'active']);
        $this->branch   = Branch::create(['business_id' => $this->business->id, 'name' => 'Main Factory', 'code' => 'MF1', 'status' => 'active']);

        $this->rawMatLocation = Location::create([
            'branch_id' => $this->branch->id, 'name' => 'Raw Mat Store', 'code' => 'RM1', 'type' => 'warehouse', 'status' => 'active',
        ]);
        $this->finishedGoodsLocation = Location::create([
            'branch_id' => $this->branch->id, 'name' => 'FG Store', 'code' => 'FG1', 'type' => 'warehouse', 'status' => 'active',
        ]);

        $rawCat  = Category::withoutGlobalScopes()->create(['business_id' => $this->business->id, 'name' => 'Sesame', 'slug' => 'sesame', 'type' => 'raw_material']);
        $packCat = Category::withoutGlobalScopes()->create(['business_id' => $this->business->id, 'name' => 'Packing', 'slug' => 'packing', 'type' => 'packing_material']);
        $oilCat  = Category::withoutGlobalScopes()->create(['business_id' => $this->business->id, 'name' => 'Oil', 'slug' => 'oil', 'type' => 'oil']);

        $this->rawMaterial = Product::withoutGlobalScopes()->create([
            'business_id' => $this->business->id, 'category_id' => $rawCat->id,
            'name' => 'Sesame Seeds', 'sku' => 'RM-001', 'type' => Product::TYPE_RAW_MATERIAL,
            'unit' => 'kg', 'cost_price' => 50.00, 'selling_price' => 0, 'status' => 'active',
        ]);

        $this->packingMaterial = Product::withoutGlobalScopes()->create([
            'business_id' => $this->business->id, 'category_id' => $packCat->id,
            'name' => 'Bottle 500ml', 'sku' => 'PKG-001', 'type' => Product::TYPE_PACKING_MATERIAL,
            'unit' => 'piece', 'cost_price' => 2.00, 'selling_price' => 0, 'status' => 'active',
        ]);

        $this->finishedOil = Product::withoutGlobalScopes()->create([
            'business_id' => $this->business->id, 'category_id' => $oilCat->id,
            'name' => 'Sesame Oil 500ml', 'sku' => 'OIL-001', 'type' => Product::TYPE_FINISHED_PRODUCT,
            'unit' => 'ml', 'package_size' => '500ml', 'cost_price' => 0, 'selling_price' => 0.15, 'status' => 'active',
        ]);

        $role = Role::firstOrCreate(['name' => 'Tester']);
        $this->user = User::create(['name' => 'Test', 'email' => 'test@factory.com', 'password' => bcrypt('password')]);
        $this->user->roles()->attach($role->id);
        $this->actingAs($this->user);

        // Seed raw materials and packing into inventory
        $this->inventory->receivePurchase($this->rawMatLocation->id, $this->rawMaterial->id, 200.0, 50.0);
        $this->inventory->receivePurchase($this->rawMatLocation->id, $this->packingMaterial->id, 1000.0, 2.0);

        $this->order = $this->service->createOrder([
            'business_id'     => $this->business->id,
            'branch_id'       => $this->branch->id,
            'product_id'      => $this->finishedOil->id,
            'package_size'    => '500ml',
            'planned_quantity'=> 10000,
        ]);
    }

    /** @test */
    public function it_opens_a_batch_with_unique_batch_number(): void
    {
        $batch = $this->service->openBatch($this->order, ['oil_unit' => 'ml']);

        $this->assertNotEmpty($batch->batch_number);
        $this->assertStringStartsWith('BATCH-', $batch->batch_number);
        $this->assertEquals(ProductionBatch::STATUS_OPEN, $batch->status);
    }

    /** @test */
    public function raw_material_consumption_deducts_stock(): void
    {
        $batch = $this->service->openBatch($this->order, []);

        $this->service->consumeRawMaterial($batch, $this->rawMatLocation->id, $this->rawMaterial->id, 50.0, 'LOT-001');

        // Stock should be 200 - 50 = 150
        $balance = $this->inventory->getBalance($this->rawMatLocation->id, 'product', $this->rawMaterial->id);
        $this->assertEquals(150.0, $balance);

        // StockMovement should exist
        $this->assertDatabaseHas('stock_movements', [
            'location_id'   => $this->rawMatLocation->id,
            'item_id'       => $this->rawMaterial->id,
            'movement_type' => StockMovement::TYPE_PRODUCTION_CONSUMPTION,
            'direction'     => 'out',
        ]);

        // Consumption record should exist
        $this->assertDatabaseHas('batch_material_consumption', [
            'production_batch_id' => $batch->id,
            'raw_material_id'     => $this->rawMaterial->id,
            'consumption_type'    => 'raw_material',
            'lot_number'          => 'LOT-001',
        ]);

        // Batch raw_material_cost should be updated
        $batch->refresh();
        $this->assertGreaterThan(0, (float) $batch->raw_material_cost);
    }

    /** @test */
    public function consumption_is_blocked_when_stock_insufficient(): void
    {
        $batch = $this->service->openBatch($this->order, []);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Insufficient stock/');

        $this->service->consumeRawMaterial($batch, $this->rawMatLocation->id, $this->rawMaterial->id, 9999.0);
    }

    /** @test */
    public function oil_yield_is_recorded_and_batch_moves_to_in_qc(): void
    {
        $batch = $this->service->openBatch($this->order, []);
        $this->service->consumeRawMaterial($batch, $this->rawMatLocation->id, $this->rawMaterial->id, 50.0);

        $batch = $this->service->recordOilOutput($batch, 35000.0, 'ml', $this->finishedGoodsLocation->id);

        $this->assertEquals(35000.0, (float) $batch->oil_quantity_produced);
        $this->assertEquals('ml', $batch->oil_unit);
        $this->assertEquals(ProductionBatch::STATUS_IN_QC, $batch->status);
    }

    /** @test */
    public function yield_percentage_is_calculated_correctly(): void
    {
        $batch = $this->service->openBatch($this->order, []);
        $this->service->consumeRawMaterial($batch, $this->rawMatLocation->id, $this->rawMaterial->id, 50.0);
        $batch = $this->service->recordOilOutput($batch, 35000.0, 'ml', $this->finishedGoodsLocation->id);

        // Yield = (35000ml / 1000 litres) / 50kg × 100 = 70%
        $yield = $batch->getYieldPercentage();
        $this->assertEquals(70.0, $yield);
    }

    /** @test */
    public function qc_approval_moves_batch_to_approved(): void
    {
        $batch = $this->service->openBatch($this->order, []);
        $this->service->consumeRawMaterial($batch, $this->rawMatLocation->id, $this->rawMaterial->id, 50.0);
        $batch = $this->service->recordOilOutput($batch, 35000.0, 'ml', $this->finishedGoodsLocation->id);

        $qc = $this->service->submitQualityCheck($batch, [
            'status'          => QualityCheck::STATUS_APPROVED,
            'inspection_date' => now()->toDateString(),
            'remarks'         => 'All good',
        ]);

        $batch->refresh();
        $this->assertEquals(ProductionBatch::STATUS_APPROVED, $batch->status);
        $this->assertEquals(QualityCheck::STATUS_APPROVED, $qc->status);
    }

    /** @test */
    public function qc_rejection_blocks_batch_from_completion(): void
    {
        $batch = $this->service->openBatch($this->order, []);
        $this->service->consumeRawMaterial($batch, $this->rawMatLocation->id, $this->rawMaterial->id, 50.0);
        $batch = $this->service->recordOilOutput($batch, 35000.0, 'ml', $this->finishedGoodsLocation->id);

        $this->service->submitQualityCheck($batch, [
            'status'          => QualityCheck::STATUS_REJECTED,
            'inspection_date' => now()->toDateString(),
            'rejected_reason' => 'Failed contamination test',
        ]);

        $batch->refresh();
        $this->assertEquals(ProductionBatch::STATUS_REJECTED, $batch->status);

        // Cannot complete a rejected batch
        $this->expectException(\RuntimeException::class);
        $this->service->completeBatch($batch, $this->finishedGoodsLocation->id, 0.15);
    }

    /** @test */
    public function batch_completion_uses_approved_profit_formula(): void
    {
        $batch = $this->service->openBatch($this->order, []);

        // Consume 50 kg raw material @ 50/kg = cost 2500
        $this->service->consumeRawMaterial($batch, $this->rawMatLocation->id, $this->rawMaterial->id, 50.0);

        // Record 35,000 ml oil output
        $batch = $this->service->recordOilOutput($batch, 35000.0, 'ml', $this->finishedGoodsLocation->id);

        // QC approve
        $this->service->submitQualityCheck($batch, ['status' => 'approved', 'inspection_date' => now()->toDateString()]);
        $batch->refresh();

        // Complete with packing materials (100 bottles @ 2.00 each = 200) and selling price 0.15/ml
        $batch = $this->service->completeBatch(
            $batch,
            $this->finishedGoodsLocation->id,
            0.15, // selling price per ml
            [[
                'location_id' => $this->rawMatLocation->id,
                'product_id'  => $this->packingMaterial->id,
                'quantity'    => 100.0,
            ]]
        );

        // Profit = (35000 × 0.15) - raw_material_cost - packing_cost
        // Revenue = 35000 × 0.15 = 5250
        // Raw material cost = 50 × 50 = 2500
        // Packing cost = 100 × 2 = 200
        // Profit = 5250 - 2500 - 200 = 2550
        $this->assertEquals(ProductionBatch::STATUS_COMPLETED, $batch->status);
        $this->assertEqualsWithDelta(5250.0,  (float) $batch->revenue, 0.01);
        $this->assertEqualsWithDelta(2500.0,  (float) $batch->raw_material_cost, 0.01);
        $this->assertEqualsWithDelta(200.0,   (float) $batch->packing_cost, 0.01);
        $this->assertEqualsWithDelta(2550.0,  (float) $batch->profit, 0.01);
    }

    /** @test */
    public function packing_materials_are_deducted_from_stock(): void
    {
        $batch = $this->service->openBatch($this->order, []);
        $this->service->consumeRawMaterial($batch, $this->rawMatLocation->id, $this->rawMaterial->id, 50.0);
        $batch = $this->service->recordOilOutput($batch, 35000.0, 'ml', $this->finishedGoodsLocation->id);
        $this->service->submitQualityCheck($batch, ['status' => 'approved', 'inspection_date' => now()->toDateString()]);
        $batch->refresh();

        $this->service->completeBatch($batch, $this->finishedGoodsLocation->id, 0.15, [[
            'location_id' => $this->rawMatLocation->id,
            'product_id'  => $this->packingMaterial->id,
            'quantity'    => 70.0,
        ]]);

        // 1000 - 70 = 930 bottles remaining
        $balance = $this->inventory->getBalance($this->rawMatLocation->id, 'product', $this->packingMaterial->id);
        $this->assertEquals(930.0, $balance);

        $this->assertDatabaseHas('batch_material_consumption', [
            'production_batch_id' => $batch->id,
            'raw_material_id'     => $this->packingMaterial->id,
            'consumption_type'    => 'packing_material',
        ]);
    }

    /** @test */
    public function finished_goods_are_added_to_warehouse_after_completion(): void
    {
        $batch = $this->service->openBatch($this->order, []);
        $this->service->consumeRawMaterial($batch, $this->rawMatLocation->id, $this->rawMaterial->id, 50.0);
        $batch = $this->service->recordOilOutput($batch, 35000.0, 'ml', $this->finishedGoodsLocation->id);
        $this->service->submitQualityCheck($batch, ['status' => 'approved', 'inspection_date' => now()->toDateString()]);
        $batch->refresh();

        $this->service->completeBatch($batch, $this->finishedGoodsLocation->id, 0.15);

        // Finished goods stock should have 35,000 ml
        $balance = $this->inventory->getBalance($this->finishedGoodsLocation->id, 'product', $this->finishedOil->id);
        $this->assertEquals(35000.0, $balance);

        // FinishedGood record should exist
        $this->assertDatabaseHas('finished_goods', [
            'production_batch_id' => $batch->id,
            'product_id'          => $this->finishedOil->id,
            'quantity'            => 35000.0,
        ]);
    }

    /** @test */
    public function traceability_returns_complete_batch_data(): void
    {
        $batch = $this->service->openBatch($this->order, []);
        $this->service->consumeRawMaterial($batch, $this->rawMatLocation->id, $this->rawMaterial->id, 50.0);
        $batch = $this->service->recordOilOutput($batch, 35000.0, 'ml', $this->finishedGoodsLocation->id);
        $this->service->submitQualityCheck($batch, ['status' => 'approved', 'inspection_date' => now()->toDateString()]);
        $batch->refresh();
        $this->service->completeBatch($batch, $this->finishedGoodsLocation->id, 0.15);

        $trace = $this->service->getTraceability($batch->fresh());

        $this->assertArrayHasKey('batch_number',           $trace);
        $this->assertArrayHasKey('raw_materials',          $trace);
        $this->assertArrayHasKey('total_raw_material_kg',  $trace);
        $this->assertArrayHasKey('oil_litres',             $trace);
        $this->assertArrayHasKey('yield_percentage',       $trace);
        $this->assertArrayHasKey('total_raw_material_cost',$trace);
        $this->assertArrayHasKey('total_packing_cost',     $trace);
        $this->assertArrayHasKey('revenue',                $trace);
        $this->assertArrayHasKey('profit',                 $trace);
        $this->assertArrayHasKey('qc_status',              $trace);
        $this->assertArrayHasKey('finished_quantity',      $trace);

        $this->assertEquals(50.0,  $trace['total_raw_material_kg']);
        $this->assertEquals(35.0,  $trace['oil_litres']);
        $this->assertEquals(70.0,  $trace['yield_percentage']);
        $this->assertEquals('approved', $trace['qc_status']);
    }

    /** @test */
    public function yield_report_groups_by_raw_material_category(): void
    {
        // Complete a batch
        $batch = $this->service->openBatch($this->order, []);
        $this->service->consumeRawMaterial($batch, $this->rawMatLocation->id, $this->rawMaterial->id, 50.0);
        $batch = $this->service->recordOilOutput($batch, 35000.0, 'ml', $this->finishedGoodsLocation->id);
        $this->service->submitQualityCheck($batch, ['status' => 'approved', 'inspection_date' => now()->toDateString()]);
        $batch->refresh();
        $this->service->completeBatch($batch, $this->finishedGoodsLocation->id, 0.15);

        $report = $this->service->getYieldReportByCategory($this->business->id);

        $this->assertIsArray($report);
        $this->assertNotEmpty($report);
        $this->assertArrayHasKey('category_name',    $report[0]);
        $this->assertArrayHasKey('total_raw_kg',     $report[0]);
        $this->assertArrayHasKey('total_oil_litres', $report[0]);
        $this->assertArrayHasKey('avg_yield_pct',    $report[0]);
        $this->assertArrayHasKey('total_profit',     $report[0]);
    }
}
