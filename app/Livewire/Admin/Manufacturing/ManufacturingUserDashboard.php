<?php

namespace App\Livewire\Admin\Manufacturing;

use App\Models\Branch;
use App\Models\BranchSale;
use App\Models\BatchMaterialConsumption;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class ManufacturingUserDashboard extends Component
{
    // Add Product Form
    public ?int $salesCategoryId = null;
    public ?int $salesProductId = null;
    public string $salesQuantity = '';
    
    // Refresh token to force computed properties to recalculate
    public int $refreshToken = 0;

    // Raw Material Deduction Form - Bottle, Seed, Packing Material
    public ?int $bottleId = null;
    public string $bottleQuantity = '';
    public ?int $seedId = null;
    public string $seedQuantity = '';
    public ?int $packingId = null;
    public string $packingQuantity = '';

    // Sell Form - Manufactured Products Sale
    public bool $showSellSidebar = false;
    public string $sellType       = 'product'; // 'product' | 'purchase'
    public ?int  $sellCategoryId  = null;
    public ?int  $sellProductId   = null;
    public string $sellQuantity   = '';
    public array $salesItems = []; // Items added to sell (current cart)
    public array $soldHistory = []; // Completed sales history (shown in table)
    public bool $sellMode = false; // Whether we're in sell mode

    #[Url(as: 'tab', except: '')]
    public string $tab = ''; // Synced with ?tab= URL param

    // Filters
    public string $filterBusinessId = '';
    public string $filterBranchId   = ''; // resolved from user's branch assignment

    public function mount(): void
    {
        // sellMode is derived from the ?tab=sell URL param (Livewire #[Url] populates $this->tab)
        $this->sellMode = ($this->tab === 'sell') || (request()->get('tab') === 'sell');
        if ($this->sellMode) {
            $this->tab = 'sell';
        }

        $user = auth()->user();
        
        // Determine the user's business and branch
        if (!$user->isSuperAdmin()) {
            $ids = $user->assignedBusinessIds();
            if (count($ids) === 1) {
                $this->filterBusinessId = (string) $ids[0];

                // Resolve the manufacturer's branch (use user's actually assigned branch, not first branch)
                $userBranchIds = $user->assignedBranchIds();
                if (!empty($userBranchIds)) {
                    $this->filterBranchId = (string) $userBranchIds[0];
                    // Also resolve filterBusinessId from this branch if not set
                    if (empty($this->filterBusinessId)) {
                        $branch = Branch::find($userBranchIds[0]);
                        if ($branch) {
                            $this->filterBusinessId = (string) $branch->business_id;
                        }
                    }
                }
            }
        } else {
            // Super admin: try to get the Oil Factory business and its first branch
            $oilFactoryBusiness = \App\Models\Business::withoutGlobalScopes()
                ->where('name', 'like', '%Oil%')
                ->where('name', 'like', '%Factory%')
                ->first();
            
            if ($oilFactoryBusiness) {
                $this->filterBusinessId = (string) $oilFactoryBusiness->id;
                $branch = Branch::where('business_id', $oilFactoryBusiness->id)->first();
                if ($branch) {
                    $this->filterBranchId = (string) $branch->id;
                }
            }
        }
    }

    // Allow switching to sell mode without a full page reload
    public function enterSellMode(): void
    {
        $this->sellMode = true;
        $this->tab      = 'sell';
    }

    public function exitSellMode(): void
    {
        $this->sellMode = false;
        $this->tab      = '';
        $this->salesItems = [];
        $this->resetSellForm();
    }

    // Helper to check if this is Albareka Oil Factory with Main Factory branch
    protected function isOilFactoryMainBranch(): bool
    {
        if (empty($this->filterBusinessId)) {
            return false;
        }
        $business = \App\Models\Business::find($this->filterBusinessId);
        return $business && stripos($business->name, 'Al-Baraka') !== false && stripos($business->name, 'Lubricating') !== false;
    }

    // ── Livewire cascading selection hooks ────────────────────────────────
    public function updatedSalesCategoryId(): void
    {
        $this->salesProductId = null;
        $this->bottleId = null;
        $this->seedId = null;

        // Pre-select matching seed if only one exists for this category
        $seeds = $this->seedProducts;
        if ($seeds->count() === 1) {
            $this->seedId = $seeds->first()->id;
        }
    }

    public function updatedSalesProductId(): void
    {
        $this->bottleId = null;
        $this->seedId = null;

        if ($this->salesProductId) {
            // Auto-select matching bottle size
            $bottles = $this->bottleProducts;
            if ($bottles->isNotEmpty()) {
                $this->bottleId = $bottles->first()->id;
            }

            // Auto-select matching seed
            $seeds = $this->seedProducts;
            if ($seeds->isNotEmpty()) {
                $this->seedId = $seeds->first()->id;
            }
        }
    }

    // Get bottle products (filtered by exact size match from selected product)
    #[Computed]
    public function bottleProducts(): \Illuminate\Database\Eloquent\Collection
    {
        $user = auth()->user();
        $userBranchIds = $user->assignedBranchIds();
        
        // Start with all potential bottle products
        $allBottles = Product::with('category')
            ->where('status', 'active')
            ->where(function($q) {
                $q->where('name', 'like', '%ml%')
                  ->orWhere('name', 'like', '%ML%')
                  ->orWhere('name', 'like', '%bottle%')
                  ->orWhere('name', 'like', '%Bottle%')
                  ->orWhereHas('category', function($catQ) {
                      $catQ->where('name', 'like', '%bottle%')
                           ->orWhere('name', 'like', '%Bottle%');
                  });
            })
            // Exclude oil product names (manufactured items, not bottles)
            ->where('name', 'not like', '%tukur%')
            ->where('name', 'not like', '%selit%')
            ->where('name', 'not like', '%behrzaf%')
            ->where('name', 'not like', '%beherzaf%')
            ->where('name', 'not like', '%humera%')
            ->where('name', 'not like', '%azmud%')
            ->where('name', 'not like', '%telba%')
            ->where('name', 'not like', '%avocado%')
            ->where('name', 'not like', '%abokado%')
            ->where('name', 'not like', '%avacado%');
        
        // Filter by branch for non-super-admin users
        if (!$user->isSuperAdmin() && !empty($userBranchIds)) {
            $allBottles = $allBottles->whereHas('purchases', function($q) use ($userBranchIds) {
                $q->whereIn('branch_id', $userBranchIds)
                  ->where('status', 'completed');
            }, '>=', 1);
        }
        
        $allBottles = $allBottles->orderBy('name')->get();

        // If a product is selected, filter by EXACT size match
        if ($this->salesProductId) {
            $selectedProduct = Product::find($this->salesProductId);
            if ($selectedProduct) {
                $productName = strtolower($selectedProduct->name);
                
                // Extract size with ml (like 100ml, 200ml, 300ml, etc.)
                if (preg_match('/(\d+)\s*ml/i', $productName, $matches)) {
                    $size = $matches[1]; // e.g. 100, 200, 300
                    
                    // Filter bottles to show ONLY those with exact size match
                    $filteredBottles = $allBottles->filter(function($bottle) use ($size) {
                        $bottleName = strtolower($bottle->name);
                        if (preg_match('/(\d+)\s*ml/i', $bottleName, $bMatches)) {
                            return $bMatches[1] === (string)$size;
                        }
                        return str_contains($bottleName, (string)$size);
                    });
                    
                    return $filteredBottles->map(function ($product) {
                        $user = auth()->user();
                        $filterBusinessId = $this->filterBusinessId;
                        $filterBranchId = $this->filterBranchId;
                        $isSuperAdmin = $user->isSuperAdmin();
                        
                        // Use ONLY StockMovement as the single source of truth
                        $movementStock = (float) StockMovement::where('item_type', 'product')
                            ->where('item_id', $product->id)
                            ->when(!empty($filterBusinessId), function($q) use ($filterBusinessId) {
                                return $q->where('business_id', $filterBusinessId);
                            })
                            ->when(!$isSuperAdmin && !empty($filterBranchId), function($q) use ($filterBranchId) {
                                return $q->where('branch_id', $filterBranchId);
                            })
                            ->sum(DB::raw("CASE WHEN direction = 'in' THEN quantity ELSE -quantity END"));
                        $product->available_stock = $movementStock;
                        return $product;
                    })->values();
                }
            }
        }

        // If no product selected or no size found, show all bottles
        return $allBottles->map(function ($product) {
            $user = auth()->user();
            $filterBusinessId = $this->filterBusinessId;
            $filterBranchId = $this->filterBranchId;
            $isSuperAdmin = $user->isSuperAdmin();
            
            // Use ONLY StockMovement as the single source of truth
            $movementStock = (float) StockMovement::where('item_type', 'product')
                ->where('item_id', $product->id)
                ->when(!empty($filterBusinessId), function($q) use ($filterBusinessId) {
                    return $q->where('business_id', $filterBusinessId);
                })
                ->when(!$isSuperAdmin && !empty($filterBranchId), function($q) use ($filterBranchId) {
                    return $q->where('branch_id', $filterBranchId);
                })
                ->sum(DB::raw("CASE WHEN direction = 'in' THEN quantity ELSE -quantity END"));
            $product->available_stock = $movementStock;
            return $product;
        })->values();
    }

    // Get seed products (raw materials excluding bottles and packing materials, filtered by selected category)
    #[Computed]
    public function seedProducts(): \Illuminate\Support\Collection
    {
        $user = auth()->user();
        $userBranchIds = $user->assignedBranchIds();
        
        $allSeeds = Product::with('category')
            ->whereHas('category', function($q) {
                $q->where('type', 'raw_material');
            })
            // Exclude bottles
            ->where('name', 'not like', '%ml%')
            ->where('name', 'not like', '%ML%')
            ->where('name', 'not like', '%bottle%')
            ->where('name', 'not like', '%Bottle%')
            // Exclude packing materials
            ->where('name', 'not like', '%meshagiya%')
            ->where('name', 'not like', '%mashagiya%')
            ->where('name', 'not like', '%meshegiya%')
            ->where('name', 'not like', '%masagiya%')
            ->whereDoesntHave('category', function($q) {
                $q->where('type', 'packing_material')
                  ->orWhere('name', 'like', '%packing%')
                  ->orWhere('name', 'like', '%bottle%');
            })
            ->where('status', 'active');
        
        // Filter by branch for non-super-admin users
        if (!$user->isSuperAdmin() && !empty($userBranchIds)) {
            $allSeeds = $allSeeds->whereHas('purchases', function($q) use ($userBranchIds) {
                $q->whereIn('branch_id', $userBranchIds)
                  ->where('status', 'completed');
            }, '>=', 1);
        }
        
        $allSeeds = $allSeeds->orderBy('name')->get();

        // Determine matching seed keyword from selected product or category
        $keyword = null;
        if ($this->salesProductId) {
            $selectedProduct = Product::find($this->salesProductId);
            $pName = strtolower($selectedProduct?->name ?? '');
            if (str_contains($pName, 'selit')) {
                $keyword = 'selit';
            } elseif (str_contains($pName, 'beherzaf') || str_contains($pName, 'behrzaf')) {
                $keyword = 'beherzaf';
            } elseif (str_contains($pName, 'tukur') || str_contains($pName, 'azmud')) {
                $keyword = 'tukur';
            } elseif (str_contains($pName, 'telba')) {
                $keyword = 'telba';
            } elseif (str_contains($pName, 'humera')) {
                $keyword = 'humera';
            } elseif (str_contains($pName, 'avocado') || str_contains($pName, 'abokado') || str_contains($pName, 'avacado')) {
                $keyword = 'avocado';
            }
        }

        if (!$keyword && $this->salesCategoryId) {
            $selectedCategory = Category::find($this->salesCategoryId);
            $cName = strtolower($selectedCategory?->name ?? '');
            if (str_contains($cName, 'selit')) {
                $keyword = 'selit';
            } elseif (str_contains($cName, 'beherzaf') || str_contains($cName, 'behrzaf')) {
                $keyword = 'beherzaf';
            } elseif (str_contains($cName, 'tukur') || str_contains($cName, 'azmud') || str_contains($cName, 'black')) {
                $keyword = 'tukur';
            } elseif (str_contains($cName, 'telba')) {
                $keyword = 'telba';
            } elseif (str_contains($cName, 'humera')) {
                $keyword = 'humera';
            } elseif (str_contains($cName, 'avocado') || str_contains($cName, 'abokado') || str_contains($cName, 'avacado')) {
                $keyword = 'avocado';
            }
        }

        // Only filter if we found a matching keyword
        if ($keyword) {
            $filteredSeeds = $allSeeds->filter(function($seed) use ($keyword) {
                $sName = strtolower($seed->name);
                if ($keyword === 'beherzaf') {
                    return str_contains($sName, 'beherzaf') || str_contains($sName, 'behrzaf');
                }
                if ($keyword === 'tukur') {
                    return str_contains($sName, 'tukur') || str_contains($sName, 'azmud');
                }
                if ($keyword === 'avocado') {
                    // Match avocado, abokado, avacado (common spelling variations)
                    return str_contains($sName, 'avocado') || str_contains($sName, 'abokado') || str_contains($sName, 'avacado');
                }
                return str_contains($sName, $keyword);
            });

            // Use filtered results (strict filtering when keyword is found)
            $allSeeds = $filteredSeeds;
        }
        // If no keyword found, return ALL seeds (not restricted)

        return $allSeeds->map(function ($product) {
            $user = auth()->user();
            $filterBusinessId = $this->filterBusinessId;
            $filterBranchId = $this->filterBranchId;
            $isSuperAdmin = $user->isSuperAdmin();
            
            // Use ONLY StockMovement as the single source of truth
            $movementStock = (float) StockMovement::where('item_type', 'product')
                ->where('item_id', $product->id)
                ->when(!empty($filterBusinessId), function($q) use ($filterBusinessId) {
                    return $q->where('business_id', $filterBusinessId);
                })
                ->when(!$isSuperAdmin && !empty($filterBranchId), function($q) use ($filterBranchId) {
                    return $q->where('branch_id', $filterBranchId);
                })
                ->sum(DB::raw("CASE WHEN direction = 'in' THEN quantity ELSE -quantity END"));
            
            // Deduct any manufactured/consumed quantities
            $consumedStock = (float) BatchMaterialConsumption::whereHas('batch', function($q) use ($filterBusinessId, $isSuperAdmin, $filterBranchId) {
                    $q->when(!empty($filterBusinessId), function($qb) use ($filterBusinessId) {
                        return $qb->where('business_id', $filterBusinessId);
                    })
                    ->when(!$isSuperAdmin && !empty($filterBranchId), function($qb) use ($filterBranchId) {
                        return $qb->where('branch_id', $filterBranchId);
                    });
                })
                ->where('raw_material_id', $product->id)
                ->sum('quantity_used');
            
            // Final available stock calculation
            $product->available_stock = max(0, $movementStock - $consumedStock);
            return $product;
        });
    }

    // Get packing material products (meshagiya items and packing materials)
    #[Computed]
    public function packingProducts(): \Illuminate\Database\Eloquent\Collection
    {
        $user = auth()->user();
        $userBranchIds = $user->assignedBranchIds();
        
        // Search for meshagiya items directly by name
        $meshagiyaProducts = Product::with('category')
            ->where('status', 'active')
            ->where(function($q) {
                $q->where('name', 'like', '%meshagiya%')
                  ->orWhere('name', 'like', '%mashagiya%')
                  ->orWhere('name', 'like', '%meshegiya%')
                  ->orWhere('name', 'like', '%masagiya%');
            });
        
        // Also get products with packing_material category type
        $categoryProducts = Product::with('category')
            ->where('status', 'active')
            ->whereHas('category', function($q) {
                $q->where('type', 'packing_material');
            });
        
        // Filter by branch for non-super-admin users
        if (!$user->isSuperAdmin() && !empty($userBranchIds)) {
            $meshagiyaProducts = $meshagiyaProducts->whereHas('purchases', function($q) use ($userBranchIds) {
                $q->whereIn('branch_id', $userBranchIds)
                  ->where('status', 'completed');
            }, '>=', 1);
            
            $categoryProducts = $categoryProducts->whereHas('purchases', function($q) use ($userBranchIds) {
                $q->whereIn('branch_id', $userBranchIds)
                  ->where('status', 'completed');
            }, '>=', 1);
        }
        
        $meshagiyaProducts = $meshagiyaProducts->get();
        $categoryProducts = $categoryProducts->get();
            
        // Combine both collections and remove duplicates
        $allPackingProducts = $meshagiyaProducts->merge($categoryProducts)->unique('id');
        
        return $allPackingProducts->map(function ($product) {
            $user = auth()->user();
            $filterBusinessId = $this->filterBusinessId;
            $filterBranchId = $this->filterBranchId;
            $isSuperAdmin = $user->isSuperAdmin();
            
            // Use ONLY StockMovement as the single source of truth
            $movementStock = (float) StockMovement::where('item_type', 'product')
                ->where('item_id', $product->id)
                ->when(!empty($filterBusinessId), function($q) use ($filterBusinessId) {
                    return $q->where('business_id', $filterBusinessId);
                })
                ->when(!$isSuperAdmin && !empty($filterBranchId), function($q) use ($filterBranchId) {
                    return $q->where('branch_id', $filterBranchId);
                })
                ->sum(DB::raw("CASE WHEN direction = 'in' THEN quantity ELSE -quantity END"));
            $product->available_stock = $movementStock;
            return $product;
        })->sortBy('name')->values();
    }

    // Manufactured Items from Database (products with manufacturing_quantity > 0)
    #[Computed]
    public function manufacturediItems(): \Illuminate\Support\Collection
    {
        $user = auth()->user();
        $userBranchIds = $user->assignedBranchIds();
        $isSuperAdmin = $user->isSuperAdmin();
        
        // Get ALL products that have manufacturing metadata
        $items = Product::with(['category'])
            ->get()
            ->filter(function ($product) use ($userBranchIds, $isSuperAdmin, $user) {
                $meta = $product->metadata ?? [];
                
                // Must have manufacturing_quantity > 0
                if (!isset($meta['manufacturing_quantity']) || $meta['manufacturing_quantity'] <= 0) {
                    return false;
                }
                
                // Super admin sees all
                if ($isSuperAdmin) {
                    return true;
                }
                
                // For non-super-admin users, check if this branch manufactured this product
                $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
                
                if (!empty($manufacturingByBranch)) {
                    // Check if user's branch has manufactured this product
                    foreach ($userBranchIds as $branchId) {
                        if (isset($manufacturingByBranch[$branchId]) && (float)$manufacturingByBranch[$branchId] > 0) {
                            return true;
                        }
                    }
                }
                
                return false;
            })
            ->sortByDesc(function ($product) {
                $meta = $product->metadata ?? [];
                return $meta['manufacturing_added_at'] ?? now()->timestamp;
            })
            ->values();

        return $items;
    }

    public function getTotalQuantityProperty(): float
    {
        $user = auth()->user();
        $userBranchIds = $user->assignedBranchIds();
        
        return $this->manufacturedItems->sum(function ($product) use ($userBranchIds, $user) {
            $meta = $product->metadata ?? [];
            
            // For super admin, use manufacturing_quantity
            if ($user->isSuperAdmin()) {
                return isset($meta['manufacturing_quantity']) ? (float) $meta['manufacturing_quantity'] : 0;
            }
            
            $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
            
            // NEW FORMAT: Sum only THIS BRANCH's manufactured quantity
            if (!empty($manufacturingByBranch)) {
                $totalForThisBranch = 0;
                foreach ($userBranchIds as $branchId) {
                    if (isset($manufacturingByBranch[$branchId])) {
                        $totalForThisBranch += (float)$manufacturingByBranch[$branchId];
                    }
                }
                return $totalForThisBranch;
            }
            
            // FALLBACK: Old products without manufacturing_by_branch structure
            // For backwards compatibility, just use manufacturing_quantity
            return isset($meta['manufacturing_quantity']) ? (float) $meta['manufacturing_quantity'] : 0;
        });
    }

    // Categories & Products
    #[Computed]
    public function salesCategories(): \Illuminate\Database\Eloquent\Collection
    {
        // For Manufacturer Products, show ALL categories that are products (exclude seed, bottle, packing materials)
        $query = Category::withoutGlobalScopes()
            ->where('is_active', true)
            // Exclude seed, bottle, packing materials - only show finished products
            ->whereNotIn('type', ['raw_material', 'packing_material', 'seed']);
        
        // Filter by business: use assigned business if available
        $businessId = null;
        
        if (!empty($this->filterBusinessId)) {
            $businessId = (int)$this->filterBusinessId;
            $query->where('business_id', $businessId);
            
            // If no categories found for this business, try Oil Factory as fallback
            $count = $query->count();
            if ($count === 0) {
                // Reset and try Oil Factory
                $query = Category::withoutGlobalScopes()
                    ->where('is_active', true)
                    ->whereNotIn('type', ['raw_material', 'packing_material', 'seed']);
                
                $oilFactoryBusiness = \App\Models\Business::withoutGlobalScopes()
                    ->whereIn('name', ['Oil - Factory', 'Albareka Oil - Factory', 'Oil Factory'])
                    ->first();
                
                if (!$oilFactoryBusiness) {
                    $oilFactoryBusiness = \App\Models\Business::withoutGlobalScopes()
                        ->where('name', 'like', '%Oil%')
                        ->where('name', 'like', '%Factory%')
                        ->first();
                }
                
                if ($oilFactoryBusiness) {
                    $query->where('business_id', $oilFactoryBusiness->id);
                }
            }
        } else {
            // Super admin or no business assigned: find Oil Factory
            $oilFactoryBusiness = \App\Models\Business::withoutGlobalScopes()
                ->whereIn('name', ['Oil - Factory', 'Albareka Oil - Factory', 'Oil Factory'])
                ->first();
            
            if (!$oilFactoryBusiness) {
                $oilFactoryBusiness = \App\Models\Business::withoutGlobalScopes()
                    ->where('name', 'like', '%Oil%')
                    ->where('name', 'like', '%Factory%')
                    ->first();
            }
            
            if ($oilFactoryBusiness) {
                $query->where('business_id', $oilFactoryBusiness->id);
            }
        }
        
        return $query->orderBy('name')->get();
    }

    #[Computed]
    public function salesProducts(): \Illuminate\Database\Eloquent\Collection
    {
        // For non-super-admins: if they're manufacturing from a category not in their business,
        // allow them to see products from that category (e.g., Bazar user using Oil Factory categories)
        // This enables cross-business manufacturing via fallback categories
        return Product::with('category')
            ->when($this->salesCategoryId, function($q) {
                return $q->where('category_id', $this->salesCategoryId);
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    // Raw Materials for Deduction
    #[Computed]
    public function rawMaterialCategories(): \Illuminate\Database\Eloquent\Collection
    {
        return Category::whereIn('type', [Category::TYPE_RAW_MATERIAL, Category::TYPE_PACKING_MATERIAL])
            ->when(!empty($this->filterBusinessId), function($q) {
                return $q->where('business_id', $this->filterBusinessId);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function getRawMaterialsForCategory(int $categoryId): \Illuminate\Database\Eloquent\Collection
    {
        return Product::with('category')
            ->when(!empty($this->filterBusinessId), function($q) {
                return $q->where('business_id', $this->filterBusinessId);
            })
            ->where('category_id', $categoryId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                $stock = Purchase::where('product_id', $product->id)
                    ->where('business_id', $this->filterBusinessId)
                    ->where('status', Purchase::STATUS_COMPLETED)
                    ->sum('quantity');
                
                $product->available_stock = $stock;
                return $product;
            })
            ->filter(function($p) {
                return $p->available_stock > 0;
            });
    }

    // Helper to deduct raw material quantity from stock
    private function deductRawMaterial(int $productId, float $quantity): void
    {
        // Record StockMovement deduction (outbound production consumption)
        // Always use the user's assigned business and branch, not the location's
        $branchId = !empty($this->filterBranchId) ? (int)$this->filterBranchId : null;
        $businessId = !empty($this->filterBusinessId) ? (int)$this->filterBusinessId : null;
        
        // Get location for this branch
        $location = null;
        if ($branchId) {
            $location = Location::where('branch_id', $branchId)->first();
        }
        
        // Fallback to first available location
        if (!$location) {
            $location = Location::first();
        }

        if ($location && $businessId && $branchId) {
            $rawProduct = Product::find($productId);
            $effectiveUnitCost = (float) ($rawProduct?->cost_price ?? 0);
            
            // Calculate balance after deduction using the SAME business_id and branch_id we're filtering by
            $currentStock = (float) StockMovement::where('item_type', 'product')
                ->where('item_id', $productId)
                ->where('business_id', $businessId)
                ->where('branch_id', $branchId)
                ->sum(DB::raw("CASE WHEN direction = 'in' THEN quantity ELSE -quantity END"));
            $newBalance = max(0, $currentStock - $quantity);

            StockMovement::create([
                'business_id'    => $businessId,
                'branch_id'      => $branchId,
                'location_id'    => $location->id,
                'item_type'      => 'product',
                'item_id'        => $productId,
                'movement_type'  => StockMovement::TYPE_PRODUCTION_CONSUMPTION,
                'direction'      => 'out',
                'quantity'       => $quantity,
                'unit_cost'      => $effectiveUnitCost,
                'total_cost'     => round($quantity * $effectiveUnitCost, 4),
                'balance_after'  => $newBalance,
                'reference_type' => 'production_deduction',
                'notes'          => 'Raw material deduction for manufactured product',
                'created_by'     => auth()->id(),
                'moved_at'       => now(),
            ]);
        }
    }

    // Actions
    public function addSalesItem(): void
    {
        $this->validate([
            'salesCategoryId' => 'required|integer|exists:categories,id',
            'salesProductId' => 'required|integer|exists:products,id',
            'salesQuantity' => 'required|numeric|min:0.001',
            'bottleId' => 'nullable|integer|exists:products,id',
            'bottleQuantity' => 'nullable|numeric|min:0',
            'seedId' => 'nullable|integer|exists:products,id',
            'seedQuantity' => 'nullable|numeric|min:0', // Seed qty is optional - if 0, we just don't deduct
            'packingId' => 'nullable|integer|exists:products,id',
            'packingQuantity' => 'nullable|numeric|min:0',
        ]);

        $product = Product::find($this->salesProductId);
        if (!$product) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Product not found.']);
            return;
        }

        $productQty = (float) $this->salesQuantity;

        // Bottle stock check — bottle qty defaults to productQty if not specified
        $bottleQty = !empty($this->bottleQuantity) && (float)$this->bottleQuantity > 0
            ? (float)$this->bottleQuantity
            : $productQty;

        if ($this->bottleId) {
            // Use ONLY StockMovement as single source of truth
            $bottleStock = (float) StockMovement::where('item_type', 'product')
                ->where('item_id', $this->bottleId)
                ->when($this->filterBusinessId, function($q) {
                    return $q->where('business_id', $this->filterBusinessId);
                })
                ->when($this->filterBranchId, function($q) {
                    return $q->where('branch_id', $this->filterBranchId);
                })
                ->sum(DB::raw("CASE WHEN direction = 'in' THEN quantity ELSE -quantity END"));
            
            if ($bottleQty > $bottleStock) {
                $bottleName = \App\Models\Product::find($this->bottleId)?->name ?? 'Bottle';
                $this->addError('bottleId', "⚠️ Insufficient stock for '{$bottleName}'. Available: {$bottleStock}, Needed: {$bottleQty}. Cannot proceed.");
                return;
            }
        }

        // Packing stock check — packing qty defaults to productQty if not specified
        $packingQty = !empty($this->packingQuantity) && (float)$this->packingQuantity > 0
            ? (float)$this->packingQuantity
            : $productQty;

        if ($this->packingId) {
            // Use ONLY StockMovement as single source of truth
            $packingStock = (float) StockMovement::where('item_type', 'product')
                ->where('item_id', $this->packingId)
                ->when($this->filterBusinessId, function($q) {
                    return $q->where('business_id', $this->filterBusinessId);
                })
                ->when($this->filterBranchId, function($q) {
                    return $q->where('branch_id', $this->filterBranchId);
                })
                ->sum(DB::raw("CASE WHEN direction = 'in' THEN quantity ELSE -quantity END"));
            
            if ($packingQty > $packingStock) {
                $packingName = \App\Models\Product::find($this->packingId)?->name ?? 'Packing Material';
                $this->addError('packingId', "⚠️ Insufficient stock for '{$packingName}'. Available: {$packingStock}, Needed: {$packingQty}. Cannot proceed.");
                return;
            }
        }

        // Seed stock check
        if ($this->seedId && !empty($this->seedQuantity) && (float)$this->seedQuantity > 0) {
            // Use ONLY StockMovement as single source of truth
            $seedStock = (float) StockMovement::where('item_type', 'product')
                ->where('item_id', $this->seedId)
                ->when($this->filterBusinessId, function($q) {
                    return $q->where('business_id', $this->filterBusinessId);
                })
                ->when($this->filterBranchId, function($q) {
                    return $q->where('branch_id', $this->filterBranchId);
                })
                ->sum(DB::raw("CASE WHEN direction = 'in' THEN quantity ELSE -quantity END"));
            
            if ((float)$this->seedQuantity > $seedStock) {
                $seedName = \App\Models\Product::find($this->seedId)?->name ?? 'Seed';
                $this->addError('seedId', "⚠️ Insufficient stock for '{$seedName}'. Available: {$seedStock}, Needed: {$this->seedQuantity}. Cannot proceed.");
                return;
            }
        }

        DB::transaction(function () use ($product, $productQty) {
            // Add manufacturing quantity to product
            $meta = $product->metadata ?? [];
            $currentQty = isset($meta['manufacturing_quantity']) ? (float) $meta['manufacturing_quantity'] : 0;
            $meta['manufacturing_quantity'] = $currentQty + $productQty;
            
            // Track which branches have manufactured this product
            // Store as an array of branch IDs with their quantities
            $user = auth()->user();
            $branchIds = $user->assignedBranchIds();
            
            if (!empty($branchIds)) {
                $currentBranchId = $branchIds[0];
                
                // Initialize or get existing branch manufacturing records
                if (!isset($meta['manufacturing_by_branch'])) {
                    $meta['manufacturing_by_branch'] = [];
                }
                
                // Get current quantity for this branch
                $branchQty = isset($meta['manufacturing_by_branch'][$currentBranchId]) 
                    ? (float)$meta['manufacturing_by_branch'][$currentBranchId] 
                    : 0;
                
                // Add the new quantity
                $meta['manufacturing_by_branch'][$currentBranchId] = $branchQty + $productQty;
            }
            
            // Track daily manufactured amount (accumulate if same day, reset if new day)
            $today = now()->toDateString();
            
            // Check if this product was already manufactured today
            if (isset($meta['manufacturing_added_at'])) {
                $lastAddedDate = \Carbon\Carbon::parse($meta['manufacturing_added_at'])->toDateString();
                if ($lastAddedDate === $today) {
                    // Same day - accumulate today's total
                    $currentDaily = (float)($meta['manufacturing_daily_qty'] ?? 0);
                    $meta['manufacturing_daily_qty'] = $currentDaily + $productQty;
                } else {
                    // Different day - reset for today
                    $meta['manufacturing_daily_qty'] = $productQty;
                }
            } else {
                // First time manufacturing this product
                $meta['manufacturing_daily_qty'] = $productQty;
            }
            
            // Always update the timestamp to today
            $meta['manufacturing_added_at'] = now()->toIso8601String();

            // Persist seed deduction info for display in the table
            if ($this->seedId && !empty($this->seedQuantity) && (float) $this->seedQuantity > 0) {
                $seed = Product::find($this->seedId);
                $prevSeedQty = (float) ($meta['seed_quantity_used'] ?? 0);
                $meta['seed_quantity_used'] = $prevSeedQty + (float) $this->seedQuantity;
                $meta['seed_name']          = $seed?->name ?? 'Seed';
                $meta['seed_unit']          = $seed?->unit ?? 'kg';
            }

            $product->metadata = $meta;
            $product->status = 'active';
            $product->is_marketplace_visible = true;
            
            // If the product doesn't belong to the user's business, update it
            // This handles the case where Bilal uses Oil Factory categories/products
            if (!empty($this->filterBusinessId) && $product->business_id !== (int)$this->filterBusinessId) {
                $product->business_id = (int)$this->filterBusinessId;
            }
            
            $product->save();

            // Deduct bottle (use product quantity as default if not specified)
            $bottleQty = !empty($this->bottleQuantity) ? (float) $this->bottleQuantity : $productQty;
            if ($this->bottleId && $bottleQty > 0) {
                $this->deductRawMaterial($this->bottleId, $bottleQty);
            }

            // Deduct seed
            if ($this->seedId && !empty($this->seedQuantity) && (float) $this->seedQuantity > 0) {
                $this->deductRawMaterial($this->seedId, (float) $this->seedQuantity);
            }

            // Deduct packing material (use product quantity as default if not specified)
            $packingQty = !empty($this->packingQuantity) ? (float) $this->packingQuantity : $productQty;
            if ($this->packingId && $packingQty > 0) {
                $this->deductRawMaterial($this->packingId, $packingQty);
            }
        });

        // Reset form - set to string values first to force Livewire re-evaluation
        $this->salesProductId = null;
        $this->salesQuantity = '';
        $this->bottleId = null;
        $this->bottleQuantity = '';
        $this->seedId = null;
        $this->seedQuantity = '';
        $this->packingId = null;
        $this->packingQuantity = '';
        $this->salesCategoryId = null;
        
        // Increment refresh token to force all computed properties to recalculate
        $this->refreshToken++;
        
        // Dispatch success and let Livewire re-render computed properties with fresh DB data
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Item added to the list. Raw materials deducted.']);
    }

    // Edit modal state
    public bool $showEditSalesItemModal = false;
    public ?int $editingProductId = null;
    public string $editQuantity = '';
    public string $editReason = '';

    public function openEditSalesItemModal(int $productId): void
    {
        $product = Product::find($productId);
        if (!$product) return;

        $meta = $product->metadata ?? [];
        $qty = isset($meta['manufacturing_quantity']) ? $meta['manufacturing_quantity'] : 0;

        $this->editingProductId = $productId;
        $this->editQuantity = (string) $qty;
        $this->editReason = '';
        $this->showEditSalesItemModal = true;
    }

    public function saveSalesItem(): void
    {
        $this->validate([
            'editQuantity' => 'required|numeric|min:0',
            'editReason' => 'required|string|min:3|max:500',
        ]);

        $product = Product::find($this->editingProductId);
        if (!$product) return;

        $meta = $product->metadata ?? [];
        
        // Track if this is a new manufacturing entry (no manufacturing_added_at yet)
        $isNewEntry = !isset($meta['manufacturing_added_at']);
        
        $meta['manufacturing_quantity'] = (float) $this->editQuantity;
        
        // Only set manufacturing_added_at for new entries
        if ($isNewEntry) {
            $meta['manufacturing_added_at'] = now()->toIso8601String();
            $meta['manufacturing_daily_qty'] = (float) $this->editQuantity;
        }
        // For existing entries: DON'T change manufacturing_added_at or manufacturing_daily_qty
        // (keep the original manufacture date and daily qty from when it was first added)

        $product->metadata = $meta;
        $product->status = 'active';
        $product->is_marketplace_visible = true;
        $product->save();

        $this->showEditSalesItemModal = false;
        $this->editingProductId = null;

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Item updated successfully.']);
    }

    public function closeEditSalesItemModal(): void
    {
        $this->showEditSalesItemModal = false;
        $this->editingProductId = null;
        $this->editReason = '';
    }

    // Delete Modal
    public bool $showDeleteConfirmModal = false;
    public ?int $deletingProductId = null;
    public string $deleteReason = '';

    public function openDeleteConfirmModal(int $productId): void
    {
        $product = Product::find($productId);
        if (!$product) return;

        $this->deletingProductId = $productId;
        $this->deleteReason = '';
        $this->showDeleteConfirmModal = true;
    }

    public function deleteSalesItem(): void
    {
        $this->validate([
            'deleteReason' => 'required|string|min:3|max:500',
        ]);

        $product = Product::find($this->deletingProductId);
        if ($product) {
            $meta = $product->metadata ?? [];
            $meta['manufacturing_quantity'] = 0;
            $meta['manufacturing_added_at'] = null;
            $product->metadata = $meta;
            $product->save();
        }

        $this->showDeleteConfirmModal = false;
        $this->deletingProductId = null;

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Item removed from the list.']);
    }

    public function closeDeleteConfirmModal(): void
    {
        $this->showDeleteConfirmModal = false;
        $this->deletingProductId = null;
        $this->deleteReason = '';
    }

    // ── Sell Functions ────────────────────────────────────────────────────

    // Open sell sidebar
    public function openSellSidebar(): void
    {
        $this->showSellSidebar = true;
        $this->resetSellForm();
    }

    // Close sell sidebar
    public function closeSellSidebar(): void
    {
        $this->showSellSidebar = false;
        $this->resetSellForm();
    }

    // Reset sell form
    private function resetSellForm(): void
    {
        $this->sellCategoryId = null;
        $this->sellProductId  = null;
        $this->sellQuantity   = '';
    }

    // Switch sell type and reset dependent fields
    public function setSellType(string $type): void
    {
        $this->sellType       = $type;
        $this->sellCategoryId = null;
        $this->sellProductId  = null;
        $this->sellQuantity   = '';
        unset($this->sellCategories); // bust Computed cache
        unset($this->sellProducts);
    }

    public function updatedSeedQuantity(): void
    {
        if (!$this->seedId || empty($this->seedQuantity)) return;

        $seedStock = \App\Models\Purchase::where('product_id', $this->seedId)
            ->where('status', 'completed')
            ->sum('quantity');

        if ((float)$this->seedQuantity > $seedStock) {
            $this->addError('seedQuantity', "⚠️ Cannot exceed available stock ({$seedStock} kg). Reduce the quantity.");
        } else {
            $this->resetErrorBag('seedQuantity');
        }
    }

    public function updatedSeedId(): void
    {
        $this->seedQuantity = '';
        $this->resetErrorBag('seedQuantity');
    }

    public function updatedSellCategoryId(): void
    {
        $this->sellProductId = null;
        $this->sellQuantity  = '';
        unset($this->sellProducts);
    }

    // Get categories for sell form — filtered by type
    #[Computed]
    public function sellCategories(): \Illuminate\Database\Eloquent\Collection
    {
        $query = Category::when(!empty($this->filterBusinessId), fn($q) =>
                    $q->where('business_id', $this->filterBusinessId))
                ->where('is_active', true)
                ->orderBy('name');

        if ($this->sellType === 'purchase') {
            // Only show categories that have actual stock transferred to this branch
            // by the general manager (purchase_type=product, status=completed, qty>0)
            // Category belongs to the source product's business — use withoutGlobalScopes
            $categoryIds = Purchase::where('purchases.purchase_type', Purchase::TYPE_PRODUCT)
                ->where('purchases.status', Purchase::STATUS_COMPLETED)
                ->where('purchases.quantity', '>', 0)
                ->when(!empty($this->filterBranchId),   fn($q) => $q->where('purchases.branch_id',   (int) $this->filterBranchId))
                ->when(!empty($this->filterBusinessId), fn($q) => $q->where('purchases.business_id', (int) $this->filterBusinessId))
                ->join('products', 'purchases.product_id', '=', 'products.id')
                ->whereNull('products.deleted_at')
                ->selectRaw('COALESCE(purchases.category_id, products.category_id) as cat_id')
                ->pluck('cat_id')
                ->filter()
                ->unique();

            // Use withoutGlobalScopes — category may belong to source business
            return Category::withoutGlobalScopes()
                ->whereIn('id', $categoryIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);
        }
        // Manufactured / finished products — exclude raw/packing categories
        $query->whereNotIn('type', [
            Category::TYPE_RAW_MATERIAL,
            Category::TYPE_PACKING_MATERIAL,
        ]);

        return $query->get(['id', 'name']);
    }

    // Get products for selected sell category — stock-aware, per type
    #[Computed]
    public function sellProducts(): \Illuminate\Support\Collection
    {
        if (!$this->sellCategoryId) {
            return collect();
        }

        $query = Product::when(!empty($this->filterBusinessId), fn($q) =>
                    $q->where('business_id', $this->filterBusinessId))
                ->where('category_id', $this->sellCategoryId)
                ->where('status', 'active')
                ->orderBy('name');

        // For purchase type, restrict to products with actual transferred stock
        // and use withoutGlobalScopes since products may belong to source business
        if ($this->sellType === 'purchase') {
            $productIds = Purchase::where('purchases.purchase_type', Purchase::TYPE_PRODUCT)
                ->where('purchases.status', Purchase::STATUS_COMPLETED)
                ->where('purchases.quantity', '>', 0)
                ->when(!empty($this->filterBranchId),   fn($q) => $q->where('purchases.branch_id',   (int) $this->filterBranchId))
                ->when(!empty($this->filterBusinessId), fn($q) => $q->where('purchases.business_id', (int) $this->filterBusinessId))
                ->join('products', 'purchases.product_id', '=', 'products.id')
                ->whereNull('products.deleted_at')
                ->where(function($q) {
                    $q->where('purchases.category_id', $this->sellCategoryId)
                      ->orWhere('products.category_id', $this->sellCategoryId);
                })
                ->pluck('purchases.product_id')
                ->filter()
                ->unique();

            return Product::withoutGlobalScopes()
                ->whereIn('id', $productIds)
                ->where('status', 'active')
                ->orderBy('name')
                ->get()
                ->map(function ($product) {
                    $product->available_qty = $this->resolveAvailableQty($product);
                    return $product;
                })
                ->filter(fn($p) => $p->available_qty > 0)
                ->values();
        }

        return $query->get()
            ->map(function ($product) {
                $product->available_qty = $this->resolveAvailableQty($product);
                return $product;
            })
            ->filter(fn($p) => $p->available_qty > 0)
            ->values();
    }

    // Resolve available qty depending on sell type
    private function resolveAvailableQty(Product $product): float
    {
        if ($this->sellType === 'purchase') {
            // Stock transferred by general manager via ProductTransfer
            return (float) Purchase::where('purchases.product_id', $product->id)
                ->where('purchases.purchase_type', Purchase::TYPE_PRODUCT)
                ->when(!empty($this->filterBranchId),   fn($q) => $q->where('purchases.branch_id',   (int) $this->filterBranchId))
                ->when(!empty($this->filterBusinessId), fn($q) => $q->where('purchases.business_id', (int) $this->filterBusinessId))
                ->where('purchases.status', Purchase::STATUS_COMPLETED)
                ->where('purchases.quantity', '>', 0)
                ->sum('purchases.quantity');
        }

        // --- product type ---
        // Use StockMovement only (net of in/out) for consistent calculation
        $query = StockMovement::where('item_id', $product->id)
            ->where('item_type', 'product');

        if (!empty($this->filterBusinessId)) {
            $query->where('business_id', (int) $this->filterBusinessId);
        }

        $stockIn  = (clone $query)->where('direction', 'in')
            ->whereNotIn('movement_type', StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND)
            ->sum('quantity');

        $stockOut = (clone $query)->where('direction', 'out')
            ->whereIn('movement_type', StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND)
            ->sum('quantity');

        return max(0, (float)$stockIn - (float)$stockOut);
    }

    // Get available quantity for selected product
    public function getSellAvailableQuantity(): float
    {
        if (!$this->sellProductId) {
            return 0;
        }

        $product = Product::find($this->sellProductId);
        if (!$product) {
            return 0;
        }

        return $this->resolveAvailableQty($product);
    }

    // Add item to sales list
    public function addSellItem(): void
    {
        $this->validate([
            'sellCategoryId' => 'required|integer|exists:categories,id',
            'sellProductId' => 'required|integer|exists:products,id',
            'sellQuantity' => 'required|numeric|min:0.001',
        ]);

        $product = Product::findOrFail($this->sellProductId);
        $availableQty = $this->getSellAvailableQuantity();
        $sellQty = (float) $this->sellQuantity;

        // Check if enough quantity available
        if ($sellQty > $availableQty) {
            $this->addError('sellQuantity', "Only {$availableQty} units available");
            return;
        }

        // Check if item already exists in sales list
        $existingIndex = null;
        foreach ($this->salesItems as $index => $item) {
            if ($item['product_id'] === $this->sellProductId) {
                $existingIndex = $index;
                break;
            }
        }

        if ($existingIndex !== null) {
            // Update existing item
            $this->salesItems[$existingIndex]['quantity'] += $sellQty;
            $this->salesItems[$existingIndex]['total_price'] = 
                $this->salesItems[$existingIndex]['quantity'] * $this->salesItems[$existingIndex]['unit_price'];
        } else {
            // Add new item
            $this->salesItems[] = [
                'product_id' => $this->sellProductId,
                'sell_type'  => $this->sellType,
                'name'       => $product->name,
                'quantity'   => $sellQty,
                'unit_price' => (float) $product->selling_price,
                'total_price'=> $sellQty * (float) $product->selling_price,
            ];
        }

        // Reset form
        $this->sellProductId = null;
        $this->sellQuantity = '';
        
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Item added to sales list']);
    }

    // Remove item from sales list
    public function removeSellItem(int $index): void
    {
        unset($this->salesItems[$index]);
        $this->salesItems = array_values($this->salesItems); // Reindex array
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Item removed from sales list']);
    }

    // Get total sales amount
    public function getSellTotal(): float
    {
        return array_sum(array_column($this->salesItems, 'total_price'));
    }

    // Complete sale
    public function completeSale(): void
    {
        if (empty($this->salesItems)) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'No items to sell']);
            return;
        }

        DB::transaction(function() {
            $branchId   = !empty($this->filterBranchId) ? (int) $this->filterBranchId : null;
            $locationId = $branchId
                ? Location::where('branch_id', $branchId)->value('id')
                : null;

            foreach ($this->salesItems as $item) {
                $product    = Product::findOrFail($item['product_id']);
                $sellQty    = (float) $item['quantity'];
                $unitPrice  = (float) $item['unit_price'];
                $costPrice  = (float) ($product->cost_price ?? 0);

                // ── 1. Deduct stock from the correct source ───────────────────
                if ($this->sellType === 'purchase') {
                    // Deduct from Purchase records (transferred stock from GM)
                    $remaining = $sellQty;
                    $purchases = Purchase::where('purchases.product_id', $product->id)
                        ->where('purchases.purchase_type', Purchase::TYPE_PRODUCT)
                        ->when(!empty($this->filterBranchId),   fn($q) => $q->where('purchases.branch_id',   (int) $this->filterBranchId))
                        ->when(!empty($this->filterBusinessId), fn($q) => $q->where('purchases.business_id', (int) $this->filterBusinessId))
                        ->where('purchases.status', Purchase::STATUS_COMPLETED)
                        ->where('purchases.quantity', '>', 0)
                        ->orderBy('purchases.purchase_date', 'asc')
                        ->get();

                    foreach ($purchases as $purchase) {
                        if ($remaining <= 0) break;
                        if ($purchase->quantity >= $remaining) {
                            $purchase->decrement('quantity', $remaining);
                            $remaining = 0;
                        } else {
                            $remaining -= $purchase->quantity;
                            $purchase->update(['quantity' => 0]);
                        }
                    }
                } else {
                    // Deduct from metadata manufacturing_quantity if present
                    $metadata = $product->metadata ?? [];
                    $mfrQty   = (float) ($metadata['manufacturing_quantity'] ?? 0);
                    if ($mfrQty > 0) {
                        $metadata['manufacturing_quantity'] = max(0, $mfrQty - $sellQty);
                        $product->metadata = $metadata;
                        $product->save();
                    }
                }

                // ── 2. Write a StockMovement (sale / out) ────────────────────────
                StockMovement::create([
                    'business_id'   => $this->filterBusinessId ?: null,
                    'branch_id'     => $branchId,
                    'location_id'   => $locationId,
                    'item_type'     => 'product',
                    'item_id'       => $product->id,
                    'movement_type' => StockMovement::TYPE_SALE,
                    'direction'     => 'out',
                    'quantity'      => $sellQty,
                    'unit_cost'     => $unitPrice,
                    'total_cost'    => round($sellQty * $unitPrice, 4),
                    'reference_type'=> 'branch_sale',
                    'notes'         => 'Manufacturer direct sale',
                    'created_by'    => auth()->id(),
                    'moved_at'      => now(),
                ]);

                // ── 3. Write a BranchSale record ─────────────────────────────────
                BranchSale::create([
                    'branch_id'   => $branchId,
                    'location_id' => $locationId,
                    'product_id'  => $product->id,
                    'quantity'    => $sellQty,
                    'unit_price'  => $unitPrice,
                    'cost_price'  => $costPrice,
                    'total_amount'=> round($sellQty * $unitPrice, 4),
                    'sale_date'   => now()->toDateString(),
                    'recorded_by' => auth()->id(),
                ]);
            }
        });

        // Record in session history
        $this->soldHistory[] = [
            'sold_at'    => now()->format('Y-m-d H:i'),
            'items'      => $this->salesItems,
            'total'      => array_sum(array_column($this->salesItems, 'total_price')),
            'item_count' => count($this->salesItems),
        ];

        // Reset cart
        $this->salesItems    = [];
        $this->sellCategoryId = null;
        $this->sellProductId  = null;
        $this->sellQuantity   = '';

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Sale completed successfully']);
    }

    public function render()
    {
        return view('livewire.admin.manufacturing.user-dashboard')
            ->title('Manufacturer Products');
    }
}