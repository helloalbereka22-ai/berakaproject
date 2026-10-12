<?php

namespace App\Livewire\Store;

use App\Services\GuestCartService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.marketplace')]
class GuestCart extends Component
{
    public string $customerName  = '';
    public string $customerPhone = '';
    public string $customerEmail = '';
    public string $address       = '';
    public string $notes         = '';
    public bool   $orderPlaced   = false;
    public string $orderNumber   = '';
    public string $errorMessage  = '';

    public function removeItem(int $productId): void
    {
        app(GuestCartService::class)->removeFromCart($productId);
        $this->dispatch('cart-updated');
    }

    public function updateQty(int $productId, float $qty): void
    {
        app(GuestCartService::class)->updateQuantity($productId, $qty);
    }

    public function placeOrder(): void
    {
        $this->errorMessage = '';

        $this->validate([
            'customerName'  => 'required|min:2',
            'customerPhone' => [
                'required',
                function ($attribute, $value, $fail) {
                    // Remove all non-numeric characters
                    $phone = preg_replace('/[^0-9]/', '', $value);
                    
                    // Check length
                    if (strlen($phone) > 10) {
                        $fail('Phone number greater than 10 is not allowed.');
                        return;
                    }
                    
                    // Check if exactly 10 digits
                    if (strlen($phone) !== 10) {
                        $fail('Phone number must be exactly 10 digits.');
                        return;
                    }
                    
                    // Check if starts with 09 or 07
                    if (!preg_match('/^(09|07)/', $phone)) {
                        $fail('Phone number must start with 09 or 07.');
                        return;
                    }
                }
            ],
            'address'       => 'required|min:5',
        ]);

        $cart = app(GuestCartService::class)->getSummary();

        if (empty($cart['items'])) {
            $this->errorMessage = 'Your cart is empty.';
            return;
        }

        $this->orderNumber = 'ORD-' . strtoupper(substr(md5(uniqid()), 0, 8));

        try {
            // Create table if missing
            if (!Schema::hasTable('guest_orders')) {
                Schema::create('guest_orders', function ($table) {
                    $table->id();
                    $table->string('order_number')->unique();
                    $table->string('customer_name');
                    $table->string('customer_phone');
                    $table->string('customer_email')->nullable();
                    $table->text('address');
                    $table->text('notes')->nullable();
                    $table->json('items');
                    $table->decimal('total_amount', 12, 2)->default(0);
                    $table->string('status')->default('pending');
                    $table->timestamps();
                });
            }

            DB::table('guest_orders')->insert([
                'order_number'   => $this->orderNumber,
                'customer_name'  => $this->customerName,
                'customer_phone' => $this->customerPhone,
                'customer_email' => $this->customerEmail ?: null,
                'address'        => $this->address,
                'notes'          => $this->notes ?: null,
                'items'          => json_encode($cart['items']),
                'total_amount'   => $cart['subtotal'],
                'status'         => 'pending',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

        } catch (\Throwable $e) {
            $this->errorMessage = 'Error saving order: ' . $e->getMessage();
            return;
        }

        app(GuestCartService::class)->clearCart();
        $this->orderPlaced = true;
        $this->dispatch('cart-updated');
    }

    public function render()
    {
        $summary = app(GuestCartService::class)->getSummary();
        return view('livewire.store.guest-cart', compact('summary'))
            ->title('Your Cart — Al-Baraka');
    }
}
