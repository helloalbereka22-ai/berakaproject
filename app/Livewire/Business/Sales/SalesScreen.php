<?php

namespace App\Livewire\Business\Sales;

use App\Customer;
use App\Models\Product;
use App\Services\SalesService;
use Livewire\Component;

class SalesScreen extends Component
{
    public $businessId;
    public $branchId;
    
    // Cart state
    public $cart = [];
    public $customerId = null;
    public $subtotal = 0;
    public $discount = 0;
    public $total = 0;
    
    // Search states
    public $productSearch = '';
    public $availableProducts = [];
    public $customers = [];

    public function mount()
    {
        // In a real app: auth()->user()->business_id
        $this->businessId = 1;
        $this->branchId = 1; 
        
        $this->loadCustomers();
    }

    public function updatedProductSearch()
    {
        if (strlen($this->productSearch) > 2) {
            // Fetch products where the branch has inventory > 0
            $this->availableProducts = Product::query()
                ->join('stock_movements', function ($join) {
                    $join->on('products.id', '=', 'stock_movements.item_id')
                         ->where('stock_movements.item_type', 'product');
                })
                ->where('stock_movements.business_id', $this->businessId)
                ->where('stock_movements.branch_id', $this->branchId)
                ->where('products.name', 'like', '%' . $this->productSearch . '%')
                ->select(
                    'products.*',
                    \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN stock_movements.direction = 'in' THEN stock_movements.quantity ELSE -stock_movements.quantity END) as quantity_on_hand")
                )
                ->groupBy('products.id')
                ->having('quantity_on_hand', '>', 0)
                ->get();
        } else {
            $this->availableProducts = [];
        }
    }

    public function loadCustomers()
    {
        $this->customers = Customer::where('business_id', $this->businessId)->get();
    }

    public function addToCart(int $productId, string $name, float $price, int $maxStock)
    {
        if (isset($this->cart[$productId])) {
            if ($this->cart[$productId]['quantity'] < $maxStock) {
                $this->cart[$productId]['quantity']++;
            } else {
                session()->flash('error', "Maximum stock reached for {$name}.");
                return;
            }
        } else {
            $this->cart[$productId] = [
                'product_id' => $productId,
                'name' => $name,
                'price' => $price,
                'quantity' => 1,
                'discount' => 0,
            ];
        }
        
        $this->calculateTotals();
        $this->productSearch = '';
        $this->availableProducts = [];
    }

    public function removeFromCart($productId)
    {
        unset($this->cart[$productId]);
        $this->calculateTotals();
    }

    public function updateQuantity($productId, $quantity)
    {
        if ($quantity > 0) {
            $this->cart[$productId]['quantity'] = $quantity;
            $this->calculateTotals();
        }
    }

    public function calculateTotals()
    {
        $this->subtotal = 0;
        foreach ($this->cart as $item) {
            $this->subtotal += ($item['price'] * $item['quantity']) - $item['discount'];
        }
        $this->total = $this->subtotal - $this->discount;
    }

    public function checkout(SalesService $salesService, string $type = 'completed_sale')
    {
        if (empty($this->cart)) {
            session()->flash('error', 'Cart is empty.');
            return;
        }

        try {
            // Create Order/Quotation
            $order = $salesService->createOrder(
                $this->businessId,
                $this->branchId,
                auth()->id() ?? 1,
                array_values($this->cart),
                $this->customerId ?: null,
                $type === 'completed_sale' ? 'sales_order' : 'quotation'
            );

            if ($type === 'completed_sale') {
                // If it's a direct sale (POS checkout), deduct stock instantly
                $salesService->completeSale($order->id, auth()->id() ?? 1);
                
                // For demo, assume cash payment in full
                $salesService->processPayment($order->id, $order->total_amount, 'cash');
                
                session()->flash('success', 'Sale completed successfully! Order: ' . $order->order_number);
            } else {
                session()->flash('success', 'Quotation saved successfully! Ref: ' . $order->order_number);
            }

            // Clear Cart
            $this->cart = [];
            $this->customerId = null;
            $this->calculateTotals();

        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.business.sales.sales-screen');
    }
}
