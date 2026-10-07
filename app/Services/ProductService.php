<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    // ── Create ────────────────────────────────────────────────────────────

    public function create(array $data, int $businessId): Product
    {
        return DB::transaction(function () use ($data, $businessId) {
            $data['business_id'] = $businessId;
            $data['sku'] = $data['sku'] ?? $this->generateSku($data['type'] ?? 'general', $data['name']);

            $product = Product::create($data);

            AuditService::logCreate('App\\Models\\Product', $product, $businessId);

            return $product;
        });
    }

    // ── Update ────────────────────────────────────────────────────────────

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $original = $product->toArray();

            // Never allow changing business_id via update
            unset($data['business_id']);

            $product->update($data);

            AuditService::logUpdate('App\\Models\\Product', $product, $original, $product->business_id);

            return $product->fresh();
        });
    }

    // ── Delete (soft) ─────────────────────────────────────────────────────

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product) {
            AuditService::logDelete('App\\Models\\Product', $product, $product->business_id);
            $product->delete();
        });
    }

    // ── Toggle status ─────────────────────────────────────────────────────

    public function toggleStatus(Product $product): Product
    {
        $original = $product->toArray();
        $product->status = $product->status === Product::STATUS_ACTIVE
            ? Product::STATUS_INACTIVE
            : Product::STATUS_ACTIVE;
        $product->save();

        AuditService::logUpdate('App\\Models\\Product', $product, $original, $product->business_id);

        return $product;
    }

    // ── Toggle marketplace visibility ─────────────────────────────────────

    public function toggleMarketplace(Product $product): Product
    {
        if (!$product->isSellable()) {
            throw new \RuntimeException("Product type '{$product->type}' cannot be listed on the marketplace.");
        }

        $original = $product->toArray();
        $product->is_marketplace_visible = !$product->is_marketplace_visible;
        $product->save();

        AuditService::logUpdate('App\\Models\\Product', $product, $original, $product->business_id);

        return $product;
    }

    // ── SKU generation ────────────────────────────────────────────────────

    public function generateSku(string $type, string $name): string
    {
        $prefix = match ($type) {
            Product::TYPE_RAW_MATERIAL     => 'RM',
            Product::TYPE_PACKING_MATERIAL => 'PKG',
            Product::TYPE_BOOK             => 'BK',
            Product::TYPE_STATIONERY       => 'STA',
            Product::TYPE_FASHION          => 'FSH',
            default                        => 'PRD',
        };

        $slug = strtoupper(substr(Str::slug($name, ''), 0, 6));
        $seq  = str_pad(Product::withoutGlobalScopes()->count() + 1, 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$slug}-{$seq}";
    }

    // ── Category create ───────────────────────────────────────────────────

    public function createCategory(array $data, int $businessId): Category
    {
        return DB::transaction(function () use ($data, $businessId) {
            $data['business_id'] = $businessId;
            $category = Category::create($data);

            AuditService::logCreate('App\\Models\\Category', $category, $businessId);

            return $category;
        });
    }

    // ── Category update ───────────────────────────────────────────────────

    public function updateCategory(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            $original = $category->toArray();
            unset($data['business_id']);

            $category->update($data);
            AuditService::logUpdate('App\\Models\\Category', $category, $original, $category->business_id);

            return $category->fresh();
        });
    }

    // ── Category delete ───────────────────────────────────────────────────

    public function deleteCategory(Category $category): void
    {
        if ($category->products()->exists()) {
            throw new \RuntimeException('Cannot delete a category that has products assigned to it.');
        }
        if ($category->children()->exists()) {
            throw new \RuntimeException('Cannot delete a category that has subcategories.');
        }

        DB::transaction(function () use ($category) {
            AuditService::logDelete('App\\Models\\Category', $category, $category->business_id);
            $category->delete();
        });
    }

    // ── Marketplace query ─────────────────────────────────────────────────

    public function marketplaceQuery(array $filters = [])
    {
        $query = Product::withoutGlobalScopes()
            ->marketplace()
            ->with(['category', 'business'])
            ->orderBy('sort_order')
            ->orderBy('name');

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        if (!empty($filters['min_price'])) {
            $query->where('selling_price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('selling_price', '<=', $filters['max_price']);
        }

        if (!empty($filters['featured'])) {
            $query->where('is_featured', true);
        }

        return $query;
    }
}
