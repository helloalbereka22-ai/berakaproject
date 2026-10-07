<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\Translatable;

class Product extends Model
{
    use SoftDeletes, Translatable;
    use \App\Traits\BelongsToBusiness;

    // ── Product types ────────────────────────────────────────────────────

    // ┌─────────────────────────────────────────────────────────────────┐
    // │  MANUFACTURING DOMAIN  (never sold directly to customers)       │
    // └─────────────────────────────────────────────────────────────────┘
    const TYPE_RAW_MATERIAL         = 'raw_material';      // e.g. palm oil, seed
    const TYPE_PACKING_MATERIAL     = 'packing_material';  // e.g. carton, bottle
    const TYPE_FINISHED_PRODUCT     = 'finished_product';  // manufactured oil, etc.
    const TYPE_BOTTLE_PRODUCT       = 'bottle_product';    // bottled product output

    // ┌─────────────────────────────────────────────────────────────────┐
    // │  SALEABLE DOMAIN  (sold to customers)                          │
    // └─────────────────────────────────────────────────────────────────┘
    const TYPE_BOOK                 = 'book';       // bookstore - Computer Sci, Programming...
    const TYPE_STATIONERY           = 'stationery'; // Notebook, Pen, Pencil, Bag...
    const TYPE_FASHION              = 'fashion';    // Jalabiya, Dress, Shirt...
    const TYPE_GENERAL              = 'general';    // kept for backward compat — not shown in forms

    const TYPES = [
        // ─ Manufacturing ─────────────────────────────────────────────────────────────
        self::TYPE_RAW_MATERIAL         => 'Manufacturing - Raw Material',
        self::TYPE_PACKING_MATERIAL     => 'Manufacturing - Packaging Material',
        self::TYPE_FINISHED_PRODUCT     => 'Manufacturing - Finished Product',
        self::TYPE_BOTTLE_PRODUCT       => 'Manufacturing - Bottle Product',
        // ─ Saleable ───────────────────────────────────────────────────────────────
        self::TYPE_BOOK                 => 'Saleable - Book',
        self::TYPE_STATIONERY           => 'Saleable - Stationery',
        self::TYPE_FASHION              => 'Saleable - Fashion',
    ];

    // ── Domain 1: RAW MATERIALS ──────────────────────────────────────────
    const RAW_MATERIAL_TYPES = [
        self::TYPE_RAW_MATERIAL,
    ];

    // ── Domain 2: PACKAGING MATERIALS ─────────────────────────────────────
    const PACKAGING_MATERIAL_TYPES = [
        self::TYPE_PACKING_MATERIAL,
    ];

    // ── Domain 3: SALEABLE PRODUCTS ───────────────────────────────────────
    const SALEABLE_PRODUCT_TYPES = [
        self::TYPE_FINISHED_PRODUCT,
        self::TYPE_BOTTLE_PRODUCT,
        self::TYPE_BOOK,
        self::TYPE_STATIONERY,
        self::TYPE_FASHION,
        self::TYPE_GENERAL,  // backward compat
    ];

    // ── Legacy alias kept for backward compat ────────────────────────────
    const SELLABLE_TYPES = self::SALEABLE_PRODUCT_TYPES;

    // ── Status ───────────────────────────────────────────────────────────
    const STATUS_ACTIVE   = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_DRAFT    = 'draft';

    const STATUSES = [
        self::STATUS_ACTIVE   => 'Active',
        self::STATUS_INACTIVE => 'Inactive',
        self::STATUS_DRAFT    => 'Draft',
    ];

    protected $fillable = [
        'business_id',
        'parent_id',
        'category_id',
        'type',
        'name',
        'short_name',
        'sku',
        'barcode',
        'unit',
        'package_size',
        'description',
        'selling_price',
        'discount_price',
        'cost_price',
        'reorder_point',
        'min_stock',
        'max_stock',
        'status',
        'is_featured',
        'is_marketplace_visible',
        'sort_order',
        'image_path',
        'metadata',
    ];

    protected $casts = [
        'selling_price'          => 'decimal:4',
        'discount_price'         => 'decimal:4',
        'cost_price'             => 'decimal:4',
        'reorder_point'          => 'decimal:4',
        'min_stock'              => 'decimal:4',
        'max_stock'              => 'decimal:4',
        'is_featured'            => 'boolean',
        'is_marketplace_visible' => 'boolean',
        'metadata'               => 'array',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function parent()
    {
        return $this->belongsTo(Product::class, 'parent_id');
    }

    public function variants()
    {
        return $this->hasMany(Product::class, 'parent_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function attributeValues()
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function collections()
    {
        return $this->belongsToMany(ProductCollection::class, 'collection_product')
            ->withPivot('sort_order');
    }

    public function getCurrentStockAttribute()
    {
        if ($this->relationLoaded('stockMovements')) {
            return $this->stockMovements->reduce(function ($carry, $mov) {
                return $carry + ($mov->direction === 'in' ? $mov->quantity : -$mov->quantity);
            }, 0);
        }
        return (float) $this->stockMovements()->sum(\Illuminate\Support\Facades\DB::raw("CASE WHEN direction = 'in' THEN quantity ELSE -quantity END"));
    }

    public function isLowStock(): bool
    {
        return $this->reorder_point > 0 && $this->current_stock <= $this->reorder_point;
    }

    // ── Image helpers (stored in metadata['images']) ─────────────────────

    /**
     * Get all product images from metadata.
     * Each image: ['path' => string, 'alt' => string, 'sort_order' => int, 'is_primary' => bool]
     *
     * @return array<int, array{path: string, url: string, alt: string, sort_order: int, is_primary: bool}>
     */
    public function getImagesAttribute(): array
    {
        $images = $this->metadata['images'] ?? [];

        if (empty($images)) {
            // Fall back to legacy image_path if present
            if ($this->image_path) {
                return [[
                    'path'       => $this->image_path,
                    'url'        => \Illuminate\Support\Facades\Storage::url($this->image_path),
                    'alt'        => $this->name,
                    'sort_order' => 0,
                    'is_primary' => true,
                ]];
            }
            return [];
        }

        return collect($images)
            ->sortBy('sort_order')
            ->map(fn($img) => [
                'path'       => $img['path'],
                'url'        => \Illuminate\Support\Facades\Storage::url($img['path']),
                'alt'        => $img['alt'] ?? $this->name,
                'sort_order' => (int) ($img['sort_order'] ?? 0),
                'is_primary' => (bool) ($img['is_primary'] ?? false),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Primary image — the first image flagged is_primary, or the first image overall.
     *
     * @return array{path: string, url: string, alt: string}|null
     */
    public function getPrimaryImageAttribute(): ?array
    {
        $images = $this->images;

        if (empty($images)) {
            return null;
        }

        $primary = collect($images)->firstWhere('is_primary', true) ?? $images[0];

        return $primary;
    }

    /**
     * Convenience URL for the primary image (kept for backward compat).
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->primary_image['url'] ?? null;
    }

    /**
     * Add an image to the product's metadata images array.
     * Automatically sets is_primary = true if it is the first image.
     *
     * @param string $path    Storage path (e.g. "products/abc.jpg")
     * @param string $alt     Alt text / caption
     * @param bool   $primary Force this image to be primary
     */
    public function addImage(string $path, string $alt = '', bool $primary = false): void
    {
        $meta   = $this->metadata ?? [];
        $images = $meta['images'] ?? [];

        $isPrimary = $primary || empty($images); // first image is always primary by default

        // Clear existing primary flag if we are setting a new one
        if ($isPrimary) {
            $images = array_map(function ($img) {
                $img['is_primary'] = false;
                return $img;
            }, $images);
        }

        $images[] = [
            'path'       => $path,
            'alt'        => $alt ?: $this->name,
            'sort_order' => count($images),
            'is_primary' => $isPrimary,
        ];

        $meta['images'] = $images;
        $this->metadata = $meta;
        $this->save();
    }

    /**
     * Remove an image by its storage path.
     * If the removed image was primary, the next image becomes primary.
     */
    public function removeImage(string $path): void
    {
        $meta   = $this->metadata ?? [];
        $images = $meta['images'] ?? [];

        $wasPrimary = false;
        $images = array_filter($images, function ($img) use ($path, &$wasPrimary) {
            if ($img['path'] === $path) {
                $wasPrimary = $img['is_primary'] ?? false;
                return false;
            }
            return true;
        });

        $images = array_values($images); // re-index

        // Re-assign primary to first remaining image
        if ($wasPrimary && !empty($images)) {
            $images[0]['is_primary'] = true;
        }

        // Re-assign sort_order
        foreach ($images as $i => $img) {
            $images[$i]['sort_order'] = $i;
        }

        $meta['images'] = $images;
        $this->metadata = $meta;
        $this->save();
    }

    /**
     * Set a specific image as primary by its storage path.
     */
    public function setPrimaryImage(string $path): void
    {
        $meta   = $this->metadata ?? [];
        $images = $meta['images'] ?? [];

        $found = false;
        foreach ($images as &$img) {
            $img['is_primary'] = ($img['path'] === $path);
            if ($img['is_primary']) {
                $found = true;
            }
        }
        unset($img);

        if (!$found) {
            throw new \InvalidArgumentException("Image path '{$path}' not found on this product.");
        }

        $meta['images'] = $images;
        $this->metadata = $meta;
        $this->save();
    }

    /**
     * Reorder images. Pass an array of paths in the desired order.
     * Any path not in the list keeps its current position at the end.
     *
     * @param string[] $orderedPaths
     */
    public function reorderImages(array $orderedPaths): void
    {
        $meta   = $this->metadata ?? [];
        $images = $meta['images'] ?? [];

        $keyed = collect($images)->keyBy('path');
        $reordered = [];

        foreach ($orderedPaths as $i => $path) {
            if ($keyed->has($path)) {
                $img                = $keyed[$path];
                $img['sort_order']  = $i;
                $reordered[]        = $img;
                $keyed->forget($path);
            }
        }

        // Append any images not in the ordered list
        foreach ($keyed as $img) {
            $img['sort_order'] = count($reordered);
            $reordered[]       = $img;
        }

        $meta['images'] = $reordered;
        $this->metadata = $meta;
        $this->save();
    }

    public function stockMovements()
    {
        // stock_movements uses polymorphic item_type / item_id columns
        return $this->morphMany(StockMovement::class, 'item');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    // ── Domain Scopes ────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /** Domain 1: Raw Materials only */
    public function scopeRawMaterials(Builder $query): Builder
    {
        return $query->whereIn('type', self::RAW_MATERIAL_TYPES);
    }

    /** Domain 2: Packaging Materials only */
    public function scopePackagingMaterials(Builder $query): Builder
    {
        return $query->whereIn('type', self::PACKAGING_MATERIAL_TYPES);
    }

    /** Domain 3: All saleable products (manufactured, books, general) */
    public function scopeSaleable(Builder $query): Builder
    {
        return $query->whereIn('type', self::SALEABLE_PRODUCT_TYPES)
                     ->where('status', self::STATUS_ACTIVE);
    }

    /** Domain 3 sub-set: Manufactured finished goods only */
    public function scopeManufactured(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_FINISHED_PRODUCT)
                     ->where('status', self::STATUS_ACTIVE);
    }

    /** Domain 3 sub-set: Bookstore products only */
    public function scopeBooks(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_BOOK)
                     ->where('status', self::STATUS_ACTIVE);
    }

    public function scopeMarketplace(Builder $query): Builder
    {
        return $query->where('is_marketplace_visible', true)
            ->where('status', self::STATUS_ACTIVE);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true)->where('status', self::STATUS_ACTIVE);
    }

    /** Ensure only concrete variants are returned (e.g. for manufacturing workflows) */
    public function scopeConfiguredVariants(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNotNull('parent_id')
              ->orWhereDoesntHave('variants');
        });
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%")
              ->orWhere('barcode', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    // ── Domain Helpers ───────────────────────────────────────────────────

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function isSellable(): bool
    {
        return in_array($this->type, self::SELLABLE_TYPES);
    }

    public function isRawMaterial(): bool
    {
        return $this->type === self::TYPE_RAW_MATERIAL;
    }

    public function isPackingMaterial(): bool
    {
        return $this->type === self::TYPE_PACKING_MATERIAL;
    }

    public function getDiscountAmountAttribute(): float
    {
        if ($this->discount_price > 0 && $this->discount_price < $this->selling_price) {
            return (float) ($this->selling_price - $this->discount_price);
        }
        return 0.0;
    }

    public function getDiscountPercentageAttribute(): float
    {
        if ($this->selling_price > 0 && $this->discount_price > 0 && $this->discount_price < $this->selling_price) {
            return (float) ((($this->selling_price - $this->discount_price) / $this->selling_price) * 100);
        }
        return 0.0;
    }
}
