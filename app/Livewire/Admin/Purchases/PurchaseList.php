<?php

namespace App\Livewire\Admin\Purchases;

use App\Models\Purchase;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class PurchaseList extends Component
{
    use WithPagination;

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $query = Purchase::with(['business', 'branch', 'warehouse', 'product', 'creator'])
            ->orderByDesc('purchase_date')
            ->orderByDesc('id');

        if (!$user->isSuperAdmin()) {
            $query->whereIn('business_id', $user->assignedBusinessIds());
        }

        if ($this->search) {
            $query->where('reference', 'like', '%' . $this->search . '%')
                  ->orWhereHas('product', function($q) {
                      $q->where('name', 'like', '%' . $this->search . '%');
                  });
        }

        return view('livewire.admin.purchases.index', [
            'purchases' => $query->paginate(15),
        ])->title('Purchases');
    }

    public function voidPurchase($id)
    {
        $purchase = Purchase::findOrFail($id);
        
        // Reverse stock movement
        // In a real app we'd verify it hasn't been consumed yet, etc.
        \Illuminate\Support\Facades\DB::transaction(function() use ($purchase) {
            $purchase->status = 'voided';
            $purchase->save();

            \App\Models\StockMovement::create([
                'business_id' => $purchase->business_id,
                'branch_id' => $purchase->branch_id,
                'warehouse_id' => $purchase->warehouse_id,
                'product_id' => $purchase->product_id,
                'type' => 'out', // reversing the in
                'quantity' => $purchase->quantity,
                'reference_type' => 'purchase_void',
                'reference_id' => $purchase->id,
                'reference_number' => $purchase->reference,
                'notes' => 'Manual purchase voided',
                'created_by' => auth()->id(),
            ]);
        });

        session()->flash('success', 'Purchase voided and stock reversed.');
    }
}
