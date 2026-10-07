<?php

namespace App\Services;

use App\Models\Discount;
use App\Models\Product;
use App\Models\Promotion;

class MarketingService
{
    /**
     * Calculate the best applicable discount for a single product line.
     * Checks in order: product-level, category-level.
     * Returns: ['discount_amount' => float, 'discount_type' => string, 'final_price' => float]
     */
    public function applyProductDiscount(Product $product, float $unitPrice): array
    {
        $now = now();

        // 1. Product-level promotion
        $promotion = Promotion::where('scope_type', 'product')
            ->where('scope_id', $product->id)
            ->where('status', 'active')
            ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->with('discount')
            ->first();

        // 2. Category-level promotion
        if (!$promotion && $product->category_id) {
            $promotion = Promotion::where('scope_type', 'category')
                ->where('scope_id', $product->category_id)
                ->where('status', 'active')
                ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
                ->where(fn($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
                ->with('discount')
                ->first();
        }

        if (!$promotion || !$promotion->discount) {
            return [
                'discount_amount' => 0.0,
                'discount_type'   => null,
                'final_price'     => $unitPrice,
            ];
        }

        $discount       = $promotion->discount;
        $discountAmount = $this->calculateDiscountAmount($discount, $unitPrice);
        $finalPrice     = max(0, $unitPrice - $discountAmount);

        return [
            'discount_amount' => round($discountAmount, 4),
            'discount_type'   => $discount->type,
            'final_price'     => round($finalPrice, 4),
        ];
    }

    /**
     * Apply an order-level discount on the subtotal.
     */
    public function applyOrderDiscount(float $subtotal, ?string $discountCode = null): array
    {
        if (!$discountCode) {
            return ['discount_amount' => 0.0, 'discount_type' => null, 'final_total' => $subtotal];
        }

        $discount = Discount::where('code', $discountCode)
            ->where('status', 'active')
            ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->first();

        if (!$discount) {
            return ['discount_amount' => 0.0, 'discount_type' => null, 'final_total' => $subtotal];
        }

        // Check minimum order amount
        if ($discount->minimum_order_amount && $subtotal < $discount->minimum_order_amount) {
            return ['discount_amount' => 0.0, 'discount_type' => null, 'final_total' => $subtotal];
        }

        $discountAmount = $this->calculateDiscountAmount($discount, $subtotal);

        // Respect maximum discount cap
        if ($discount->maximum_discount_amount && $discountAmount > $discount->maximum_discount_amount) {
            $discountAmount = (float) $discount->maximum_discount_amount;
        }

        return [
            'discount_amount' => round($discountAmount, 4),
            'discount_type'   => $discount->type,
            'final_total'     => round(max(0, $subtotal - $discountAmount), 4),
        ];
    }

    /**
     * Calculate discount amount from a Discount record against a base price.
     */
    public function calculateDiscountAmount(Discount $discount, float $baseAmount): float
    {
        return match ($discount->type) {
            'percentage'   => $baseAmount * ((float) $discount->value / 100),
            'fixed_amount' => min((float) $discount->value, $baseAmount),
            default        => 0.0,
        };
    }
}
