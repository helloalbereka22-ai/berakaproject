<?php

namespace App\Livewire\Admin\Manufacturing;

use App\Models\Branch;
use App\Models\Location;
use App\Models\ManufacturingOrder;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Services\InventoryService;
use App\Services\ManufacturingService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Full manufacturing batch workflow UI.
 *
 * Step 1 — Select Order → Open Batch
 * Step 2 — Consume Raw Materials (multiple lines)
 * Step 3 — Record Oil Output
 * Step 4 — Submit QC
 * Step 5 — Complete Batch (consume packing + create finished goods)
 */
#[Layout('components.layouts.admin')]
class BatchProcess extends Component
{
    // ── Navigation ────────────────────────────────────────────────────────
    public int    $step     = 1;   // 1..5
    public ?int   $orderId  = null;
    public ?int   $batchId  = null;

    // ── Step 1: Select / create order ────────────────────────────────────
    public string $filterBusinessId = '';
    public string $filterStatus     = 'pending';

    // ── Step 2: Raw material consumption ─────────────────────────────────
    // Each row: [location_id, product_id, quantity_kg, lot_number]
    public array $rawLines = [
        ['location_id' => '', 'product_id' => '', 'quantity' => '', 'lot_number' => ''],
    ];

    // ── Step 3: Oil output ────────────────────────────────────────────────
    public string $oilQuantity   = '';
    public string $oilUnit       = 'litre';
    public string $oilLocationId = '';

    // ── Step 4: QC ────────────────────────────────────────────────────────
    public string $qcStatus        = 'approved';
    public string $qcInspectorId   = '';
    public string $qcQuantityCheck = '';
    public string $qcQuantityPass  = '';
    public string $qcQuantityFail  = '';
    public string $qcRemarks       = '';
    public string $qcRejectedReason= '';

    // ── Step 5: Complete + packing ────────────────────────────────────────
    public string $fgLocationId       = '';
    public string $sellingPricePerMl  = '';
    // Each row: [location_id, product_id, quantity]
    public array $packingLines = [
        ['location_id' => '', 'product_id' => '', 'quantity' => ''],
    ];

    // ── Messages ──────────────────────────────────────────────────────────
    public string $successMessage = '';
    public string $errorMessage   = '';

    // ── Lookup lists (refreshed as needed) ───────────────────────────────
    public array $businesses     = [];
    public array $rawMaterials   = [];
    public array $packingMats    = [];
    public array $locations      = [];
    public array $inspectors     = [];

    public function mount(?int $orderId = null, ?int $batchId = null): void
    {
        $user = auth()->user();

        $this->businesses = $user->isSuperAdmin()
            ? \App\Models\Business::active()->orderBy('name')->get(['id', 'name'])->toArray()
            : \App\Models\Business::whereIn('id', $user->assignedBusinessIds())->orderBy('name')->get(['id', 'name'])->toArray();

        if (count($this->businesses) === 1) {
            $this->filterBusinessId = (string) $this->businesses[0]['id'];
        }

        if ($batchId) {
            $this->batchId = $batchId;
            $batch         = ProductionBatch::find($batchId);
            $this->orderId = $batch?->manufacturing_order_id;
            $this->step    = $this->detectStep($batch);
            $this->refreshLookups();
        } elseif ($orderId) {
            $this->orderId = $orderId;
            $this->step    = 2;
            $this->refreshLookups();
        }
    }

    // ── Computed properties ───────────────────────────────────────────────

    #[Computed]
    public function orders(): \Illuminate\Database\Eloquent\Collection
    {
        return ManufacturingOrder::with(['product', 'branch.business', 'activeBatch'])
            ->when($this->filterBusinessId, fn($q) => $q->where('business_id', $this->filterBusinessId))
            ->when(!auth()->user()->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', auth()->user()->assignedBusinessIds())
            )
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function currentOrder(): ?ManufacturingOrder
    {
        return $this->orderId
            ? ManufacturingOrder::with(['product.variants', 'branch.business'])->find($this->orderId)
            : null;
    }

    #[Computed]
    public function currentBatch(): ?ProductionBatch
    {
        return $this->batchId
            ? ProductionBatch::with([
                'rawMaterialConsumptions.rawMaterial',
                'packingConsumptions.rawMaterial',
                'qualityCheck',
                'finishedGood',
                'manufacturingOrder.product',
              ])->find($this->batchId)
            : null;
    }

    // ── Step navigation ───────────────────────────────────────────────────

    public function selectOrder(int $orderId): void
    {
        $this->orderId        = $orderId;
        $this->batchId        = null;
        $this->errorMessage   = '';
        $this->successMessage = '';
        $this->step           = 2;
        $this->refreshLookups();
        unset($this->currentOrder, $this->currentBatch, $this->orders);
    }

    // ── Step 2: Open batch ────────────────────────────────────────────────

    public function openBatch(): void
    {
        $this->clearMessages();

        $order = ManufacturingOrder::find($this->orderId);
        if (!$order) { $this->errorMessage = 'Order not found.'; return; }

        try {
            $batch         = app(ManufacturingService::class)->openBatch($order, [
                'oil_unit'         => $this->oilUnit ?: 'litre',
                'supervisor_id'    => auth()->id(),
            ]);
            $this->batchId = $batch->id;
            $this->step    = 2;
            $this->refreshLookups();
            $this->successMessage = "Batch {$batch->batch_number} opened.";
            unset($this->currentBatch);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // ── Step 2: Add / remove raw material lines ───────────────────────────

    public function addRawLine(): void
    {
        $this->rawLines[] = ['location_id' => '', 'product_id' => '', 'quantity' => '', 'lot_number' => ''];
    }

    public function removeRawLine(int $index): void
    {
        array_splice($this->rawLines, $index, 1);
        if (empty($this->rawLines)) {
            $this->rawLines = [['location_id' => '', 'product_id' => '', 'quantity' => '', 'lot_number' => '']];
        }
    }

    public function consumeRawMaterials(): void
    {
        $this->clearMessages();

        $this->validate([
            'rawLines'                  => 'required|array|min:1',
            'rawLines.*.location_id'    => 'required|integer|exists:locations,id',
            'rawLines.*.product_id'     => 'required|integer|exists:products,id',
            'rawLines.*.quantity'       => 'required|numeric|min:0.0001',
        ]);

        $batch = ProductionBatch::find($this->batchId);
        if (!$batch) { $this->errorMessage = 'Batch not found.'; return; }

        $service = app(ManufacturingService::class);
        $errors  = [];

        foreach ($this->rawLines as $i => $line) {
            try {
                $service->consumeRawMaterial(
                    $batch,
                    (int)   $line['location_id'],
                    (int)   $line['product_id'],
                    (float) $line['quantity'],
                    $line['lot_number'] ?: null
                );
            } catch (\Throwable $e) {
                $errors[] = "Row " . ($i + 1) . ": " . $e->getMessage();
            }
        }

        if ($errors) {
            $this->errorMessage = implode(' | ', $errors);
            return;
        }

        $this->rawLines       = [['location_id' => '', 'product_id' => '', 'quantity' => '', 'lot_number' => '']];
        $this->successMessage = 'Raw material consumption recorded.';
        unset($this->currentBatch);
    }

    // ── Step 3: Record oil output ─────────────────────────────────────────

    public function recordOilOutput(): void
    {
        $this->clearMessages();

        $this->validate([
            'oilQuantity'   => 'required|numeric|min:0.0001',
            'oilUnit'       => 'required|in:ml,litre',
            'oilLocationId' => 'required|integer|exists:locations,id',
        ]);

        $batch = ProductionBatch::find($this->batchId);
        if (!$batch) { $this->errorMessage = 'Batch not found.'; return; }

        try {
            app(ManufacturingService::class)->recordOilOutput(
                $batch,
                (float) $this->oilQuantity,
                $this->oilUnit,
                (int) $this->oilLocationId
            );
            $this->step           = 4;
            $this->successMessage = "Oil output recorded: {$this->oilQuantity} {$this->oilUnit}.";
            unset($this->currentBatch);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // ── Step 4: QC ────────────────────────────────────────────────────────

    public function submitQc(): void
    {
        $this->clearMessages();

        $this->validate([
            'qcStatus'          => 'required|in:approved,rejected',
            'qcQuantityCheck'   => 'nullable|numeric|min:0',
            'qcQuantityPass'    => 'nullable|numeric|min:0',
            'qcQuantityFail'    => 'nullable|numeric|min:0',
            'qcRemarks'         => 'nullable|string|max:1000',
            'qcRejectedReason'  => 'nullable|string|max:1000',
        ]);

        $batch = ProductionBatch::find($this->batchId);
        if (!$batch) { $this->errorMessage = 'Batch not found.'; return; }

        try {
            app(ManufacturingService::class)->submitQualityCheck($batch, [
                'status'           => $this->qcStatus,
                'inspector_id'     => $this->qcInspectorId ?: auth()->id(),
                'quantity_checked' => $this->qcQuantityCheck ?: 0,
                'quantity_passed'  => $this->qcQuantityPass  ?: 0,
                'quantity_failed'  => $this->qcQuantityFail  ?: 0,
                'remarks'          => $this->qcRemarks,
                'rejected_reason'  => $this->qcRejectedReason,
            ]);

            if ($this->qcStatus === 'approved') {
                $this->step           = 5;
                $this->successMessage = 'QC approved. Ready to complete batch.';
            } else {
                $this->successMessage = 'QC rejected. Batch has been flagged.';
            }
            unset($this->currentBatch);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // ── Step 5: Add / remove packing lines ───────────────────────────────

    public function addPackingLine(): void
    {
        $this->packingLines[] = ['location_id' => '', 'product_id' => '', 'quantity' => ''];
    }

    public function removePackingLine(int $index): void
    {
        array_splice($this->packingLines, $index, 1);
        if (empty($this->packingLines)) {
            $this->packingLines = [['location_id' => '', 'product_id' => '', 'quantity' => '']];
        }
    }

    public function completeBatch(): void
    {
        $this->clearMessages();

        $this->validate([
            'fgLocationId'              => 'required|integer|exists:locations,id',
            'sellingPricePerMl'         => 'nullable|numeric|min:0',
            'packingLines.*.location_id'=> 'nullable|integer|exists:locations,id',
            'packingLines.*.product_id' => 'nullable|integer|exists:products,id',
            'packingLines.*.quantity'   => 'nullable|numeric|min:0',
        ]);

        $batch = ProductionBatch::find($this->batchId);
        if (!$batch) { $this->errorMessage = 'Batch not found.'; return; }

        // Filter out empty packing lines
        $packingItems = collect($this->packingLines)
            ->filter(fn($l) => !empty($l['location_id']) && !empty($l['product_id']) && !empty($l['quantity']) && $l['quantity'] > 0)
            ->map(fn($l) => [
                'location_id' => (int)   $l['location_id'],
                'product_id'  => (int)   $l['product_id'],
                'quantity'    => (float) $l['quantity'],
            ])
            ->values()
            ->toArray();

        try {
            app(ManufacturingService::class)->completeBatch(
                $batch,
                (int)   $this->fgLocationId,
                (float) ($this->sellingPricePerMl ?: 0),
                $packingItems
            );

            $this->successMessage = "Batch {$batch->batch_number} completed. Finished goods added to inventory.";
            unset($this->currentBatch);

            session()->flash('success', $this->successMessage);
            $this->redirect(route('admin.manufacturing.batches'), navigate: true);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function refreshLookups(): void
    {
        $businessId = $this->filterBusinessId
            ?: ($this->currentOrder?->business_id)
            ?: null;

        if ($businessId) {
            $locationIds = Location::whereHas('branch', fn($q) =>
                $q->where('business_id', $businessId)
            )->pluck('id');

            $this->locations = Location::whereIn('id', $locationIds)
                ->with('branch')
                ->orderBy('name')
                ->get(['id', 'name', 'branch_id'])
                ->map(fn($l) => [
                    'id'          => $l->id,
                    'name'        => $l->name . ' (' . optional($l->branch)->name . ')',
                    'branch_id'   => $l->branch_id,
                ])
                ->toArray();

            $this->rawMaterials = Product::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->rawMaterials()
                ->orderBy('name')
                ->get(['id', 'name', 'unit'])
                ->toArray();

            $this->packingMats = Product::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->packagingMaterials()
                ->orderBy('name')
                ->get(['id', 'name', 'unit'])
                ->toArray();

            $this->inspectors = \App\Models\User::whereHas('roles', fn($q) =>
                $q->wherePivot('business_id', $businessId)
            )->orderBy('name')->get(['id', 'name'])->toArray();
        }
    }

    private function detectStep(?ProductionBatch $batch): int
    {
        if (!$batch) return 1;
        return match ($batch->status) {
            ProductionBatch::STATUS_OPEN     => 2,
            ProductionBatch::STATUS_IN_QC    => 4,
            ProductionBatch::STATUS_APPROVED => 5,
            default                          => 2,
        };
    }

    private function clearMessages(): void
    {
        $this->successMessage = '';
        $this->errorMessage   = '';
    }

    public function render()
    {
        return view('livewire.admin.manufacturing.batch-process')
            ->title('Manufacturing — Batch Process');
    }
}
