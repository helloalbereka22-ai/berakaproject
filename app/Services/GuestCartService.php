<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Session;

class GuestCartService
{
    private const SESSION_KEY = 'guest_cart';

    /** Get all cart items from session */
    public function getCart(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    /** Add a product to the guest cart */
    public function addToCart(int $productId, float $quantity = 1): bool
    {
        $cart = $this->getCart();

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            // Find product — only check for active status
            $product = Product::withoutGlobalScopes()
                ->find($productId);
            
            if (!$product) {
                return false; // Product doesn't exist
            }

            $primaryImage = $product->primaryImage;
            $imagePath = null;
            if ($primaryImage && isset($primaryImage['path'])) {
                $imagePath = $primaryImage['path'];
            }

            $cart[$productId] = [
                'product_id'    => $productId,
                'name'          => $product->name,
                'price'         => (float) ($product->selling_price ?? 0),
                'unit'          => $product->unit ?? '',
                'image'         => $imagePath,
                'quantity'      => $quantity,
            ];
        }

        Session::put(self::SESSION_KEY, $cart);
        return true;
    }

    /** Update quantity — remove if 0 or less */
    public function updateQuantity(int $productId, float $quantity): void
    {
        $cart = $this->getCart();
        if ($quantity <= 0) {
            unset($cart[$productId]);
        } else {
            if (isset($cart[$productId])) {
                $cart[$productId]['quantity'] = $quantity;
            }
        }
        Session::put(self::SESSION_KEY, $cart);
    }

    /** Remove a product */
    public function removeFromCart(int $productId): void
    {
        $cart = $this->getCart();
        unset($cart[$productId]);
        Session::put(self::SESSION_KEY, $cart);
    }

    /** Clear the entire cart */
    public function clearCart(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /** Get total item count */
    public function getCount(): int
    {
        return count($this->getCart());
    }

    /** Get cart summary with totals */
    public function getSummary(): array
    {
        $cart     = $this->getCart();
        $subtotal = 0;

        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        return [
            'items'    => array_values($cart),
            'count'    => count($cart),
            'subtotal' => round($subtotal, 2),
        ];
    }
}
