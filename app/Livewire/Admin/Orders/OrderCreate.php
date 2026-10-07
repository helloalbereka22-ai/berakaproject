<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class OrderCreate extends Component
{
    // ── Customer ──────────────────────────────────────────────────────────
    public string $customerSearch   = '';
    public ?int   $customer_id      = null;
    public string $customerName     = '';
    public bool   $showCustomerList = false;

    // ── Walk-in customer (no account) ────────────────────────────────────
    public bool   $walkIn        = false;
    public string $walkInName    = '';
    public string $walkInPhone   = '';

    // ── Context (set from seller's assigned branch, or selectable for admin) ─
    public ?int   $branchId   = null;
    public ?int   $businessId = null;
    
    // ── Branch/Business lists for super admin to select from ──────────────
    public array  $branches = [];
    public array  $businesses = [];

    // ── Product picker ────────────────────────────────────────────────────
    public string $productSearch    = '';
    public string $filterCategoryId = '';

    // ── Cart items: [['product_id','name','unit','price','qty','stock']] ─
    public array  $cartItems = [];

    // ── Order meta ────────────────────────────────────────────────────────
    public string $notes          = '';
    public string $paymentMethod  = 'cash';

    // ── Messages ─────────────────────────────────────────────────────────
    public string $errorMessage   = '';

    // ─────────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            // Super admin can select any business/branch
            $this->businesses = Business::active()->orderBy('name')->get(['id', 'name'])->toArray();
        } else {
            // Regular seller gets assigned branch
            $branchIds = $user->assignedBranchIds();
            $this->branchId = $branchIds[0] ?? null;
            if ($this->branchId) {
                $branch = Branch::find($this->branchId);
                $this->businessId = $branch?->business_id;
            }
        }
    }

    // ── Customer search ───────────────────────────────────────────────────

    public function updatedCustomerSearch(): void
    {
        $this->showCustomerList = strlen($this->customerSearch) >= 2;
        $this->customer_id      = null;
        $this->customerName     = '';
    }

    #[Computed]
    public function customerResults(): \Illuminate\Database\Eloquent\Collection
    {
        if (strlen($this->customerSearch) < 2) return collect();
        return Customer::where(function ($q) {
            $q->where('name', 'like', '%' . $this->customerSearch . '%')
              ->orWhere('phone', 'like', '%' . $this->customerSearch . '%')
              ->orWhere('email', 'like', '%' . $this->customerSearch . '%');
        })->limit(8)->get(['id', 'name', 'phone', 'email']);
    }

    public function selectCustomer(int $id, string $name): void
    {
        $this->customer_id      = $id;
        $this->customerName     = $name;
        $this->customerSearch   = $name;
        $this->showCustomerList = false;
    }

    public function toggleWalkIn(): void
    {
        $this->walkIn       = !$this->walkIn;
        $this->customer_id  = null;
        $this->customerName = '';
        $this->customerSearch = '';
        $this->showCustomerList = false;
    }

    // ── Product search / picker ───────────────────────────────────────────

    public function updatedProductSearch(): void   { unset($this->availableProducts); }
    public function updatedFilterCategoryId(): void { unset($this->availableProducts); }
    
    public function updatedBusinessId(): void 
    {
        $this->branchId = null;
        $this->branches = [];
        if ($this->businessId) {
            $this->branches = Branch::where('business_id', $this->businessId)
                ->active()->orderBy('name')->get(['id', 'name'])->toArray();
        }
        unset($this->categories);
    }
    
    public function updatedBranchId(): void 
    {
        unset($this->availableProducts);
    }

    #[Computed]
    public function categories(): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->businessId) return collect();
        return Category::withoutGlobalScopes()
            ->where('business_id', $this->businessId)
            ->active()
            ->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function availableProducts(): \Illuminate\Support\Collection
    {
        if (!$this->branchId) return collect();

        $locationIds = Location::where('branch_id', $this->branchId)->pluck('id');

        $stock = StockMovement::whereIn('location_id', $locationIds)
            ->where('item_type', 'product')
            ->join('products', 'stock_movements.item_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereIn('products.type', Product::SALEABLE_PRODUCT_TYPES)
            ->where('products.status', Product::STATUS_ACTIVE)
            ->whereNull('products.deleted_at')
            ->when($this->filterCategoryId, fn($q) =>
                $q->where('products.category_id', $this->filterCategoryId)
            )
            ->when($this->productSearch, fn($q) =>
                $q->where('products.name', 'like', '%' . $this->productSearch . '%')
            )
            ->select(
                'products.id',
                'products.name',
                'products.unit',
                'products.selling_price',
                'products.cost_price',
                'categories.name as category_name',
                DB::raw("SUM(CASE WHEN stock_movements.direction='in' THEN stock_movements.quantity ELSE -stock_movements.quantity END) as qty_on_hand")
            )
            ->groupBy(
                'products.id', 'products.name', 'products.unit',
                'products.selling_price', 'products.cost_price', 'categories.name'
            )
            ->having('qty_on_hand', '>', 0)
            ->orderBy('products.name')
            ->get();

        // Exclude already-added items
        $addedIds = collect($this->cartItems)->pluck('product_id')->toArray();
        return $stock->whereNotIn('id', $addedIds)->values();
    }

    public function addToCart(int $productId): void
    {
        $product = $this->availableProducts->firstWhere('id', $productId);
        if (!$product) return;

        // Already in cart check
        foreach ($this->cartItems as $item) {
            if ($item['product_id'] === $productId) return;
        }

        $this->cartItems[] = [
            'product_id'    => $productId,
            'name'          => $product->name,
            'unit'          => $product->unit,
            'price'         => (float) $product->selling_price,
            'cost'          => (float) $product->cost_price,
            'qty'           => 1,
            'stock'         => (float) $product->qty_on_hand,
            'category_name' => $product->category_name,
        ];

        unset($this->availableProducts);
        $this->productSearch = '';
    }

    public function removeFromCart(int $index): void
    {
        unset($this->cartItems[$index]);
        $this->cartItems = array_values($this->cartItems);
        unset($this->availableProducts);
    }

    public function updatedCartItems(): void
    {
        // Clamp quantities to available stock
        foreach ($this->cartItems as $i => $item) {
            $qty = max(0.001, (float) ($item['qty'] ?? 1));
            if ($qty > $item['stock']) {
                $qty = $item['stock'];
            }
            $this->cartItems[$i]['qty'] = $qty;
        }
    }

    // ── Totals ────────────────────────────────────────────────────────────

    #[Computed]
    public function subtotal(): float
    {
        return collect($this->cartItems)->sum(fn($i) => (float)$i['price'] * (float)$i['qty']);
    }

    // ── Place order ───────────────────────────────────────────────────────

    public function placeOrder(): void
    {
        $this->errorMessage = '';

        // Validate customer
        if (!$this->walkIn && !$this->customer_id) {
            $this->errorMessage = 'Please select a customer or enable walk-in mode.';
            return;
        }
        if ($this->walkIn && empty(trim($this->walkInName))) {
            $this->errorMessage = 'Please enter the walk-in customer name.';
            return;
        }

        // Validate cart
        if (empty($this->cartItems)) {
            $this->errorMessage = 'Add at least one product to the order.';
            return;
        }

        // Validate quantities
        foreach ($this->cartItems as $item) {
            if ((float)$item['qty'] <= 0) {
                $this->errorMessage = "Quantity for \"{$item['name']}\" must be greater than 0.";
                return;
            }
            if ((float)$item['qty'] > $item['stock']) {
                $this->errorMessage = "Quantity for \"{$item['name']}\" exceeds available stock ({$item['stock']} {$item['unit']}).";
                return;
            }
        }

        $customerId = $this->customer_id;

        // Create walk-in customer on the fly if needed
        if ($this->walkIn) {
            $walkin = Customer::create([
                'name'   => trim($this->walkInName),
                'phone'  => trim($this->walkInPhone) ?: null,
                'status' => 'active',
            ]);
            $customerId = $walkin->id;
        }

        $subtotal = collect($this->cartItems)->sum(fn($i) => (float)$i['price'] * (float)$i['qty']);

        DB::transaction(function () use ($customerId, $subtotal) {
            $order = Order::create([
                'business_id'    => $this->businessId,
                'branch_id'      => $this->branchId,
                'customer_id'    => $customerId,
                'order_number'   => Order::generateOrderNumber(),
                'status'         => Order::STATUS_CONFIRMED,
                'payment_status' => Order::PAYMENT_APPROVED,
                'payment_approved_at' => now(),
                'payment_approved_by' => auth()->id(),
                'subtotal'       => $subtotal,
                'discount_amount'=> 0,
                'delivery_fee'   => 0,
                'tax_amount'     => 0,
                'total_amount'   => $subtotal,
                'notes'          => $this->notes,
                'currency'       => 'ETB',
                'placed_at'      => now(),
            ]);

            foreach ($this->cartItems as $item) {
                OrderItem::create([
                    'order_id'             => $order->id,
                    'business_id'          => $this->businessId,
                    'product_id'           => $item['product_id'],
                    'fulfilling_branch_id' => $this->branchId,
                    'quantity'             => (float) $item['qty'],
                    'unit_price'           => (float) $item['price'],
                    'original_price'       => (float) $item['price'],
                    'cost_price_snapshot'  => (float) $item['cost'],
                    'discount_amount'      => 0,
                    'total_amount'         => (float) $item['price'] * (float) $item['qty'],
                ]);
            }
        });

        session()->flash('order_success', 'Order placed successfully.');
        $this->redirect(route('admin.branch-seller.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.orders.create')
            ->title('New Order');
    }
}
