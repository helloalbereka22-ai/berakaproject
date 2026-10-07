<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StoreService
{
   
    public function marketplaceQuery(array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $query = Product::withoutGlobalScopes()
            ->where('status', Product::STATUS_ACTIVE)
            ->where('is_marketplace_visible', true)
            ->whereIn('type', ['book', 'stationery', 'fashion', 'general', 'finished_product', 'bottle_product'])
            ->with(['category', 'business'])
            ->orderBy('sort_order')
            ->orderBy('name');

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('sku', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        // Handle 'book' type filter - map to products from bookstore business with 'general' type
        if (!empty($filters['type'])) {
            if ($filters['type'] === 'book') {
                // For 'book' filter, show products from bookstore business
                $bookstoreBusiness = \App\Models\Business::where('name', 'like', '%book%')->first();
                if ($bookstoreBusiness) {
                    $query->where('business_id', $bookstoreBusiness->id);
                }
            } else {
                $query->where('type', $filters['type']);
            }
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $query->where('selling_price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $query->where('selling_price', '<=', $filters['max_price']);
        }

        return $query;
    }

    /**
     * Manufacturing products query - ONLY manufactured oils and bottles
     */
    public function manufacturingQuery(array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $query = Product::withoutGlobalScopes()
            ->where('status', Product::STATUS_ACTIVE)
            ->where('is_marketplace_visible', true)
            ->whereIn('type', ['finished_product', 'bottle_product'])  // ONLY manufactured products
            ->with(['category', 'business'])
            ->orderBy('sort_order')
            ->orderBy('name');

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('sku', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $query->where('selling_price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $query->where('selling_price', '<=', $filters['max_price']);
        }

        return $query;
    }

    /**
     * Featured products — is_featured=true.
     */
    public function getFeatured(int $limit = 8): Collection
    {
        return Product::withoutGlobalScopes()
            ->where('status', Product::STATUS_ACTIVE)
            ->where('is_marketplace_visible', true)
            ->where('is_featured', true)
            ->whereIn('type', Product::SELLABLE_TYPES)
            ->with(['category'])
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();
    }

    /**
     * Best sellers — products with the most confirmed order item lines.
     */
    public function getBestSellers(int $limit = 8): Collection
    {
        return Product::withoutGlobalScopes()
            ->where('status', Product::STATUS_ACTIVE)
            ->where('is_marketplace_visible', true)
            ->whereIn('type', Product::SELLABLE_TYPES)
            ->withCount(['orderItems as sales_count'])
            ->with(['category'])
            ->orderByDesc('sales_count')
            ->limit($limit)
            ->get();
    }

    /**
     * New arrivals — most recently created.
     */
    public function getNewArrivals(int $limit = 8): Collection
    {
        return Product::withoutGlobalScopes()
            ->where('status', Product::STATUS_ACTIVE)
            ->where('is_marketplace_visible', true)
            ->whereIn('type', Product::SELLABLE_TYPES)
            ->with(['category'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get a product with full detail for the product page.
     * Hides internal cost, business structure, etc.
     */
    public function getProductDetail(int $productId): Product
    {
        $product = Product::withoutGlobalScopes()
            ->where('status', Product::STATUS_ACTIVE)
            ->where('is_marketplace_visible', true)
            ->with(['category', 'business'])
            ->findOrFail($productId);

        return $product;
    }

    /**
     * Get all active marketplace categories (used for filters).
     */
    public function getMarketplaceCategories(): Collection
    {
        return Category::withoutGlobalScopes()
            ->where('is_active', true)
            ->whereHas('products', fn($q) =>
                $q->where('status', Product::STATUS_ACTIVE)
                  ->where('is_marketplace_visible', true)
            )
            ->withCount(['products as marketplace_product_count' => fn($q) =>
                $q->where('status', Product::STATUS_ACTIVE)
                  ->where('is_marketplace_visible', true)
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getWishlist(): array
    {
        return session()->get('wishlist', []);
    }

    public function addToWishlist(int $productId): void
    {
        $wishlist = $this->getWishlist();
        if (!in_array($productId, $wishlist)) {
            $wishlist[] = $productId;
            session()->put('wishlist', $wishlist);
        }
    }

    public function removeFromWishlist(int $productId): void
    {
        $wishlist = array_filter($this->getWishlist(), fn($id) => $id !== $productId);
        session()->put('wishlist', array_values($wishlist));
    }

    public function getWishlistProducts(): Collection
    {
        $ids = $this->getWishlist();
        if (empty($ids)) return collect();

        return Product::withoutGlobalScopes()
            ->whereIn('id', $ids)
            ->where('status', Product::STATUS_ACTIVE)
            ->with(['category'])
            ->get();
    }

    /**
     * Build a public product array — omit internal cost, business structure, warehouse info.
     */
    public function toPublicArray(Product $product): array
    {
        $base = [
            'id'           => $product->id,
            'name'         => $product->name,
            'short_name'   => $product->short_name,
            'sku'          => $product->sku,
            'type'         => $product->type,
            'type_label'   => $product->getTypeLabel(),
            'description'  => $product->description,
            'selling_price'=> (float) $product->selling_price,
            'unit'         => $product->unit,
            'package_size' => $product->package_size,
            'is_featured'  => (bool) $product->is_featured,
            'category'     => optional($product->category)->name,
            'category_id'  => $product->category_id,
            // Never expose: cost_price, business_id internals, warehouse
        ];

        // Type-specific fields
        if ($product->type === Product::TYPE_BOOK) {
            $base['isbn'] = $product->barcode;
        }

        if (in_array($product->type, [Product::TYPE_FINISHED_PRODUCT, Product::TYPE_BOTTLE_PRODUCT, Product::TYPE_GENERAL])) {
            $base['package_size'] = $product->package_size;
        }

        return $base;
    }
}
