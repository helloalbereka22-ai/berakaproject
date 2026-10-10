<?php

namespace App\Services;

use App\Models\BatchMaterialConsumption;
use App\Models\BatchReconciliation;
use App\Models\BatchReturn;
use App\Models\Distribution;
use App\Models\FinishedGood;
use App\Models\Location;
use App\Models\ManufacturingOrder;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\QualityCheck;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManufacturingService
{
    public function __construct(
        private readonly InventoryService $inventory,
    ) {}

    // ── Manufacturing Order ───────────────────────────────────────────────

    public function createOrder(array $data): ManufacturingOrder
    {
        return DB::transaction(function () use ($data) {
            $order = ManufacturingOrder::create([
                'business_id'        => $data['business_id'],
                'branch_id'          => $data['branch_id'],
                'product_id'         => $data['product_id'],
                'package_size'       => $data['package_size'] ?? null,
                'planned_quantity'   => $data['planned_quantity'],
                'status'             => $data['status'] ?? 'pending',
                'supervisor_id'      => $data['supervisor_id'] ?? null,
                'planned_start_date' => $data['planned_start_date'] ?? null,
                'planned_end_date'   => $data['planned_end_date'] ?? null,
                'created_by'         => Auth::id(),
                'notes'              => $data['notes'] ?? null,
            ]);

            AuditService::logCreate('App\\Models\\ManufacturingOrder', $order, $order->business_id);

            return $order;
        });
    }

    // ── Production Batch ──────────────────────────────────────────────────

    public function openBatch(ManufacturingOrder $order, array $data): ProductionBatch
    {
        return DB::transaction(function () use ($order, $data) {
            $batch = ProductionBatch::create([
                'business_id'            => $order->business_id,
                'branch_id'              => $order->branch_id,
                'manufacturing_order_id' => $order->id,
                'batch_number'           => ProductionBatch::generateBatchNumber(),
                'planned_quantity'       => $data['planned_quantity'] ?? $order->planned_quantity,
                'produced_quantity'      => 0,
                'oil_quantity_produced'  => 0,
                'oil_unit'               => $data['oil_unit'] ?? ProductionBatch::OIL_UNIT_ML,
                'raw_material_cost'      => 0,
                'packing_cost'           => 0,
                'revenue'                => 0,
                'profit'                 => 0,
                'status'                 => ProductionBatch::STATUS_OPEN,
                'supervisor_id'          => $data['supervisor_id'] ?? null,
                'notes'                  => $data['notes'] ?? null,
                'started_at'             => now(),
            ]);

            AuditService::logCreate('App\\Models\\ProductionBatch', $batch, $order->business_id);

            return $batch;
        });
    }

    // ── Raw Material Stock-Out ────────────────────────────────────────────

    public function consumeRawMaterial(
        ProductionBatch $batch,
        int $locationId,
        int $productId,
        float $quantityKg,
        ?string $lotNumber = null
    ): BatchMaterialConsumption {
        if ($batch->status !== ProductionBatch::STATUS_OPEN) {
            throw new \RuntimeException("Cannot consume materials on a batch with status '{$batch->status}'.");
        }
        if ($quantityKg <= 0) {
            throw new \InvalidArgumentException("Quantity must be greater than zero.");
        }

        return DB::transaction(function () use ($batch, $locationId, $productId, $quantityKg, $lotNumber) {
            $movement = $this->inventory->recordMovement(
                $locationId, 'product', $productId,
                StockMovement::TYPE_PRODUCTION_CONSUMPTION,
                $quantityKg, 0.0,
                [
                    'reference_type' => 'App\\Models\\ProductionBatch',
                    'reference_id'   => $batch->id,
                    'notes'          => "Batch {$batch->batch_number} raw material consumption",
                ]
            );

            $unitCost  = (float) $movement->unit_cost;
            $totalCost = round($quantityKg * $unitCost, 4);

            $consumption = BatchMaterialConsumption::create([
                'production_batch_id' => $batch->id,
                'raw_material_id'     => $productId,
                'quantity_used'       => $quantityKg,
                'quantity_consumed'   => $quantityKg,
                'unit_cost'           => $unitCost,
                'total_cost'          => $totalCost,
                'lot_number'          => $lotNumber,
                'warehouse_id'        => $locationId,
                'consumption_type'    => BatchMaterialConsumption::TYPE_RAW_MATERIAL,
                'consumed_at'         => now(),
                'created_at'          => now(),
            ]);

            $batch->increment('raw_material_cost', $totalCost);

            return $consumption;
        });
    }

    // ── Packing Material Consumption ──────────────────────────────────────

    public function consumePackingMaterials(ProductionBatch $batch, array $packingItems): array
    {
        if (!in_array($batch->status, [ProductionBatch::STATUS_OPEN, ProductionBatch::STATUS_APPROVED])) {
            throw new \RuntimeException("Packing can only happen on an open or QC-approved batch.");
        }

        return DB::transaction(function () use ($batch, $packingItems) {
            $consumptions = [];

            foreach ($packingItems as $item) {
                $locationId = (int) $item['location_id'];
                $productId  = (int) $item['product_id'];
                $quantity   = (float) $item['quantity'];

                if ($quantity <= 0) {
                    throw new \InvalidArgumentException("Packing quantity must be greater than zero.");
                }

                $product = Product::withoutGlobalScopes()->findOrFail($productId);
                if ($product->type !== Product::TYPE_PACKING_MATERIAL) {
                    throw new \RuntimeException("Product '{$product->name}' is not a packing material.");
                }

                $movement = $this->inventory->recordMovement(
                    $locationId, 'product', $productId,
                    StockMovement::TYPE_PACKING_CONSUMPTION,
                    $quantity, 0.0,
                    [
                        'reference_type' => 'App\\Models\\ProductionBatch',
                        'reference_id'   => $batch->id,
                        'notes'          => "Batch {$batch->batch_number} packing: {$product->name}",
                    ]
                );

                $unitCost  = (float) $movement->unit_cost;
                $totalCost = round($quantity * $unitCost, 4);

                $consumptions[] = BatchMaterialConsumption::create([
                    'production_batch_id' => $batch->id,
                    'raw_material_id'     => $productId,
                    'quantity_used'       => $quantity,
                    'quantity_consumed'   => $quantity,
                    'unit_cost'           => $unitCost,
                    'total_cost'          => $totalCost,
                    'warehouse_id'        => $locationId,
                    'consumption_type'    => BatchMaterialConsumption::TYPE_PACKING_MATERIAL,
                    'consumed_at'         => now(),
                    'created_at'          => now(),
                ]);

                $batch->increment('packing_cost', $totalCost);
            }

            return $consumptions;
        });
    }

    // ── Oil Yield Recording ───────────────────────────────────────────────

    public function recordOilOutput(
        ProductionBatch $batch,
        float $oilQuantity,
        string $unit,
        int $locationId
    ): ProductionBatch {
        if ($batch->status !== ProductionBatch::STATUS_OPEN) {
            throw new \RuntimeException("Oil output can only be recorded on an open batch.");
        }
        if ($oilQuantity <= 0) {
            throw new \InvalidArgumentException("Oil quantity must be greater than zero.");
        }
        if (!in_array($unit, [ProductionBatch::OIL_UNIT_ML, ProductionBatch::OIL_UNIT_LITRE])) {
            throw new \InvalidArgumentException("Oil unit must be 'ml' or 'litre'.");
        }

        return DB::transaction(function () use ($batch, $oilQuantity, $unit, $locationId) {
            $batch->update([
                'oil_quantity_produced' => $oilQuantity,
                'oil_unit'              => $unit,
                'produced_quantity'     => $oilQuantity,
                'status'                => ProductionBatch::STATUS_IN_QC,
            ]);

            AuditService::log('oil_output_recorded', 'App\\Models\\ProductionBatch', $batch->id,
                [], ['oil_quantity' => $oilQuantity, 'unit' => $unit], $batch->business_id);

            return $batch->fresh();
        });
    }

    // ── Quality Control ───────────────────────────────────────────────────

    public function submitQualityCheck(ProductionBatch $batch, array $data): QualityCheck
    {
        if ($batch->status !== ProductionBatch::STATUS_IN_QC) {
            throw new \RuntimeException("QC can only be performed on batches with status 'in_qc'.");
        }

        return DB::transaction(function () use ($batch, $data) {
            $status = $data['status'];

            $qc = QualityCheck::create([
                'production_batch_id' => $batch->id,
                'inspected_by'        => $data['inspector_id'] ?? Auth::id(),
                'status'              => $status,
                'inspection_date'     => $data['inspection_date'] ?? now()->toDateString(),
                'approval_date'       => $status === QualityCheck::STATUS_APPROVED ? now()->toDateString() : null,
                'quantity_checked'    => $data['quantity_checked'] ?? 0,
                'quantity_passed'     => $data['quantity_passed'] ?? 0,
                'quantity_failed'     => $data['quantity_failed'] ?? 0,
                'remarks'             => $data['remarks'] ?? null,
                'rejected_reason'     => $data['rejected_reason'] ?? null,
                'checked_at'          => now(),
                'created_at'          => now(),
            ]);

            $newStatus = $status === QualityCheck::STATUS_APPROVED
                ? ProductionBatch::STATUS_APPROVED
                : ProductionBatch::STATUS_REJECTED;

            $batch->update(['status' => $newStatus]);

            AuditService::log("qc_{$status}", 'App\\Models\\ProductionBatch', $batch->id,
                ['status' => ProductionBatch::STATUS_IN_QC],
                ['status' => $newStatus, 'qc_id' => $qc->id],
                $batch->business_id);

            return $qc;
        });
    }

    // ── Complete Batch → Finished Goods ──────────────────────────────────

    public function completeBatch(
        ProductionBatch $batch,
        int $finishedGoodsLocationId,
        float $sellingPricePerMl = 0.0,
        array $packingItems = []
    ): ProductionBatch {
        if ($batch->status !== ProductionBatch::STATUS_APPROVED) {
            throw new \RuntimeException(
                "Batch must be QC-approved before completion. Current status: {$batch->status}."
            );
        }

        // Ensure manufacturing order and product are properly loaded
        $batch->load('manufacturingOrder.product');
        
        if (!$batch->manufacturingOrder || !$batch->manufacturingOrder->product_id) {
            throw new \RuntimeException(
                "Cannot complete batch: Manufacturing order or product is missing."
            );
        }

        return DB::transaction(function () use ($batch, $finishedGoodsLocationId, $sellingPricePerMl, $packingItems) {
            if (!empty($packingItems)) {
                $this->consumePackingMaterials($batch, $packingItems);
                $batch->refresh();
            }

            $oilLitres = $batch->getOilInLitres();
            $unitCost  = $oilLitres > 0
                ? round(((float) $batch->raw_material_cost + (float) $batch->packing_cost) / $oilLitres, 4)
                : 0.0;

            // Create the finished good record first
            $finishedGood = FinishedGood::create([
                'production_batch_id'   => $batch->id,
                'product_id'            => $batch->manufacturingOrder->product_id,
                'quantity'              => $batch->oil_quantity_produced,
                'quantity_available'    => $batch->oil_quantity_produced,
                'quantity_distributed'  => 0,
                'quantity_sold'         => 0,
                'quantity_returned'     => 0,
                'unit_cost'             => $unitCost,
                'warehouse_location_id' => $finishedGoodsLocationId,
                'status'                => FinishedGood::STATUS_AVAILABLE,
            ]);

            // Validate location before recording movement
            $fgLocation = Location::find($finishedGoodsLocationId);
            if (!$fgLocation) {
                throw new \RuntimeException(
                    "Cannot record production output: Location {$finishedGoodsLocationId} does not exist."
                );
            }
            if (!$fgLocation->branch) {
                throw new \RuntimeException(
                    "Cannot record production output: Location {$fgLocation->id} ({$fgLocation->name}) "
                    . "does not have a valid branch assignment."
                );
            }

            // Record the production output movement using the finished good's product
            $this->inventory->recordMovement(
                $finishedGoodsLocationId, 'product',
                $finishedGood->product_id,  // Use the finished good's product
                StockMovement::TYPE_PRODUCTION_OUTPUT,
                $batch->oil_quantity_produced, $unitCost,
                [
                    'reference_type' => 'App\\Models\\ProductionBatch',
                    'reference_id'   => $batch->id,
                    'notes'          => "Finished goods from batch {$batch->batch_number}",
                ]
            );

            $rawMaterialCost = (float) $batch->raw_material_cost;
            $packingCost     = (float) $batch->packing_cost;
            $oilMl           = $batch->getOilInMl();
            $revenue         = $oilMl * $sellingPricePerMl;
            $profit          = $revenue - $rawMaterialCost - $packingCost;

            $batch->update([
                'status'       => ProductionBatch::STATUS_COMPLETED,
                'revenue'      => round($revenue, 4),
                'profit'       => round($profit, 4),
                'packing_cost' => $packingCost,
                'completed_at' => now(),
            ]);

            AuditService::log('batch_completed', 'App\\Models\\ProductionBatch', $batch->id,
                [],
                ['oil_produced' => $batch->oil_quantity_produced, 'raw_material_cost' => $rawMaterialCost,
                 'packing_cost' => $packingCost, 'revenue' => $revenue, 'profit' => $profit],
                $batch->business_id);

            return $batch->fresh();
        });
    }

    // ── Distribution ──────────────────────────────────────────────────────

    public function distribute(
        ProductionBatch $batch,
        int $sourceLocationId,
        int $destinationBranchId,
        int $destinationLocationId,
        float $quantity,
        string $notes = ''
    ): Distribution {
        if (!$batch->isQcApproved()) {
            throw new \RuntimeException("Cannot distribute from a batch that has not passed QC.");
        }

        $fg = $batch->finishedGood;
        if (!$fg) {
            throw new \RuntimeException("No finished goods record for batch {$batch->batch_number}.");
        }

        if ($quantity > $fg->getRemaining()) {
            throw new \RuntimeException(
                "Insufficient finished goods. Available: {$fg->getRemaining()}, Requested: {$quantity}."
            );
        }

        return DB::transaction(function () use (
            $batch, $fg, $sourceLocationId, $destinationBranchId,
            $destinationLocationId, $quantity, $notes
        ) {
            $this->inventory->recordMovement(
                $sourceLocationId, 'product', $fg->product_id,
                StockMovement::TYPE_DISTRIBUTION, $quantity, 0.0,
                ['reference_type' => 'App\\Models\\ProductionBatch', 'reference_id' => $batch->id,
                 'notes' => "Distribution from batch {$batch->batch_number}"]
            );

            $this->inventory->recordMovement(
                $destinationLocationId, 'product', $fg->product_id,
                StockMovement::TYPE_TRANSFER_IN, $quantity, (float) $fg->unit_cost,
                ['reference_type' => 'App\\Models\\ProductionBatch', 'reference_id' => $batch->id,
                 'notes' => "Received distribution from batch {$batch->batch_number}"]
            );

            $ref = 'DIST-' . strtoupper(Str::random(8));

            $distribution = Distribution::create([
                'finished_good_id'        => $fg->id,
                'source_location_id'      => $sourceLocationId,
                'destination_branch_id'   => $destinationBranchId,
                'destination_location_id' => $destinationLocationId,
                'quantity'                => $quantity,
                'quantity_received'       => 0,
                'reference'               => $ref,
                'notes'                   => $notes,
                'status'                  => Distribution::STATUS_IN_TRANSIT,
                'distributed_at'          => now(),
                'created_by'              => Auth::id(),
            ]);

            $fg->increment('quantity_distributed', $quantity);

            AuditService::log('distributed', 'App\\Models\\ProductionBatch', $batch->id,
                [], ['distribution_id' => $distribution->id, 'quantity' => $quantity, 'reference' => $ref],
                $batch->business_id);

            return $distribution;
        });
    }

    public function confirmDistributionReceipt(Distribution $distribution, float $quantityReceived, string $notes = ''): Distribution
    {
        return DB::transaction(function () use ($distribution, $quantityReceived, $notes) {
            $distribution->update([
                'quantity_received' => $quantityReceived,
                'received_at'       => now(),
                'status'            => Distribution::STATUS_COMPLETED,
                'notes'             => trim($distribution->notes . "\n" . $notes),
            ]);
            return $distribution->fresh();
        });
    }

    // ── Batch Return ──────────────────────────────────────────────────────

    public function recordReturn(
        ProductionBatch $batch,
        int $locationId,
        float $quantity,
        string $reason
    ): BatchReturn {
        return DB::transaction(function () use ($batch, $locationId, $quantity, $reason) {
            $fg = $batch->finishedGood;
            if (!$fg) {
                throw new \RuntimeException("No finished goods record for this batch.");
            }

            $this->inventory->recordMovement(
                $locationId, 'product', $fg->product_id,
                StockMovement::TYPE_SALE_RETURN, $quantity, (float) $fg->unit_cost,
                ['reference_type' => 'App\\Models\\ProductionBatch', 'reference_id' => $batch->id,
                 'notes' => "Return to batch {$batch->batch_number}: {$reason}"]
            );

            $return = BatchReturn::create([
                'production_batch_id' => $batch->id,
                'product_id'          => $fg->product_id,
                'quantity'            => $quantity,
                'reason'              => $reason,
                'status'              => BatchReturn::STATUS_APPROVED,
                'returned_by'         => Auth::id(),
                'returned_at'         => now(),
                'approved_by'         => Auth::id(),
                'approved_at'         => now(),
            ]);

            $fg->increment('quantity_returned', $quantity);

            return $return;
        });
    }

    // ── Batch Reconciliation ──────────────────────────────────────────────

    /**
     * Reconcile quantities for a batch.
     * Expected = finished_quantity
     * Actual   = current_stock + sold + distributed - returned
     * Flag mismatch WITHOUT silently correcting it.
     */
    public function reconcileBatch(ProductionBatch $batch): BatchReconciliation
    {
        $batch->load(['finishedGood.distributions', 'returns']);

        $fg           = $batch->finishedGood;
        $finishedQty  = $fg ? (float) $fg->quantity : 0;
        $soldQty      = (float) \App\Models\BranchSale::where('production_batch_id', $batch->id)->sum('quantity');
        $distQty      = $fg ? (float) ($fg->distributions->sum('quantity')) : 0;
        $retQty       = (float) $batch->returns->filter(fn($r) => $r->isApproved())->sum('quantity');

        $currentStock = ($fg && $fg->warehouse_location_id)
            ? $this->inventory->getBalance($fg->warehouse_location_id, 'product', $fg->product_id)
            : 0;

        // actual accounted = what we can verify
        $actualQty   = round($currentStock + $soldQty + $distQty - $retQty, 4);
        $variance    = round($actualQty - $finishedQty, 4);
        $hasMismatch = abs($variance) > 0.001;
        $remaining   = max(0, $finishedQty - $distQty - $soldQty + $retQty);

        // Get existing to preserve investigation status
        $existing = BatchReconciliation::where('production_batch_id', $batch->id)->first();
        $newStatus = $hasMismatch
            ? (in_array($existing?->status, [
                BatchReconciliation::STATUS_INVESTIGATING,
                BatchReconciliation::STATUS_RESOLVED,
              ]) ? $existing->status : BatchReconciliation::STATUS_MISMATCH)
            : BatchReconciliation::STATUS_CLEAN;

        $reconciliation = BatchReconciliation::updateOrCreate(
            ['production_batch_id' => $batch->id],
            [
                'produced_quantity'    => $finishedQty,
                'distributed_quantity' => $distQty,
                'sold_quantity'        => $soldQty,
                'returned_quantity'    => $retQty,
                'remaining_quantity'   => $remaining,
                'expected_quantity'    => $finishedQty,
                'actual_quantity'      => $actualQty,
                'variance'             => $variance,
                'has_mismatch'         => $hasMismatch,
                'status'               => $newStatus,
                'reconciled_at'        => now(),
            ]
        );

        if ($hasMismatch) {
            AuditService::log(
                'reconciliation_mismatch',
                'App\\Models\\ProductionBatch',
                $batch->id,
                ['expected' => $finishedQty],
                ['actual' => $actualQty, 'variance' => $variance],
                $batch->business_id, $batch->branch_id,
                "Batch {$batch->batch_number}: variance of {$variance} units detected — investigation required"
            );
        }

        return $reconciliation->fresh();
    }

    public function flagForInvestigation(BatchReconciliation $reconciliation, string $notes): BatchReconciliation
    {
        $reconciliation->update([
            'status'              => BatchReconciliation::STATUS_INVESTIGATING,
            'investigation_notes' => $notes,
            'investigated_by'     => Auth::id(),
            'investigated_at'     => now(),
        ]);
        return $reconciliation->fresh();
    }

    public function resolveReconciliation(BatchReconciliation $reconciliation, string $notes): BatchReconciliation
    {
        $reconciliation->update([
            'status'              => BatchReconciliation::STATUS_RESOLVED,
            'investigation_notes' => trim(($reconciliation->investigation_notes ?? '') . "\nResolved: {$notes}"),
            'investigated_at'     => now(),
        ]);
        return $reconciliation->fresh();
    }

    public function getUnresolvedMismatches(int $businessId): \Illuminate\Support\Collection
    {
        return BatchReconciliation::whereHas('batch', fn($q) => $q->where('business_id', $businessId))
            ->whereIn('status', [BatchReconciliation::STATUS_MISMATCH, BatchReconciliation::STATUS_INVESTIGATING])
            ->with(['batch.manufacturingOrder.product', 'investigatedBy'])
            ->orderByDesc('reconciled_at')
            ->get();
    }

    // ── Full Traceability ─────────────────────────────────────────────────

    public function getTraceability(ProductionBatch $batch): array
    {
        $batch->load([
            'manufacturingOrder.product',
            'rawMaterialConsumptions.rawMaterial.category',
            'rawMaterialConsumptions.warehouse',
            'packingConsumptions.rawMaterial',
            'qualityChecks.inspector',
            'finishedGood.warehouseLocation',
            'finishedGood.distributions.destinationBranch',
            'finishedGood.distributions.destinationLocation',
            'finishedGood.distributions.creator',
            'returns.returner',
            'returns.approvedBy',
            'supervisor',
            'branch',
            'reconciliation',
        ]);

        $rawC   = $batch->rawMaterialConsumptions;
        $packC  = $batch->packingConsumptions;
        $fg     = $batch->finishedGood;
        $dists  = $fg?->distributions ?? collect();
        $rets   = $batch->returns ?? collect();

        $rawKg    = $rawC->sum(fn($c) => $c->getEffectiveQuantity());
        $rawCost  = (float) $rawC->sum('total_cost');
        $packCost = (float) $packC->sum('total_cost');
        $oilMl    = $batch->getOilInMl();
        $oilL     = $batch->getOilInLitres();
        $yield    = $rawKg > 0 ? round($oilL / $rawKg * 100, 2) : 0;

        $distQty    = (float) $dists->sum('quantity');
        $soldQty    = (float) \App\Models\BranchSale::where('production_batch_id', $batch->id)->sum('quantity');
        $retQty     = (float) $rets->filter(fn($r) => $r->isApproved())->sum('quantity');
        $finishedQ  = $fg ? (float) $fg->quantity : 0;
        $currentStock = ($fg && $fg->warehouse_location_id)
            ? $this->inventory->getBalance($fg->warehouse_location_id, 'product', $fg->product_id)
            : 0;

        return [
            'id'               => $batch->id,
            'batch_number'     => $batch->batch_number,
            'status'           => $batch->status,
            'status_label'     => $batch->getStatusLabel(),
            'production_date'  => $batch->started_at?->toDateString(),
            'completed_at'     => $batch->completed_at?->toDateString(),
            'product'          => optional($batch->manufacturingOrder?->product)->name,
            'package_size'     => $batch->manufacturingOrder?->package_size,
            'factory'          => optional($batch->branch)->name,
            'supervisor'       => optional($batch->supervisor)->name,

            'raw_materials' => $rawC->map(fn($c) => [
                'material'    => optional($c->rawMaterial)->name,
                'category'    => optional($c->rawMaterial?->category)->name,
                'quantity_kg' => $c->getEffectiveQuantity(),
                'unit_cost'   => (float) $c->unit_cost,
                'total_cost'  => (float) $c->total_cost,
                'lot_number'  => $c->lot_number,
                'warehouse'   => optional($c->warehouse)->name,
            ])->values(),
            'total_raw_material_kg'   => $rawKg,
            'total_raw_material_cost' => $rawCost,

            'oil_quantity_produced' => (float) $batch->oil_quantity_produced,
            'oil_unit'              => $batch->oil_unit,
            'oil_ml'                => $oilMl,
            'oil_litres'            => $oilL,
            'yield_percentage'      => $yield,
            'cost_per_litre'        => $oilL > 0 ? round(($rawCost + $packCost) / $oilL, 4) : 0,

            'packing_materials' => $packC->map(fn($c) => [
                'material'   => optional($c->rawMaterial)->name,
                'quantity'   => $c->getEffectiveQuantity(),
                'unit_cost'  => (float) $c->unit_cost,
                'total_cost' => (float) $c->total_cost,
            ])->values(),
            'total_packing_cost' => $packCost,

            'qc_status'          => optional($batch->qualityChecks->last())->status,
            'qc_inspector'       => optional($batch->qualityChecks->last()?->inspector)->name,
            'qc_date'            => $batch->qualityChecks->last()?->inspection_date?->toDateString(),
            'qc_approval_date'   => $batch->qualityChecks->last()?->approval_date?->toDateString(),
            'qc_remarks'         => optional($batch->qualityChecks->last())->remarks,
            'qc_rejected_reason' => optional($batch->qualityChecks->last())->rejected_reason,

            'finished_quantity'  => $finishedQ,
            'finished_warehouse' => optional($fg?->warehouseLocation)->name,
            'current_stock'      => $currentStock,

            'distributions' => $dists->map(fn($d) => [
                'id'                   => $d->id,
                'reference'            => $d->reference,
                'destination_branch'   => optional($d->destinationBranch)->name,
                'destination_location' => optional($d->destinationLocation)->name,
                'quantity_sent'        => (float) $d->quantity,
                'quantity_received'    => (float) $d->quantity_received,
                'status'               => $d->status,
                'distributed_at'       => $d->distributed_at?->toDateString(),
                'received_at'          => $d->received_at?->toDateString(),
                'created_by'           => optional($d->creator)->name,
                'notes'                => $d->notes,
            ])->values(),
            'distributed_quantity' => $distQty,

            'sold_quantity' => $soldQty,
            'revenue'       => (float) $batch->revenue,

            'returns' => $rets->map(fn($r) => [
                'quantity'    => (float) $r->quantity,
                'reason'      => $r->reason,
                'status'      => $r->status,
                'returned_by' => optional($r->returner)->name,
                'returned_at' => $r->returned_at?->toDateString(),
                'approved_by' => optional($r->approvedBy)->name,
                'approved_at' => $r->approved_at?->toDateString(),
            ])->values(),
            'returned_quantity'  => $retQty,
            'remaining_quantity' => max(0, $finishedQ - $distQty - $soldQty + $retQty),

            'raw_material_cost' => (float) $batch->raw_material_cost,
            'packing_cost'      => (float) $batch->packing_cost,
            'total_cost'        => (float) $batch->raw_material_cost + (float) $batch->packing_cost,
            'profit'            => (float) $batch->profit,

            'reconciliation' => $batch->reconciliation ? [
                'status'              => $batch->reconciliation->status,
                'has_mismatch'        => $batch->reconciliation->has_mismatch,
                'expected_quantity'   => (float) $batch->reconciliation->expected_quantity,
                'actual_quantity'     => (float) $batch->reconciliation->actual_quantity,
                'variance'            => (float) $batch->reconciliation->variance,
                'investigation_notes' => $batch->reconciliation->investigation_notes,
                'reconciled_at'       => $batch->reconciliation->reconciled_at?->toDateString(),
            ] : null,
        ];
    }

    // ── Yield Report by Raw Material Category ────────────────────────────

    public function getYieldReportByCategory(int $businessId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $batches = ProductionBatch::forBusiness($businessId)
            ->completed()
            ->with(['rawMaterialConsumptions.rawMaterial.category', 'manufacturingOrder.product'])
            ->when($fromDate, fn($q) => $q->where('started_at', '>=', $fromDate))
            ->when($toDate,   fn($q) => $q->where('started_at', '<=', $toDate))
            ->orderBy('started_at')
            ->get();

        $byCat = [];
        foreach ($batches as $batch) {
            foreach ($batch->rawMaterialConsumptions as $c) {
                $catId   = $c->rawMaterial?->category_id ?? 0;
                $catName = optional($c->rawMaterial?->category)->name ?? 'Uncategorised';
                if (!isset($byCat[$catId])) {
                    $byCat[$catId] = [
                        'category_id' => $catId, 'category_name' => $catName,
                        'batches' => 0, 'total_raw_kg' => 0, 'total_raw_cost' => 0,
                        'total_oil_ml' => 0, 'total_oil_litres' => 0,
                        'total_packing_cost' => 0, 'total_revenue' => 0, 'total_profit' => 0,
                        'avg_yield_pct' => 0, 'batch_list' => [],
                    ];
                }
                $byCat[$catId]['total_raw_kg']  += $c->getEffectiveQuantity();
                $byCat[$catId]['total_raw_cost'] += (float) $c->total_cost;
            }

            $firstCatId = $batch->rawMaterialConsumptions->first()?->rawMaterial?->category_id ?? 0;
            if (isset($byCat[$firstCatId])) {
                $byCat[$firstCatId]['batches']++;
                $byCat[$firstCatId]['total_oil_ml']       += $batch->getOilInMl();
                $byCat[$firstCatId]['total_oil_litres']   += $batch->getOilInLitres();
                $byCat[$firstCatId]['total_packing_cost'] += (float) $batch->packing_cost;
                $byCat[$firstCatId]['total_revenue']      += (float) $batch->revenue;
                $byCat[$firstCatId]['total_profit']       += (float) $batch->profit;
                $byCat[$firstCatId]['batch_list'][]        = [
                    'batch_number'    => $batch->batch_number,
                    'production_date' => $batch->started_at?->toDateString(),
                    'raw_kg'          => $batch->rawMaterialConsumptions->sum(fn($c) => $c->getEffectiveQuantity()),
                    'oil_litres'      => $batch->getOilInLitres(),
                    'yield_pct'       => $batch->getYieldPercentage(),
                    'raw_cost'        => (float) $batch->raw_material_cost,
                    'packing_cost'    => (float) $batch->packing_cost,
                    'revenue'         => (float) $batch->revenue,
                    'profit'          => (float) $batch->profit,
                ];
            }
        }

        foreach ($byCat as &$cat) {
            if ($cat['total_raw_kg'] > 0) {
                $cat['avg_yield_pct'] = round(($cat['total_oil_litres'] / $cat['total_raw_kg']) * 100, 2);
            }
        }

        return array_values($byCat);
    }

    // ── Batch Comparison ──────────────────────────────────────────────────

    public function compareBatches(array $batchIds): array
    {
        return ProductionBatch::whereIn('id', $batchIds)
            ->with(['rawMaterialConsumptions.rawMaterial', 'packingConsumptions.rawMaterial', 'qualityChecks'])
            ->get()
            ->map(fn($b) => [
                'batch_number'      => $b->batch_number,
                'product'           => optional($b->manufacturingOrder?->product)->name,
                'raw_material_kg'   => $b->rawMaterialConsumptions->sum(fn($c) => $c->getEffectiveQuantity()),
                'oil_litres'        => $b->getOilInLitres(),
                'yield_pct'         => $b->getYieldPercentage(),
                'raw_material_cost' => (float) $b->raw_material_cost,
                'packing_cost'      => (float) $b->packing_cost,
                'total_cost'        => (float) $b->raw_material_cost + (float) $b->packing_cost,
                'revenue'           => (float) $b->revenue,
                'profit'            => (float) $b->profit,
                'profit_margin_pct' => (float) $b->revenue > 0
                    ? round(((float) $b->profit / (float) $b->revenue) * 100, 2) : 0,
                'qc_status'         => optional($b->qualityChecks->last())->status,
                'sell_through_pct'  => (float) $b->oil_quantity_produced > 0
                    ? round((\App\Models\BranchSale::where('production_batch_id', $b->id)->sum('quantity')
                        / (float) $b->oil_quantity_produced) * 100, 2) : 0,
            ])
            ->values()
            ->toArray();
    }
}
