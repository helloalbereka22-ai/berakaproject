<?php

namespace App\Livewire\Admin\Manufacturing;

use App\Models\Business;
use App\Models\ProductionBatch;
use App\Services\AuthorizationService;
use App\Services\ManufacturingService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class BatchTraceability extends Component
{
    use WithPagination;

    // ── Batch list filters ────────────────────────────────────────────────
    public string $search      = '';
    public string $businessId  = '';
    public string $status      = '';
    public string $fromDate    = '';
    public string $toDate      = '';

    // ── Detail view ───────────────────────────────────────────────────────
    public ?int   $selectedBatchId = null;
    public array  $traceData       = [];

    // ── Reconciliation ────────────────────────────────────────────────────
    public bool   $showReconModal      = false;
    public string $reconNotes          = '';
    public ?int   $reconRecordId       = null;
    public string $reconAction         = ''; // investigate | resolve

    // ── Comparison ───────────────────────────────────────────────────────
    public bool   $showCompareModal    = false;
    public array  $compareIds          = [];
    public array  $compareResults      = [];
    public string $compareSearchId     = '';

    // ── Distribution ─────────────────────────────────────────────────────
    public bool   $showDistModal       = false;
    public string $distLocationId      = '';
    public string $distDestBranchId    = '';
    public string $distDestLocationId  = '';
    public string $distQuantity        = '';
    public string $distNotes           = '';
    public ?int   $distBatchId         = null;

    public array $businesses = [];
    public array $locations  = [];

    public function mount(): void
    {
        $user = auth()->user();
        $this->businesses = $user->isSuperAdmin()
            ? Business::active()->orderBy('name')->get(['id','name'])->toArray()
            : Business::whereIn('id', $user->assignedBusinessIds())->orderBy('name')->get(['id','name'])->toArray();
    }

    public function updatedSearch(): void   { $this->resetPage(); }
    public function updatedBusinessId(): void { $this->resetPage(); }
    public function updatedStatus(): void   { $this->resetPage(); }

    // ── Select a batch for traceability ──────────────────────────────────

    public function selectBatch(int $id): void
    {
        $batch = ProductionBatch::findOrFail($id);
        if ($batch->business_id) {
            app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), $batch->business_id);
        }
        $this->selectedBatchId = $id;
        $this->traceData = app(ManufacturingService::class)->getTraceability($batch);
    }

    public function clearSelection(): void
    {
        $this->selectedBatchId = null;
        $this->traceData = [];
    }

    // ── Reconcile a batch ─────────────────────────────────────────────────

    public function reconcile(int $batchId): void
    {
        $batch = ProductionBatch::findOrFail($batchId);
        $rec = app(ManufacturingService::class)->reconcileBatch($batch);

        // Refresh trace data if viewing this batch
        if ($this->selectedBatchId === $batchId) {
            $this->traceData = app(ManufacturingService::class)->getTraceability($batch->fresh());
        }

        if ($rec->has_mismatch) {
            session()->flash('warning', "Mismatch detected on batch {$batch->batch_number}. Variance: {$rec->variance} units. Investigation required.");
        } else {
            session()->flash('success', "Batch {$batch->batch_number} reconciled — quantities match.");
        }
    }

    public function openReconAction(int $reconId, string $action): void
    {
        $this->reconRecordId = $reconId;
        $this->reconAction   = $action;
        $this->reconNotes    = '';
        $this->showReconModal = true;
    }

    public function submitReconAction(): void
    {
        $this->validate(['reconNotes' => 'required|string|min:10']);

        $rec = \App\Models\BatchReconciliation::findOrFail($this->reconRecordId);
        $svc = app(ManufacturingService::class);

        if ($this->reconAction === 'investigate') {
            $svc->flagForInvestigation($rec, $this->reconNotes);
            session()->flash('success', 'Batch marked for investigation.');
        } else {
            $svc->resolveReconciliation($rec, $this->reconNotes);
            session()->flash('success', 'Reconciliation marked as resolved.');
        }

        if ($this->selectedBatchId) {
            $batch = ProductionBatch::find($this->selectedBatchId);
            if ($batch) {
                $this->traceData = $svc->getTraceability($batch->fresh());
            }
        }

        $this->showReconModal = false;
        $this->reset(['reconRecordId', 'reconAction', 'reconNotes']);
    }

    // ── Comparison ───────────────────────────────────────────────────────

    public function addToCompare(int $batchId): void
    {
        if (count($this->compareIds) >= 10) {
            session()->flash('error', 'Maximum 10 batches can be compared at once.');
            return;
        }
        if (!in_array($batchId, $this->compareIds)) {
            $this->compareIds[] = $batchId;
        }
    }

    public function removeFromCompare(int $batchId): void
    {
        $this->compareIds = array_values(array_filter($this->compareIds, fn($id) => $id !== $batchId));
        $this->compareResults = [];
    }

    public function runComparison(): void
    {
        if (count($this->compareIds) < 2) {
            session()->flash('error', 'Select at least 2 batches to compare.');
            return;
        }
        $this->compareResults = app(ManufacturingService::class)->compareBatches($this->compareIds);
        $this->showCompareModal = true;
    }

    // ── Distribution ─────────────────────────────────────────────────────

    public function openDistributeModal(int $batchId): void
    {
        $this->distBatchId = $batchId;
        $this->distQuantity = '';
        $this->distNotes    = '';
        $this->showDistModal = true;

        // Load locations for the business
        $batch = ProductionBatch::with('branch.business')->find($batchId);
        if ($batch?->branch?->business_id) {
            $this->locations = \App\Models\Location::whereHas('branch', fn($q) =>
                $q->where('business_id', $batch->branch->business_id)
            )->orderBy('name')->get(['id', 'name', 'type'])->toArray();
        }
    }

    public function saveDistribution(): void
    {
        $this->validate([
            'distLocationId'     => 'required|integer',
            'distDestBranchId'   => 'required|integer',
            'distDestLocationId' => 'required|integer',
            'distQuantity'       => 'required|numeric|min:0.0001',
        ]);

        $batch = ProductionBatch::findOrFail($this->distBatchId);
        if ($batch->business_id) {
            app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), $batch->business_id);
        }

        try {
            app(ManufacturingService::class)->distribute(
                $batch,
                (int) $this->distLocationId,
                (int) $this->distDestBranchId,
                (int) $this->distDestLocationId,
                (float) $this->distQuantity,
                $this->distNotes
            );

            $this->showDistModal = false;
            $this->reset(['distBatchId', 'distLocationId', 'distDestBranchId', 'distDestLocationId', 'distQuantity', 'distNotes']);
            session()->flash('success', 'Distribution recorded successfully.');

            // Refresh trace if viewing
            if ($this->selectedBatchId === $batch->id) {
                $this->traceData = app(ManufacturingService::class)->getTraceability($batch->fresh());
            }
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    // ── Batch list ────────────────────────────────────────────────────────

    public function getBatchesProperty()
    {
        $user  = auth()->user();
        $query = ProductionBatch::with(['manufacturingOrder.product', 'qualityChecks', 'reconciliation'])
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            ->when($this->businessId, fn($q) => $q->where('business_id', $this->businessId))
            ->when($this->status,     fn($q) => $q->where('status', $this->status))
            ->when($this->fromDate,   fn($q) => $q->where('started_at', '>=', $this->fromDate))
            ->when($this->toDate,     fn($q) => $q->where('started_at', '<=', $this->toDate))
            ->when($this->search,     fn($q) =>
                $q->where('batch_number', 'like', "%{$this->search}%")
            )
            ->orderByDesc('started_at');

        return $query->paginate(20);
    }

    public function getMismatchesProperty()
    {
        if (!$this->businessId) return collect();
        return app(ManufacturingService::class)->getUnresolvedMismatches((int) $this->businessId);
    }

    public function render()
    {
        return view('livewire.admin.manufacturing.batch-traceability', [
            'batches'    => $this->batches,
            'mismatches' => $this->mismatches,
            'statuses'   => ProductionBatch::STATUSES,
        ])->title('Batch Traceability');
    }
}
