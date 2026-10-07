<?php

namespace Tests\Feature;

use App\Models\BatchReconciliation;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\Role;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\ManufacturingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    private ManufacturingService $service;
    private InventoryService     $inventory;
    private Business             $business;
    private Branch               $branch;
    private Location             $rawLoc;
    private Location             $fgLoc;
    private Location             $destLoc;
    private Branch               $destBranch;
    private Product              $rawMaterial;
    private Product              $finishedOil;
    private ProductionBatch      $completedBatch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventory = app(InventoryService::class);
        $this->service   = app(ManufacturingService::class);

        $this->business    = Business::create(['name' => 'Factory', 'code' => 'FAC', 'status' => 'active']);
        $this->branch      = Branch::create(['business_id' => $this->business->id, 'name' => 'Main Factory', 'code' => 'MF1', 'status' => 'active']);
        $this->destBranch  = Branch::create(['business_id' => $this->business->id, 'name' => 'Bazaar', 'code' => 'BAZ', 'status' => 'active']);

        $this->rawLoc  = Location::create(['branch_id' => $this->branch->id,     'name' => 'RM Store', 'code' => 'RM1', 'type' => 'warehouse', 'status' => 'active']);
        $this->fgLoc   = Location::create(['branch_id' => $this->branch->id,     'name' => 'FG Store', 'code' => 'FG1', 'type' => 'warehouse', 'status' => 'active']);
        $this->destLoc = Location::create(['branch_id' => $this->destBranch->id, 'name' => 'Bazaar WH','code' => 'BZ1', 'type' => 'warehouse', 'status' => 'active']);

        $rawCat  = Category::withoutGlobalScopes()->create(['business_id' => $this->business->id, 'name' => 'Sesame', 'slug' => 'sesame-t', 'type' => 'raw_material']);
        $oilCat  = Category::withoutGlobalScopes()->create(['business_id' => $this->business->id, 'name' => 'Oil',    'slug' => 'oil-t',    'type' => 'oil']);

        $this->rawMaterial = Product::withoutGlobalScopes()->create([
            'business_id' => $this->business->id, 'category_id' => $rawCat->id,
            'name' => 'Sesame', 'sku' => 'RM-T01', 'type' => Product::TYPE_RAW_MATERIAL,
            'unit' => 'kg', 'cost_price' => 50.0, 'selling_price' => 0, 'status' => 'active',
        ]);
        $this->finishedOil = Product::withoutGlobalScopes()->create([
            'business_id' => $this->business->id, 'category_id' => $oilCat->id,
            'name' => 'Sesame Oil 500ml', 'sku' => 'OIL-T01', 'type' => Product::TYPE_FINISHED_PRODUCT,
            'unit' => 'ml', 'selling_price' => 0.15, 'cost_price' => 0, 'status' => 'active',
        ]);

        $role = Role::firstOrCreate(['name' => 'Tester']);
        $user = User::create(['name' => 'Test', 'email' => 'tracetest@test.com', 'password' => bcrypt('p')]);
        $user->roles()->attach($role->id);
        $this->actingAs($user);

        // Seed inventory
        $this->inventory->receivePurchase($this->rawLoc->id, $this->rawMaterial->id, 200.0, 50.0);

        // Run a complete batch lifecycle
        $order = $this->service->createOrder([
            'business_id' => $this->business->id, 'branch_id' => $this->branch->id,
            'product_id'  => $this->finishedOil->id, 'planned_quantity' => 10000, 'package_size' => '500ml',
        ]);
        $batch = $this->service->openBatch($order, ['oil_unit' => 'ml']);
        $this->service->consumeRawMaterial($batch, $this->rawLoc->id, $this->rawMaterial->id, 50.0, 'LOT-001');
        $batch = $this->service->recordOilOutput($batch, 35000.0, 'ml', $this->fgLoc->id);
        $this->service->submitQualityCheck($batch, ['status' => 'approved', 'inspection_date' => now()->toDateString()]);
        $batch->refresh();
        $this->completedBatch = $this->service->completeBatch($batch, $this->fgLoc->id, 0.15);
    }

    /** @test */
    public function traceability_shows_full_lifecycle(): void
    {
        $trace = $this->service->getTraceability($this->completedBatch->fresh());

        // Batch identity
        $this->assertEquals($this->completedBatch->batch_number, $trace['batch_number']);
        $this->assertEquals('Sesame Oil 500ml', $trace['product']);
        $this->assertEquals('Main Factory', $trace['factory']);

        // Raw materials with lot number
        $this->assertCount(1, $trace['raw_materials']);
        $this->assertEquals('LOT-001', $trace['raw_materials'][0]['lot_number']);
        $this->assertEquals(50.0, $trace['raw_materials'][0]['quantity_kg']);
        $this->assertEquals(50.0, $trace['raw_materials'][0]['unit_cost']);

        // Oil output
        $this->assertEquals(35000.0, $trace['oil_ml']);
        $this->assertEquals(70.0, $trace['yield_percentage']);

        // QC
        $this->assertEquals('approved', $trace['qc_status']);

        // Financials — no packing materials in this batch, profit = revenue - raw_cost
        // Profit = (35000 × 0.15) − (50 × 50) − 0 = 5250 − 2500 = 2750
        $this->assertEqualsWithDelta(5250.0, $trace['revenue'], 0.01);
        $this->assertEqualsWithDelta(2750.0, $trace['profit'], 0.01);

        // Quantities match
        $this->assertEquals(35000.0, $trace['finished_quantity']);
        $this->assertEquals(0.0, $trace['sold_quantity']);
        $this->assertEquals(0.0, $trace['distributed_quantity']);
        $this->assertEquals(35000.0, $trace['remaining_quantity']);
    }

    /** @test */
    public function distribution_records_correctly_in_traceability(): void
    {
        $this->service->distribute(
            $this->completedBatch,
            $this->fgLoc->id,
            $this->destBranch->id,
            $this->destLoc->id,
            10000.0,
            'Bazaar delivery'
        );

        $trace = $this->service->getTraceability($this->completedBatch->fresh());

        $this->assertCount(1, $trace['distributions']);
        $this->assertEquals(10000.0, $trace['distributions'][0]['quantity_sent']);
        $this->assertEquals('Bazaar', $trace['distributions'][0]['destination_branch']);
        $this->assertEquals(10000.0, $trace['distributed_quantity']);
        $this->assertEquals(25000.0, $trace['remaining_quantity']);

        // Source stock should decrease by 10000
        $srcBalance = $this->inventory->getBalance($this->fgLoc->id, 'product', $this->finishedOil->id);
        $this->assertEquals(25000.0, $srcBalance);

        // Destination stock should increase by 10000
        $dstBalance = $this->inventory->getBalance($this->destLoc->id, 'product', $this->finishedOil->id);
        $this->assertEquals(10000.0, $dstBalance);
    }

    /** @test */
    public function reconciliation_is_clean_when_quantities_match(): void
    {
        $rec = $this->service->reconcileBatch($this->completedBatch->fresh());

        $this->assertFalse($rec->has_mismatch);
        $this->assertEquals(BatchReconciliation::STATUS_CLEAN, $rec->status);
        $this->assertEquals(0.0, (float) $rec->variance);
        $this->assertEquals(35000.0, (float) $rec->produced_quantity);
    }

    /** @test */
    public function reconciliation_flags_mismatch_without_correcting(): void
    {
        // Manually tamper with finished_good qty to simulate discrepancy
        $fg = $this->completedBatch->finishedGood;
        $fg->update(['quantity' => 40000.0]); // Fake: says we made 40000 but only 35000 in stock

        $rec = $this->service->reconcileBatch($this->completedBatch->fresh());

        $this->assertTrue($rec->has_mismatch);
        $this->assertEquals(BatchReconciliation::STATUS_MISMATCH, $rec->status);
        $this->assertNotEquals(0.0, (float) $rec->variance);

        // Stock must NOT be changed — do not silently correct
        $stock = $this->inventory->getBalance($this->fgLoc->id, 'product', $this->finishedOil->id);
        $this->assertEquals(35000.0, $stock, 'Reconciliation must NOT silently correct the stock');

        // Restore
        $fg->update(['quantity' => 35000.0]);
    }

    /** @test */
    public function mismatch_can_be_flagged_for_investigation(): void
    {
        $fg = $this->completedBatch->finishedGood;
        $fg->update(['quantity' => 40000.0]);

        $rec = $this->service->reconcileBatch($this->completedBatch->fresh());
        $this->assertTrue($rec->has_mismatch);

        $rec = $this->service->flagForInvestigation($rec, 'Discrepancy under investigation by warehouse manager.');
        $this->assertEquals(BatchReconciliation::STATUS_INVESTIGATING, $rec->status);
        $this->assertNotEmpty($rec->investigation_notes);

        $fg->update(['quantity' => 35000.0]);
    }

    /** @test */
    public function mismatch_can_be_resolved_with_notes(): void
    {
        $fg = $this->completedBatch->finishedGood;
        $fg->update(['quantity' => 40000.0]);

        $rec = $this->service->reconcileBatch($this->completedBatch->fresh());
        $rec = $this->service->flagForInvestigation($rec, 'Started investigation.');
        $rec = $this->service->resolveReconciliation($rec, 'Data entry error confirmed — corrected manually.');

        $this->assertEquals(BatchReconciliation::STATUS_RESOLVED, $rec->status);
        $this->assertStringContainsString('Resolved', $rec->investigation_notes);

        $fg->update(['quantity' => 35000.0]);
    }

    /** @test */
    public function return_is_recorded_and_reflected_in_traceability(): void
    {
        $this->service->recordReturn($this->completedBatch, $this->fgLoc->id, 500.0, 'Damaged packaging');

        $trace = $this->service->getTraceability($this->completedBatch->fresh());

        $this->assertCount(1, $trace['returns']);
        $this->assertEquals(500.0, $trace['returns'][0]['quantity']);
        $this->assertEquals('approved', $trace['returns'][0]['status']);
        $this->assertEquals(500.0, $trace['returned_quantity']);
        // Remaining = 35000 - 0 dist - 0 sold + 500 returned = 35500
        $this->assertEquals(35500.0, $trace['remaining_quantity']);
    }

    /** @test */
    public function batch_comparison_returns_correct_metrics(): void
    {
        // Create a second batch for comparison
        $order2 = $this->service->createOrder([
            'business_id' => $this->business->id, 'branch_id' => $this->branch->id,
            'product_id'  => $this->finishedOil->id, 'planned_quantity' => 10000,
        ]);
        $batch2 = $this->service->openBatch($order2, ['oil_unit' => 'ml']);
        $this->service->consumeRawMaterial($batch2, $this->rawLoc->id, $this->rawMaterial->id, 40.0);
        $batch2 = $this->service->recordOilOutput($batch2, 26000.0, 'ml', $this->fgLoc->id);
        $this->service->submitQualityCheck($batch2, ['status' => 'approved', 'inspection_date' => now()->toDateString()]);
        $batch2->refresh();
        $batch2 = $this->service->completeBatch($batch2, $this->fgLoc->id, 0.15);

        $results = $this->service->compareBatches([$this->completedBatch->id, $batch2->id]);

        $this->assertCount(2, $results);
        $this->assertArrayHasKey('yield_pct',         $results[0]);
        $this->assertArrayHasKey('raw_material_cost',  $results[0]);
        $this->assertArrayHasKey('profit',             $results[0]);
        $this->assertArrayHasKey('profit_margin_pct',  $results[0]);
        $this->assertArrayHasKey('sell_through_pct',   $results[0]);

        // Batch 1: 50kg → 35L = 70% yield
        $this->assertEquals(70.0, $results[0]['yield_pct']);
        // Batch 2: 40kg → 26L = 65% yield
        $this->assertEquals(65.0, $results[1]['yield_pct']);
    }
}
