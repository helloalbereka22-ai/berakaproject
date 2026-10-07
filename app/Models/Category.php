<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Traits\Translatable;

class Category extends Model
{
    use Translatable;
    use \App\Traits\BelongsToBusiness;

    // ── Category types ─────────────────────────────────────────────────────

    // ┌───────────────────────────────────────────────────────────────┐
    // │  MANUFACTURING domain categories                                │
    // └───────────────────────────────────────────────────────────────┘
    const TYPE_RAW_MATERIAL          = 'raw_material';     // e.g. Palm Oil, Sunflower Seed
    const TYPE_PACKING_MATERIAL      = 'packing_material'; // e.g. Carton, Plastic Bottle

    // ┌───────────────────────────────────────────────────────────────┐
    // │  SALEABLE domain categories  (sold to customers)                │
    // └───────────────────────────────────────────────────────────────┘
    const TYPE_BOOK                  = 'book';        // Computer Science, Programming, AI...
    const TYPE_STATIONERY            = 'stationery';  // Notebook, Pen, Bag, Office Supplies
    const TYPE_FASHION               = 'fashion';     // Jalabiya, Dress, Shirt, Trouser...
    const TYPE_OIL                   = 'oil';         // legacy alias → maps to finished_product
    const TYPE_MANUFACTURED_PRODUCT  = 'finished_product';
    const TYPE_GENERAL               = 'general';     // general / other

    const TYPES = [
        // ─ Manufacturing ────────────────────────────────────────────────────────
        self::TYPE_RAW_MATERIAL         => 'Manufacturing → Raw Material',
        self::TYPE_PACKING_MATERIAL     => 'Manufacturing → Packaging Material',
        self::TYPE_OIL                  => 'Manufacturing → Oil / Finished Product',
        self::TYPE_MANUFACTURED_PRODUCT => 'Manufacturing → Manufactured Product',
        // ─ Saleable ─────────────────────────────────────────────────────────────
        self::TYPE_BOOK                 => 'Saleable → Book',
        self::TYPE_STATIONERY           => 'Saleable → Stationery',
        self::TYPE_FASHION              => 'Saleable → Fashion',
        self::TYPE_GENERAL              => 'Saleable → General',
    ];

    // ── Domain groupings ─────────────────────────────────────────────────────
    const RAW_MATERIAL_CATEGORY_TYPES = [
        self::TYPE_RAW_MATERIAL,
    ];

    const PACKAGING_CATEGORY_TYPES = [
        self::TYPE_PACKING_MATERIAL,
    ];

    const SALEABLE_CATEGORY_TYPES = [
        self::TYPE_GENERAL,
        self::TYPE_OIL,
        self::TYPE_MANUFACTURED_PRODUCT,
        self::TYPE_BOOK,
        self::TYPE_STATIONERY,
        self::TYPE_FASHION,
    ];

    const UNITS = [
        'kg' => 'Kilogram (kg)',
        'g' => 'Gram (g)',
        'ton' => 'Ton',
        'liter' => 'Liter (L)',
        'ml' => 'Milliliter (ml)',
        'quantity' => 'Quantity / General',
        'bottle' => 'Bottle',
        'piece' => 'Piece',
        'carton' => 'Carton',
        'bag' => 'Bag',
        'cap' => 'Cap',
        'label' => 'Label',
        'roll' => 'Roll',
        'box' => 'Box',
        'set' => 'Set',
    ];

    protected $fillable = [
        'business_id',
        'parent_id',
        'type',
        'default_unit',
        'name',
        'slug',
        'description',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Auto-generate slug ───────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            if (empty($category->slug)) {
                $category->slug = static::generateUniqueSlug($category->name);
            }
        });

        static::updating(function (Category $category) {
            if ($category->isDirty('name') && $category->isDirty('slug') === false) {
                $category->slug = static::generateUniqueSlug($category->name, $category->id);
            }
        });
    }

    protected static function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $count = 1;

        while (
            static::withoutGlobalScopes()
                ->where('slug', $slug)
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = "{$original}-{$count}";
            $count++;
        }

        return $slug;
    }

    // ── Relationships ────────────────────────────────────────────────────

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function articles()
    {
        return $this->hasMany(\App\Models\Article::class);
    }

    public function attributes()
    {
        return $this->belongsToMany(\App\Models\ProductAttribute::class ?? Category::class, 'category_attribute', 'category_id', 'attribute_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /** Only categories belonging to the Raw Material domain */
    public function scopeForRawMaterials(Builder $query): Builder
    {
        return $query->whereIn('type', self::RAW_MATERIAL_CATEGORY_TYPES);
    }

    /** Only categories belonging to the Packaging Material domain */
    public function scopeForPackagingMaterials(Builder $query): Builder
    {
        return $query->whereIn('type', self::PACKAGING_CATEGORY_TYPES);
    }

    /** Only categories belonging to saleable product domains */
    public function scopeForSaleableProducts(Builder $query): Builder
    {
        return $query->whereIn('type', self::SALEABLE_CATEGORY_TYPES);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function isRawMaterialCategory(): bool
    {
        return in_array($this->type, self::RAW_MATERIAL_CATEGORY_TYPES);
    }

    public function isPackagingCategory(): bool
    {
        return in_array($this->type, self::PACKAGING_CATEGORY_TYPES);
    }

    public function isSaleableCategory(): bool
    {
        return in_array($this->type, self::SALEABLE_CATEGORY_TYPES);
    }

    public function getFullPath(): string
    {
        if ($this->parent) {
            return $this->parent->getFullPath() . ' › ' . $this->name;
        }
        return $this->name;
    }
}
