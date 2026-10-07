<?php

namespace App\Livewire\Admin\Manufacturing;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use App\Services\ManufacturingService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class ManufacturingOrderCreate extends Component
{
    // ── Form fields ───────────────────────────────────────────────────────
    public string $business_id        = '';
    public string $branch_id          = '';
    public string $product_id         = '';
    public string $package_size       = '';
    public string $planned_quantity   = '';
    public string $planned_start_date = '';
    public string $planned_end_date   = '';
    public string $supervisor_id      = '';
    public string $notes              = '';

    // ── Lookup lists ──────────────────────────────────────────────────────
    public array $businesses       = [];
    public array $branches         = [];
    public array $products         = [];
    public array $variants         = [];
    public array $supervisors      = [];
    public array $packageSizes     = [];

    // ── Messages ──────────────────────────────────────────────────────────
    public string $errorMessage   = '';
    public string $successMessage = '';

    public function mount(): void
    {
        $this->planned_start_date = now()->toDateString();
        $user = auth()->user();

        $this->businesses = $user->isSuperAdmin()
            ? Business::active()->orderBy('name')->get(['id', 'name'])->toArray()
            : Business::whereIn('id', $user->assignedBusinessIds())->orderBy('name')->get(['id', 'name'])->toArray();

        // Pre-select if only one business
        if (count($this->businesses) === 1) {
            $this->business_id = (string) $this->businesses[0]['id'];
            $this->updatedBusinessId();
        }
    }

    // ── Watchers ──────────────────────────────────────────────────────────

    public function updatedBusinessId(): void
    {
        $this->branch_id    = '';
        $this->product_id   = '';
        $this->package_size = '';
        $this->variants     = [];
        $this->packageSizes = [];

        if (!$this->business_id) {
            $this->branches    = [];
            $this->products    = [];
            $this->supervisors = [];
            return;
        }

        $this->branches = Branch::where('business_id', $this->business_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();

        // Only manufactured product types
        $this->products = Product::withoutGlobalScopes()
            ->where('business_id', $this->business_id)
            ->whereIn('type', [Product::TYPE_FINISHED_PRODUCT, Product::TYPE_BOTTLE_PRODUCT])
            ->whereNull('parent_id')   // parent products only
            ->where('status', Product::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'package_size'])
            ->toArray();

        $this->supervisors = User::whereHas('roles', fn($q) =>
            $q->wherePivot('business_id', $this->business_id)
              ->whereIn('role_id', \App\Models\Role::whereIn('name', [
                  'Manufacturing Staff', 'Business Manager', 'Super Admin',
              ])->pluck('id'))
        )->orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function updatedProductId(): void
    {
        $this->package_size = '';
        $this->variants     = [];
        $this->packageSizes = [];

        if (!$this->product_id) return;

        // Load variants (children)
        $this->variants = Product::withoutGlobalScopes()
            ->where('parent_id', $this->product_id)
            ->where('status', Product::STATUS_ACTIVE)
            ->orderBy('package_size')
            ->get(['id', 'name', 'package_size', 'unit'])
            ->toArray();

        // Also collect available package sizes from variants + parent
        $parent = Product::withoutGlobalScopes()->find($this->product_id);
        $sizes  = collect($this->variants)->pluck('package_size')->filter()->unique()->values()->toArray();
        if ($parent?->package_size) {
            array_unshift($sizes, $parent->package_size);
        }
        $this->packageSizes = array_unique($sizes);
    }

    // ── Validation ────────────────────────────────────────────────────────

    protected function rules(): array
    {
        return [
            'business_id'        => 'required|integer|exists:businesses,id',
            'branch_id'          => 'required|integer|exists:branches,id',
            'product_id'         => 'required|integer|exists:products,id',
            'package_size'       => 'nullable|string|max:50',
            'planned_quantity'   => 'required|numeric|min:0.001',
            'planned_start_date' => 'required|date',
            'planned_end_date'   => 'nullable|date|after_or_equal:planned_start_date',
            'supervisor_id'      => 'nullable|integer|exists:users,id',
            'notes'              => 'nullable|string|max:2000',
        ];
    }

    // ── Save ──────────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->errorMessage   = '';
        $this->successMessage = '';

        // Verify product belongs to selected business and is a manufactured type
        $data    = $this->validate();
        $product = Product::withoutGlobalScopes()->findOrFail($data['product_id']);

        if ((int) $product->business_id !== (int) $data['business_id']) {
            $this->addError('product_id', 'Selected product does not belong to the selected business.');
            return;
        }

        if (!in_array($product->type, [Product::TYPE_FINISHED_PRODUCT, Product::TYPE_BOTTLE_PRODUCT])) {
            $this->addError('product_id', 'Only manufactured product types can be selected for a manufacturing order.');
            return;
        }

        // Verify branch belongs to business
        $branch = \App\Models\Branch::find($data['branch_id']);
        if ((int) $branch?->business_id !== (int) $data['business_id']) {
            $this->addError('branch_id', 'Selected branch does not belong to the selected business.');
            return;
        }

        try {
            $order = app(ManufacturingService::class)->createOrder([
                'business_id'        => (int) $data['business_id'],
                'branch_id'          => (int) $data['branch_id'],
                'product_id'         => (int) $data['product_id'],
                'package_size'       => $data['package_size'] ?: null,
                'planned_quantity'   => (float) $data['planned_quantity'],
                'planned_start_date' => $data['planned_start_date'],
                'planned_end_date'   => $data['planned_end_date'] ?: null,
                'supervisor_id'      => $data['supervisor_id'] ?: null,
                'notes'              => $data['notes'] ?: null,
                'status'             => 'pending',
            ]);

            session()->flash('success', "Manufacturing order #{$order->id} created for {$product->name}.");
            $this->redirect(route('admin.manufacturing.batches'), navigate: true);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.admin.manufacturing.order-create')
            ->title('New Manufacturing Order');
    }
}
