<?php

namespace App\Livewire\Store;

use App\Models\Address;
use App\Services\SalesService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.marketplace')]
class CheckoutPage extends Component
{
    // ── Delivery address fields ──────────────────────────────────────────
    public string $fullName    = '';
    public string $phone       = '';
    public string $city        = '';
    public string $subCity     = '';
    public string $street      = '';
    public string $notes       = '';

    public string $discountCode    = '';
    public string $errorMessage    = '';
    public bool   $saveAddress     = true;

    // ── Address selection ────────────────────────────────────────────────
    public ?int   $selectedAddressId = null;
    public bool   $useNewAddress     = false;

    public function mount(): void
    {
        $customer = auth('customer')->user();

        if (!$customer) {
            $this->redirectRoute('customer.login');
            return;
        }

        // Pre-fill from customer profile
        $this->fullName = $customer->name;
        $this->phone    = $customer->phone ?? '';

        // Load default address if it exists
        $default = $customer->addresses()->where('is_default', true)->first()
            ?? $customer->addresses()->latest()->first();

        if ($default) {
            $this->selectedAddressId = $default->id;
            $this->useNewAddress     = false;
        } else {
            $this->useNewAddress = true;
        }
    }

    public function updatedSelectedAddressId(): void
    {
        $this->useNewAddress = ($this->selectedAddressId === null);
    }

    protected function rules(): array
    {
        $rules = [
            'discountCode' => 'nullable|string|max:50',
        ];

        if ($this->useNewAddress || $this->selectedAddressId === null) {
            $rules['city']    = 'required|string|max:100';
            $rules['street']  = 'required|string|max:255';
        }

        return $rules;
    }

    public function placeOrder(): void
    {
        $this->errorMessage = '';

        $customer = auth('customer')->user();
        if (!$customer) {
            $this->redirectRoute('customer.login');
            return;
        }

        $this->validate();

        // Resolve address
        $addressId = null;
        if (!$this->useNewAddress && $this->selectedAddressId) {
            // Verify the address belongs to this customer
            $addr = Address::where('id', $this->selectedAddressId)
                ->where('customer_id', $customer->id)
                ->first();
            $addressId = $addr?->id;
        }

        if (!$addressId && $this->useNewAddress) {
            $newAddr = Address::create([
                'customer_id'  => $customer->id,
                'address_type' => 'delivery',
                'city'         => $this->city,
                'sub_city'     => $this->subCity ?: null,
                'street'       => $this->street,
                'is_default'   => !$customer->addresses()->exists(),
            ]);
            $addressId = $newAddr->id;
        }

        try {
            $result = app(SalesService::class)->checkout(
                customerId:         $customer->id,
                deliveryAddressId:  $addressId,
                orderDiscountCode:  $this->discountCode ?: null,
                notes:              $this->notes,
            );

            session()->forget('cart_discount_code');

            $this->redirectRoute(
                'store.order.confirmation',
                ['orderNumber' => $result['order']->order_number]
            );
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        $customer = auth('customer')->user();

        if (!$customer) {
            return view('livewire.store.checkout', [
                'summary'   => null,
                'addresses' => collect(),
            ]);
        }

        $discountCode = $this->discountCode ?: session('cart_discount_code');
        $summary      = app(SalesService::class)->getCartSummary($customer->id, $discountCode);
        $addresses    = $customer->addresses()->orderByDesc('is_default')->get();

        return view('livewire.store.checkout', compact('summary', 'addresses'))
            ->title('Checkout — Al-Baraka Store');
    }
}
