<?php

namespace App\Livewire\Admin\Dashboard;

use Illuminate\Support\Facades\DB;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class LowStock extends Component
{
    public string $search    = '';
    public int    $threshold = 10;   // default — overridden by DB on mount
    public string $filter    = 'low'; // 'low' or 'out'

    public function mount(): void
    {
        // Load saved threshold from settings (global, no business/branch scope)
        $setting = Setting::whereNull('business_id')
            ->whereNull('branch_id')
            ->where('key', 'low_stock_threshold')
            ->first();

        $this->threshold = $setting ? (int)$setting->value : 10;
        
        // Check URL query parameter for filter
        $this->filter = request('filter', 'low'); // default to 'low' stock
    }

    public function saveThreshold(): void
    {
        $this->validate(['threshold' => 'required|integer|min:1']);

        Setting::updateOrCreate(
            ['key' => 'low_stock_threshold', 'business_id' => null, 'branch_id' => null],
            ['value' => $this->threshold, 'type' => 'integer']
        );

        $this->dispatch('threshold-saved');
    }

    public function render()
    {
        $user        = auth()->user();
        $businessIds = $user->isSuperAdmin()
            ? \App\Models\Business::pluck('id')->toArray()
            : $user->assignedBusinessIds();

        $threshold = $this->threshold;

        $rows = DB::table('stock_movements')
            ->join('products',      'stock_movements.item_id',    '=', 'products.id')
            ->leftJoin('branches',  'stock_movements.branch_id',  '=', 'branches.id')
            ->leftJoin('categories','products.category_id',       '=', 'categories.id')
            ->where('stock_movements.item_type', 'product')
            ->where('products.status', 'active')
            ->whereIn('stock_movements.business_id', $businessIds)
            ->select([
                'stock_movements.item_id',
                'stock_movements.branch_id',
                'products.name        as item',
                'products.unit        as unit',
                'categories.name      as category',
                'branches.name        as branch',
                DB::raw("SUM(CASE WHEN stock_movements.direction = 'in'
                              THEN stock_movements.quantity
                              ELSE -stock_movements.quantity END) as net_qty"),
            ])
            ->groupBy(
                'stock_movements.item_id',
                'stock_movements.branch_id',
                'products.name',
                'products.unit',
                'categories.name',
                'branches.name'
            );

        // Apply filter: low stock or out of stock
        if ($this->filter === 'out') {
            // Out of stock: qty = 0 (exactly zero, not negative or positive)
            $rows = $rows->havingRaw("net_qty = 0");
        } else {
            // Low stock: qty > 0 AND qty <= threshold
            $rows = $rows->havingRaw("net_qty > 0 AND net_qty <= ?", [$threshold]);
        }
        
        $rows = $rows->orderByRaw('net_qty ASC')->get();

        // Apply search filter in PHP (after DB fetch)
        if ($this->search) {
            $s = strtolower($this->search);
            $rows = $rows->filter(fn($r) =>
                str_contains(strtolower($r->item),           $s) ||
                str_contains(strtolower($r->branch   ?? ''), $s) ||
                str_contains(strtolower($r->category ?? ''), $s)
            )->values();
        }

        $title = $this->filter === 'out' ? 'Out of Stock Items' : 'Low Stock Items';

        return view('livewire.admin.dashboard.low-stock', [
            'items'     => $rows,
            'threshold' => $threshold,
            'filter'    => $this->filter,
            'title'     => $title,
        ])->title($title);
    }
}
