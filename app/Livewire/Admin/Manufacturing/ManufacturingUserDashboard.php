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
    public string $sellItemSearch = ''; // Search term for filtering items
    public array $salesItems = []; // Items added to sell (current cart)
    public array $soldHistory = []; // Completed sales history (shown in table)
    public bool $sellMode = false; // Whether we're in sell mode

    #[Url(as: 'tab', except: '')]
    public string $tab = ''; // Synced with ?tab= URL param

    // Filters
    public string $filterBusinessId = '';
    public string $filterBranchId   = ''; // resolved from user's branch assignment
    public string $filterFromDate   = '';
    public string $filterToDate     = '';

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
            // Get user's assigned branches (more reliable than businesses for manufacturers)
            $userBranchIds = $user->assignedBranchIds();
            if (!empty($userBranchIds)) {
                // Use user's first assigned branch
                $this->filterBranchId = (string) $userBranchIds[0];
                
                // Get the business_id from that branch
                $branch = Branch::find($userBranchIds[0]);
                if ($branch) {
                    $this->filterBusinessId = (string) $branch->business_id;
                }
            } else {
                // Fallback to first business if no branches assigned
                $ids = $user->assignedBusinessIds();
                if (!empty($ids)) {
                    $this->filterBusinessId = (string) $ids[0];
                    // Try to find a branch for this business
                    $branch = Branch::where('business_id', $ids[0])->first();
                    if ($branch) {
                        $this->filterBranchId = (string) $branch->id;
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
        
        // Initialize date filters to today
        $this->filterFromDate = now()->toDateString();
        $this->filterToDate = now()->toDateString();
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

    // ── Date filter lifecycle hooks ────────────────────────────────────────
    // Bust the #[Computed] cache when date filters change so items recompute
    public function updatedFilterFromDate(): void
    {
        unset($this->manufacturediItems);
    }

    public function updatedFilterToDate(): void
    {
        unset($this->manufacturediItems);
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
    public function bottleProducts(): \Illuminate\Support\Collection
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
                            ->when(!empty($filterBranchId), function($q) use ($filterBranchId) {
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
                ->when(!empty($filterBranchId), function($q) use ($filterBranchId) {
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
                ->when(!empty($filterBranchId), function($q) use ($filterBranchId) {
                    return $q->where('branch_id', $filterBranchId);
                })
                ->sum(DB::raw("CASE WHEN direction = 'in' THEN quantity ELSE -quantity END"));
            
            // Deduct any manufactured/consumed quantities
            $consumedStock = (float) BatchMaterialConsumption::whereHas('batch', function($q) use ($filterBusinessId, $isSuperAdmin, $filterBranchId) {
                    $q->when(!empty($filterBusinessId), function($qb) use ($filterBusinessId) {
                        return $qb->where('business_id', $filterBusinessId);
                    })
                    ->when(!empty($filterBranchId), function($qb) use ($filterBranchId) {
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
    public function packingProducts(): \Illuminate\Support\Collection
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
                ->when(!empty($filterBranchId), function($q) use ($filterBranchId) {
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
        
        // Parse date filters
        $fromDate = !empty($this->filterFromDate) ? \Carbon\Carbon::parse($this->filterFromDate)->startOfDay() : null;
        $toDate = !empty($this->filterToDate) ? \Carbon\Carbon::parse($this->filterToDate)->endOfDay() : null;
        
        // Get ALL products that have manufacturing metadata
        $allProducts = Product::with(['category'])->get();
        
        $filtered = collect();
        
        foreach ($allProducts as $product) {
            $meta = $product->metadata ?? [];
            
            // Must have manufacturing_quantity > 0
            if (!isset($meta['manufacturing_quantity']) || (float)$meta['manufacturing_quantity'] <= 0) {
                continue;
            }
            
            // Check date filter
            if ($fromDate || $toDate) {
                $manufacturingDate = isset($meta['manufacturing_added_at']) 
                    ? \Carbon\Carbon::parse($meta['manufacturing_added_at'])
                    : null;
                
                if (!$manufacturingDate) {
                    continue; // Skip if no date
                }
                
                // Filter by date range
                if ($fromDate && $manufacturingDate->lt($fromDate)) {
                    continue; // Before from date
                }
                if ($toDate && $manufacturingDate->gt($toDate)) {
                    continue; // After to date
                }
            }
            
            // For non-super-admin users: ONLY show items MANUFACTURED BY THEIR BRANCH
            // Super admin sees all manufactured items
            if (!$isSuperAdmin) {
                $manufacturingByBranchId = isset($meta['manufactured_by_branch_id']) ? (int)$meta['manufactured_by_branch_id'] : null;
                
                if (!$manufacturingByBranchId) {
                    // No manufacturing_by_branch_id data = skip entirely
                    continue;
                }
                
                // Check if THIS ITEM was manufactured by user's branch
                $shouldInclude = false;
                
                foreach ($userBranchIds as $branchId) {
                    $branchIdInt = (int)$branchId;
                    
                    // Item must be manufactured by THIS specific branch
                    if ($manufacturingByBranchId === $branchIdInt) {
                        $shouldInclude = true;
                        break;
                    }
                }
                
                // If item was NOT manufactured by user's branch, SKIP it
                if (!$shouldInclude) {
                    continue;
                }
            }
            
            $filtered->push($product);
        }
        
        // Sort by manufacturing_added_at descending
        $filtered = $filtered->sortByDesc(function ($product) {
            $meta = $product->metadata ?? [];
            return $meta['manufacturing_added_at'] ?? now()->timestamp;
        })->values();

        return $filtered;
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
        // Get user's business and branch context if not set in $this
        $branchId = null;
        $businessId = null;
        
        // Try to use $this->filterBranchId and $this->filterBusinessId first
        if (!empty($this->filterBranchId)) {
            $branchId = (int)$this->filterBranchId;
            $businessId = !empty($this->filterBusinessId) ? (int)$this->filterBusinessId : null;
        } else {
            // Fallback: get user's assigned branch directly
            $user = auth()->user();
            if ($user && !$user->isSuperAdmin()) {
                $userBranchIds = $user->assignedBranchIds();
                if (!empty($userBranchIds)) {
                    $branchId = (int)$userBranchIds[0];
                    $branch = Branch::find($branchId);
                    if ($branch) {
                        $businessId = (int)$branch->business_id;
                    }
                } else {
                    // Try assigned businesses
                    $userBusinessIds = $user->assignedBusinessIds();
                    if (!empty($userBusinessIds)) {
                        $businessId = (int)$userBusinessIds[0];
                        $branch = Branch::where('business_id', $businessId)->first();
                        if ($branch) {
                            $branchId = (int)$branch->id;
                        }
                    }
                }
            }
        }
        
        // Log for debugging
        \Log::debug('deductRawMaterial', [
            'filterBranchId' => $this->filterBranchId ?? 'null',
            'filterBusinessId' => $this->filterBusinessId ?? 'null',
            'resolvedBranchId' => $branchId,
            'resolvedBusinessId' => $businessId,
            'productId' => $productId,
            'quantity' => $quantity,
        ]);
        
        // Get location for this branch
        $location = null;
        if ($branchId) {
            $location = Location::where('branch_id', $branchId)->first();
        }
        
        // Fallback to first available location
        if (!$location) {
            $location = Location::first();
        }

        if (!$location) {
            \Log::warning('No location found for branch deduction', ['branchId' => $branchId]);
            return;
        }

        if (!$businessId || !$branchId) {
            \Log::warning('Missing business or branch context for deduction', [
                'businessId' => $businessId,
                'branchId' => $branchId,
            ]);
            return;
        }

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
    
    private function restoreRawMaterial(int $productId, float $quantity): void
    {
        // Restore raw material (reverse of deduction) - inbound production restoration
        // Get user's business and branch context if not set in $this
        $branchId = null;
        $businessId = null;
        
        // Try to use $this->filterBranchId and $this->filterBusinessId first
        if (!empty($this->filterBranchId)) {
            $branchId = (int)$this->filterBranchId;
            $businessId = !empty($this->filterBusinessId) ? (int)$this->filterBusinessId : null;
        } else {
            // Fallback: get user's assigned branch directly
            $user = auth()->user();
            if ($user && !$user->isSuperAdmin()) {
                $userBranchIds = $user->assignedBranchIds();
                if (!empty($userBranchIds)) {
                    $branchId = (int)$userBranchIds[0];
                    $branch = Branch::find($branchId);
                    if ($branch) {
                        $businessId = (int)$branch->business_id;
                    }
                } else {
                    // Try assigned businesses
                    $userBusinessIds = $user->assignedBusinessIds();
                    if (!empty($userBusinessIds)) {
                        $businessId = (int)$userBusinessIds[0];
                        $branch = Branch::where('business_id', $businessId)->first();
                        if ($branch) {
                            $branchId = (int)$branch->id;
                        }
                    }
                }
            }
        }
        
        // Log for debugging
        \Log::debug('restoreRawMaterial', [
            'filterBranchId' => $this->filterBranchId ?? 'null',
            'filterBusinessId' => $this->filterBusinessId ?? 'null',
            'resolvedBranchId' => $branchId,
            'resolvedBusinessId' => $businessId,
            'productId' => $productId,
            'quantity' => $quantity,
        ]);
        
        // Get location for this branch
        $location = null;
        if ($branchId) {
            $location = Location::where('branch_id', $branchId)->first();
        }
        
        // Fallback to first available location
        if (!$location) {
            $location = Location::first();
        }

        if (!$location) {
            \Log::warning('No location found for branch restoration', ['branchId' => $branchId]);
            return;
        }

        if (!$businessId || !$branchId) {
            \Log::warning('Missing business or branch context for restoration', [
                'businessId' => $businessId,
                'branchId' => $branchId,
            ]);
            return;
        }

        $rawProduct = Product::find($productId);
        $effectiveUnitCost = (float) ($rawProduct?->cost_price ?? 0);
        
        // Calculate balance after restoration
        $currentStock = (float) StockMovement::where('item_type', 'product')
            ->where('item_id', $productId)
            ->where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->sum(DB::raw("CASE WHEN direction = 'in' THEN quantity ELSE -quantity END"));
        $newBalance = $currentStock + $quantity;

        StockMovement::create([
            'business_id'    => $businessId,
            'branch_id'      => $branchId,
            'location_id'    => $location->id,
            'item_type'      => 'product',
            'item_id'        => $productId,
            'movement_type'  => StockMovement::TYPE_PRODUCTION_CONSUMPTION,
            'direction'      => 'in',
            'quantity'       => $quantity,
            'unit_cost'      => $effectiveUnitCost,
            'total_cost'     => round($quantity * $effectiveUnitCost, 4),
            'balance_after'  => $newBalance,
            'reference_type' => 'production_restoration',
            'notes'          => 'Raw material restoration from deleted manufactured product',
            'created_by'     => auth()->id(),
            'moved_at'       => now(),
        ]);
    }

    // Actions
    public function addSalesItem(): void
    {
        \Log::info('addSalesItem called', [
            'user_id' => auth()->id(),
            'salesCategoryId' => $this->salesCategoryId,
            'salesProductId' => $this->salesProductId,
            'salesQuantity' => $this->salesQuantity,
        ]);
        
        try {
            $this->validate([
                'salesCategoryId' => 'required|integer|exists:categories,id',
                'salesProductId' => 'required|integer|exists:products,id',
                'salesQuantity' => 'required|numeric|min:0.001',
                'bottleId' => 'nullable|integer|exists:products,id',
                'bottleQuantity' => 'nullable|numeric|min:0',
                'seedId' => 'required|integer|exists:products,id',
                'seedQuantity' => 'required|numeric|min:0.001',
                'packingId' => 'required|integer|exists:products,id',
                'packingQuantity' => 'nullable|numeric|min:0',
            ]);

            \Log::info('addSalesItem validation passed');

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
                ->when((int)$this->filterBranchId > 0, function($q) {
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
                ->when((int)$this->filterBranchId > 0, function($q) {
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
                ->when((int)$this->filterBranchId > 0, function($q) {
                    return $q->where('branch_id', $this->filterBranchId);
                })
                ->sum(DB::raw("CASE WHEN direction = 'in' THEN quantity ELSE -quantity END"));
            
            if ((float)$this->seedQuantity > $seedStock) {
                $seedName = \App\Models\Product::find($this->seedId)?->name ?? 'Seed';
                $this->addError('seedId', "⚠️ Insufficient stock for '{$seedName}'. Available: {$seedStock}, Needed: {$this->seedQuantity}. Cannot proceed.");
                return;
            }
        }

        \Log::info('addSalesItem all stock checks passed, starting transaction', [
            'productId' => $product->id,
            'productQty' => $productQty,
        ]);
        } catch (\Throwable $e) {
            \Log::error('addSalesItem validation or stock check failed: ' . $e->getMessage(), [
                'exception' => (string)$e,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
            return;
        }

        // ========== SAVE TO DATABASE (WITH TRANSACTION) ==========
        try {
            $bottleId = $this->bottleId;
            $seedId = $this->seedId;
            $packingId = $this->packingId;
            $seedQuantity = $this->seedQuantity;
            
            \DB::transaction(function () use ($product, $productQty, $bottleQty, $packingQty, $bottleId, $seedId, $packingId, $seedQuantity) {
                // Add manufacturing quantity to product
                $meta = $product->metadata ?? [];
                
                // Track which branches have manufactured this product
                // Store as an array of branch IDs with their quantities
                $user = auth()->user();
                $branchIds = $user->assignedBranchIds();
                
                if (!empty($branchIds)) {
                    // Get the user's FIRST assigned branch
                    $userBranchId = (int)$branchIds[0];
                    
                    // Verify this branch exists
                    $branch = \App\Models\Branch::find($userBranchId);
                    if (!$branch) {
                        throw new \Exception('Unable to determine your branch.');
                    }
                    
                    // Initialize or get existing branch manufacturing records
                    if (!isset($meta['manufacturing_by_branch'])) {
                        $meta['manufacturing_by_branch'] = [];
                    }
                    
                    // Ensure manufacturing_by_branch is an array
                    if (!is_array($meta['manufacturing_by_branch'])) {
                        $meta['manufacturing_by_branch'] = [];
                    }
                    
                    // Get current quantity for this specific branch (use int key)
                    $branchQty = isset($meta['manufacturing_by_branch'][$userBranchId]) 
                        ? (float)$meta['manufacturing_by_branch'][$userBranchId] 
                        : 0;
                    
                    // Add the new quantity ONLY to this branch
                    $meta['manufacturing_by_branch'][$userBranchId] = $branchQty + $productQty;
                    
                    // Recalculate manufacturing_quantity as the SUM of all branch values
                    $totalQty = 0;
                    foreach ($meta['manufacturing_by_branch'] as $qty_value) {
                        if (is_numeric($qty_value)) {
                            $totalQty += (float)$qty_value;
                        }
                    }
                    $meta['manufacturing_quantity'] = $totalQty;
                }
                
                // Track daily manufactured amount PER BRANCH (accumulate if same day, reset if new day)
                $today = now()->toDateString();
                
                // Initialize manufacturing_daily_qty_by_branch if needed
                if (!isset($meta['manufacturing_daily_qty_by_branch'])) {
                    $meta['manufacturing_daily_qty_by_branch'] = [];
                }
                if (!is_array($meta['manufacturing_daily_qty_by_branch'])) {
                    $meta['manufacturing_daily_qty_by_branch'] = [];
                }
                
                // Check the last manufacturing date for THIS BRANCH
                $lastManufactureDatePerBranch = $meta['manufacturing_date_per_branch'] ?? [];
                if (!is_array($lastManufactureDatePerBranch)) {
                    $lastManufactureDatePerBranch = [];
                }
                
                // Get user's branch ID (integer key)
                $userBranchId = (int)$branchIds[0];
                $lastDateForThisBranch = $lastManufactureDatePerBranch[$userBranchId] ?? null;
                
                // Check if THIS BRANCH manufactured today (not any branch)
                if ($lastDateForThisBranch === $today) {
                    // Same day - accumulate TODAY'S quantity for THIS BRANCH only
                    $currentBranchDaily = (float)($meta['manufacturing_daily_qty_by_branch'][$userBranchId] ?? 0);
                    $meta['manufacturing_daily_qty_by_branch'][$userBranchId] = $currentBranchDaily + $productQty;
                } else {
                    // This branch manufactured on a different day - reset THIS BRANCH only
                    // Keep other branches' daily data intact!
                    $meta['manufacturing_daily_qty_by_branch'][$userBranchId] = $productQty;
                    
                    // Update the date this branch last manufactured
                    $lastManufactureDatePerBranch[$userBranchId] = $today;
                    $meta['manufacturing_date_per_branch'] = $lastManufactureDatePerBranch;
                }
                
                // Also keep the old manufacturing_daily_qty for backward compatibility (global sum)
                $globalDaily = 0;
                foreach ($meta['manufacturing_daily_qty_by_branch'] as $qty) {
                    $globalDaily += (float)$qty;
                }
                $meta['manufacturing_daily_qty'] = $globalDaily;
                
                // Always update the timestamp to today
                $meta['manufacturing_added_at'] = now()->toIso8601String();

                // Store ONLY the CURRENT (latest) manufacturing quantity for display
                // This is what was just entered in "Product Qty *" form, NOT accumulated
                $meta['last_mfr_quantity'] = $productQty;

                // Accumulate the newly manufactured quantity if adding to same product today
                // (Check if this is same day by comparing with last date)
                if ($lastDateForThisBranch === $today) {
                    // Same day - accumulate new_mfr_quantity
                    $currentNewMfr = (float)($meta['new_mfr_quantity'] ?? 0);
                    $meta['new_mfr_quantity'] = $currentNewMfr + $productQty;
                } else {
                    // Different day - reset to just this addition
                    $meta['new_mfr_quantity'] = $productQty;
                }
                
                // Store the current seed deduction for display
                $currentSeedQty = 0;
                if ($seedId && !empty($seedQuantity) && (float) $seedQuantity > 0) {
                    $currentSeedQty = (float) $seedQuantity;
                }
                $meta['current_seed_deduction'] = $currentSeedQty;

                // Store who manufactured this item per branch
                $currentUser = auth()->user();
                $userBranchIds = $currentUser->assignedBranchIds();
                
                // Get the user's assigned branch (should be only one branch per user)
                $userBranch = $userBranchIds[0] ?? null;
                
                // Store manufacturer info per branch using the user's assigned branch only
                if (!isset($meta['manufactured_by_per_branch'])) {
                    $meta['manufactured_by_per_branch'] = [];
                }
                
                if ($userBranch) {
                    $meta['manufactured_by_per_branch'][$userBranch] = [
                        'username' => $currentUser->name,
                        'branch_id' => $userBranch,
                    ];
                }
                
                // Also keep the original for backwards compatibility
                $meta['manufactured_by_username'] = $currentUser->name;
                $meta['manufactured_by_branch_id'] = $userBranch;

                // Persist seed deduction info PER BRANCH for display in the table
                if ($seedId && !empty($seedQuantity) && (float) $seedQuantity > 0) {
                    $seed = Product::find($seedId);
                    
                    // Store seed deduction PER BRANCH (not accumulated across branches)
                    if (!isset($meta['seed_by_branch'])) {
                        $meta['seed_by_branch'] = [];
                    }
                    
                    $branchSeedQty = isset($meta['seed_by_branch'][$userBranchId]) 
                        ? (float)$meta['seed_by_branch'][$userBranchId]
                        : 0;
                    
                    $meta['seed_by_branch'][$userBranchId] = $branchSeedQty + (float)$seedQuantity;
                    
                    $meta['seed_name']          = $seed?->name ?? 'Seed';
                    $meta['seed_unit']          = $seed?->unit ?? 'kg';
                    
                    // Accumulate total seed used (for report "Total Seed Used" column)
                    $prevSeedQtyUsed = (float)($meta['seed_quantity_used'] ?? 0);
                    $meta['seed_quantity_used'] = $prevSeedQtyUsed + (float)$seedQuantity;
                }

                // Calculate and store total material used quantity
                $materialUsedQty = 0;
                $materialUsedUnit = '';
                
                // Add bottle quantity to material used
                if ($bottleId && $bottleQty > 0) {
                    $bottle = Product::find($bottleId);
                    $materialUsedQty += $bottleQty;
                    $materialUsedUnit = $bottle?->unit ?? 'units';
                }
                
                // Add packing quantity to material used
                if ($packingId && $packingQty > 0) {
                    $packing = Product::find($packingId);
                    $materialUsedQty += $packingQty;
                    if (empty($materialUsedUnit)) {
                        $materialUsedUnit = $packing?->unit ?? 'units';
                    }
                }
                
                // Add seed quantity to material used
                if ($seedId && !empty($seedQuantity) && (float) $seedQuantity > 0) {
                    $materialUsedQty += (float) $seedQuantity;
                }
                
                // Store accumulated material used quantity
                if ($materialUsedQty > 0) {
                    $prevMaterialUsed = (float) ($meta['material_used_quantity'] ?? 0);
                    $meta['material_used_quantity'] = $prevMaterialUsed + $materialUsedQty;
                    $meta['material_used_unit'] = $materialUsedUnit;
                }

                $product->metadata = $meta;
                $product->status = 'active';
                $product->is_marketplace_visible = true;
                
                // DO NOT change the product's business_id
                // Each branch tracks their manufacturing separately in metadata
                // The product's business_id should remain as it was originally set
                
                $product->save();
                
                \Log::info('addSalesItem product metadata saved');

                // Deduct bottle (use product quantity as default if not specified)
                $bottleQty = !empty($this->bottleQuantity) ? (float) $this->bottleQuantity : $productQty;
                if ($bottleId && $bottleQty > 0) {
                    $this->deductRawMaterial($bottleId, $bottleQty);
                }

                // Deduct seed
                if ($seedId && !empty($seedQuantity) && (float) $seedQuantity > 0) {
                    $this->deductRawMaterial($seedId, (float) $seedQuantity);
                }

                // Deduct packing material (use product quantity as default if not specified)
                $packingQty = !empty($this->packingQuantity) ? (float) $this->packingQuantity : $productQty;
                if ($packingId && $packingQty > 0) {
                    $this->deductRawMaterial($packingId, $packingQty);
                }

                \Log::info('addSalesItem raw materials deducted');
                
                // Record production output movement for the manufactured product
                $user = auth()->user();
                $userBranchIds = $user->assignedBranchIds();
                if (!empty($userBranchIds)) {
                    $userBranchId = (int)$userBranchIds[0];
                    $location = \App\Models\Location::where('branch_id', $userBranchId)->first();
                    
                    if ($location) {
                        app(\App\Services\InventoryService::class)->recordMovement(
                            $location->id, 'product', $product->id,
                            \App\Models\StockMovement::TYPE_PRODUCTION_OUTPUT,
                            $productQty, 0.0,
                            ['notes' => 'Manufactured product']
                        );
                        \Log::info('addSalesItem production_output movement recorded', [
                            'productId' => $product->id,
                            'quantity' => $productQty,
                            'locationId' => $location->id,
                        ]);
                    }
                }
            });
        } catch (\Throwable $e) {
            \Log::error('addSalesItem database operation failed: ' . $e->getMessage(), [
                'exception' => (string)$e,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
            return;
        }

        \Log::info('addSalesItem transaction completed successfully');

        // Reset form - set to string values first to force Livewire re-evaluation
        // This triggers Livewire 4's computed property recalculation automatically
        $this->salesProductId = null;
        $this->salesQuantity = '';
        $this->bottleId = null;
        $this->bottleQuantity = '';
        $this->seedId = null;
        $this->seedQuantity = '';
        $this->packingId = null;
        $this->packingQuantity = '';
        $this->salesCategoryId = null;
        
        // Dispatch success notification
        $this->dispatch('notify', ['type' => 'success', 'message' => '✅ Item added to list! Raw materials deducted.']);
    }

    // Edit modal state
    public bool $showEditSalesItemModal = false;
    public ?int $editingProductId = null;
    public string $editQuantity = '';
    public string $editReason = '';
    public string $editItemName = '';
    public string $editNewMfrQty = '';
    public string $editUsedQty = '';

    public function openEditSalesItemModal(int $productId): void
    {
        $product = Product::find($productId);
        if (!$product) return;

        $meta = $product->metadata ?? [];
        
        // Get the quantity for THIS user's branch
        $user = auth()->user();
        $userBranchIds = $user->assignedBranchIds();
        $branchQty = 0;
        
        if (!empty($userBranchIds)) {
            $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
            $userBranchId = (int)$userBranchIds[0];
            
            if (isset($manufacturingByBranch[$userBranchId])) {
                $branchQty = (float)$manufacturingByBranch[$userBranchId];
            } elseif (isset($manufacturingByBranch[(string)$userBranchId])) {
                $branchQty = (float)$manufacturingByBranch[(string)$userBranchId];
            }
        }

        $this->editingProductId = $productId;
        $this->editItemName = $product->name;
        $this->editNewMfrQty = (string) ($meta['new_mfr_quantity'] ?? 0);
        $this->editUsedQty = (string) ($meta['current_seed_deduction'] ?? 0);
        $this->editQuantity = (string) $branchQty;
        $this->editReason = '';
        $this->showEditSalesItemModal = true;
    }

    public function saveSalesItem(): void
    {
        $this->validate([
            'editItemName' => 'required|string|min:1|max:255',
            'editNewMfrQty' => 'required|numeric|min:0',
            'editUsedQty' => 'required|numeric|min:0',
            'editReason' => 'required|string|min:3|max:500',
        ]);

        $product = Product::find($this->editingProductId);
        if (!$product) return;

        $meta = $product->metadata ?? [];
        $user = auth()->user();
        $userBranchIds = $user->assignedBranchIds();
        
        // Get old values to calculate differences
        $oldNewMfrQty = (float) ($meta['new_mfr_quantity'] ?? 0);
        $oldUsedQty = (float) ($meta['current_seed_deduction'] ?? 0);
        $newNewMfrQty = (float) $this->editNewMfrQty;
        $newUsedQty = (float) $this->editUsedQty;
        
        // Calculate differences
        $newMfrDifference = $newNewMfrQty - $oldNewMfrQty;  // e.g., 10 - 12 = -2
        $usedQtyDifference = $newUsedQty - $oldUsedQty;      // e.g., 3 - 2 = +1
        
        // Update item name
        $product->name = $this->editItemName;
        
        // Update metadata with new values
        $meta['new_mfr_quantity'] = $newNewMfrQty;
        $meta['current_seed_deduction'] = $newUsedQty;
        
        // Accumulate total seed used for "Total Seed Used" column in reports
        if ($usedQtyDifference != 0) {
            $prevSeedQtyUsed = (float)($meta['seed_quantity_used'] ?? 0);
            $meta['seed_quantity_used'] = $prevSeedQtyUsed + $usedQtyDifference;
        }
        
        // Update manufacturing_by_branch for this branch by adding the difference
        if (!empty($userBranchIds)) {
            $userBranchId = (int)$userBranchIds[0];
            
            // Initialize branch structure if needed
            if (!isset($meta['manufacturing_by_branch'])) {
                $meta['manufacturing_by_branch'] = [];
            }
            
            if (!is_array($meta['manufacturing_by_branch'])) {
                $meta['manufacturing_by_branch'] = [];
            }
            
            // Get current branch quantity and add the difference
            $currentBranchQty = isset($meta['manufacturing_by_branch'][$userBranchId]) 
                ? (float)$meta['manufacturing_by_branch'][$userBranchId]
                : 0;
            
            // New branch quantity = old + difference (e.g., 24 + (-2) = 22)
            $meta['manufacturing_by_branch'][$userBranchId] = $currentBranchQty + $newMfrDifference;
            
            // Recalculate manufacturing_quantity as the SUM of all branch values
            $totalQty = 0;
            foreach ($meta['manufacturing_by_branch'] as $qty_value) {
                if (is_numeric($qty_value)) {
                    $totalQty += (float)$qty_value;
                }
            }
            $meta['manufacturing_quantity'] = $totalQty;
            
            // Update seed_by_branch by adding the difference
            if (!isset($meta['seed_by_branch'])) {
                $meta['seed_by_branch'] = [];
            }
            
            $currentBranchSeedQty = isset($meta['seed_by_branch'][$userBranchId]) 
                ? (float)$meta['seed_by_branch'][$userBranchId]
                : 0;
            
            // New branch seed quantity = old + difference (e.g., 6 + 1 = 7)
            $meta['seed_by_branch'][$userBranchId] = $currentBranchSeedQty + $usedQtyDifference;
        }
        
        $product->metadata = $meta;
        $product->save();
        
        // Update THIS BRANCH'S daily manufacturing data if quantity changed
        if ($newMfrDifference != 0 && !empty($userBranchIds)) {
            $userBranchId = (int)$userBranchIds[0];
            
            // Initialize daily data structure if needed
            if (!isset($meta['manufacturing_daily_qty_by_branch'])) {
                $meta['manufacturing_daily_qty_by_branch'] = [];
            }
            if (!is_array($meta['manufacturing_daily_qty_by_branch'])) {
                $meta['manufacturing_daily_qty_by_branch'] = [];
            }
            
            // Update THIS BRANCH'S daily total
            $currentDailyForBranch = (float)($meta['manufacturing_daily_qty_by_branch'][$userBranchId] ?? 0);
            $meta['manufacturing_daily_qty_by_branch'][$userBranchId] = $currentDailyForBranch + $newMfrDifference;
            
            // Recalculate global daily sum (across all branches)
            $globalDaily = 0;
            foreach ($meta['manufacturing_daily_qty_by_branch'] as $qty) {
                $globalDaily += (float)$qty;
            }
            $meta['manufacturing_daily_qty'] = $globalDaily;
            
            $product->metadata = $meta;
            $product->save();
        }
        
        // Log stock movements for audit trail if there are differences
        if ($newMfrDifference != 0 || $usedQtyDifference != 0) {
            // Get location for this branch
            $location = Location::where('branch_id', $userBranchIds[0] ?? null)->first();
            $locationId = $location?->id;
            
            if ($locationId) {
                if ($newMfrDifference != 0) {
                    DB::table('stock_movements')->insert([
                        'location_id' => $locationId,
                        'item_id' => $product->id,
                        'item_type' => 'product',
                        'movement_type' => 'manufactured_quantity_adjusted',
                        'direction' => $newMfrDifference > 0 ? 'in' : 'out',
                        'quantity' => abs($newMfrDifference),
                        'moved_at' => now(),
                        'notes' => "Edited manufactured quantity: {$oldNewMfrQty} → {$newNewMfrQty}. Reason: {$this->editReason}",
                        'created_by' => $user->id,
                        'business_id' => $product->business_id,
                        'branch_id' => $userBranchIds[0] ?? null,
                    ]);
                }
                
                if ($usedQtyDifference != 0) {
                    DB::table('stock_movements')->insert([
                        'location_id' => $locationId,
                        'item_id' => $product->id,
                        'item_type' => 'product',
                        'movement_type' => 'seed_used_adjusted',
                        'direction' => $usedQtyDifference > 0 ? 'out' : 'in',
                        'quantity' => abs($usedQtyDifference),
                        'moved_at' => now(),
                        'notes' => "Edited seed used: {$oldUsedQty} → {$newUsedQty}. Reason: {$this->editReason}",
                        'created_by' => $user->id,
                        'business_id' => $product->business_id,
                        'branch_id' => $userBranchIds[0] ?? null,
                    ]);
                }
            }
        }
        
        $this->closeEditSalesItemModal();
        $this->dispatch('notify', ['type' => 'success', 'message' => '✅ Item updated successfully!']);
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
        if (!$product) return;

        $meta = $product->metadata ?? [];
        $user = auth()->user();
        $userBranchIds = $user->assignedBranchIds();
        
        // Reverse the manufactured quantity and seed used for this branch
        if (!empty($userBranchIds)) {
            $userBranchId = (int)$userBranchIds[0];
            
            // Get the current values before deletion for logging
            $newMfrQty = (float) ($meta['new_mfr_quantity'] ?? 0);
            $usedQty = (float) ($meta['current_seed_deduction'] ?? 0);
            
            // Get the material quantities to restore
            $bottleQtyToRestore = 0;
            $seedQtyToRestore = 0;
            $packingQtyToRestore = 0;
            $bottleId = null;
            $seedId = null;
            $packingId = null;
            
            // Find the material IDs and quantities from the FinishedGood → ProductionBatch → BatchMaterialConsumption
            $latestFinished = \App\Models\FinishedGood::where('product_id', $product->id)
                ->with(['batch.rawMaterialConsumptions'])
                ->orderByDesc('id')
                ->first();
            
            if ($latestFinished && $latestFinished->batch) {
                foreach ($latestFinished->batch->rawMaterialConsumptions() as $consumption) {
                    $rawMat = $consumption->rawMaterial;
                    if ($rawMat) {
                        // Try to identify what type of material this is by name/category
                        $matName = strtolower($rawMat->name ?? '');
                        $qty = (float) $consumption->getEffectiveQuantity();
                        
                        // Check if it's a bottle (bottle, jar, container in name)
                        if (strpos($matName, 'bottle') !== false || strpos($matName, 'jar') !== false || strpos($matName, 'container') !== false) {
                            $bottleId = $rawMat->id;
                            $bottleQtyToRestore += $qty;
                        }
                        // Check if it's packing (packing, box, bag in name)
                        elseif (strpos($matName, 'packing') !== false || strpos($matName, 'box') !== false || strpos($matName, 'bag') !== false) {
                            $packingId = $rawMat->id;
                            $packingQtyToRestore += $qty;
                        }
                        // Otherwise assume it's seed
                        else {
                            $seedId = $rawMat->id;
                            $seedQtyToRestore += $qty;
                        }
                    }
                }
            }
            
            // Remove this from manufacturing_by_branch
            if (!isset($meta['manufacturing_by_branch'])) {
                $meta['manufacturing_by_branch'] = [];
            }
            
            if (!is_array($meta['manufacturing_by_branch'])) {
                $meta['manufacturing_by_branch'] = [];
            }
            
            // Get current branch quantity for logging
            $currentBranchQty = isset($meta['manufacturing_by_branch'][$userBranchId]) 
                ? (float)$meta['manufacturing_by_branch'][$userBranchId]
                : 0;
            
            // Set this branch's quantity to 0 (delete this branch's record)
            $meta['manufacturing_by_branch'][$userBranchId] = 0;
            
            // Recalculate manufacturing_quantity as the SUM of all remaining branch values
            $totalQty = 0;
            foreach ($meta['manufacturing_by_branch'] as $qty_value) {
                if (is_numeric($qty_value)) {
                    $totalQty += (float)$qty_value;
                }
            }
            $meta['manufacturing_quantity'] = $totalQty;
            
            // Reverse the seed_by_branch
            $currentBranchSeedQty = isset($meta['seed_by_branch'][$userBranchId]) 
                ? (float)$meta['seed_by_branch'][$userBranchId]
                : 0;
                
            if (isset($meta['seed_by_branch'][$userBranchId])) {
                $meta['seed_by_branch'][$userBranchId] = 0;
            }
            
            // Reset new_mfr_quantity and current_seed_deduction to 0 (these are shared)
            $meta['new_mfr_quantity'] = 0;
            $meta['current_seed_deduction'] = 0;
            
            // Reset THIS BRANCH'S daily data
            if (isset($meta['manufacturing_daily_qty_by_branch'][$userBranchId])) {
                $meta['manufacturing_daily_qty_by_branch'][$userBranchId] = 0;
            }
            
            // Recalculate global daily sum
            $globalDaily = 0;
            foreach ($meta['manufacturing_daily_qty_by_branch'] as $qty) {
                $globalDaily += (float)$qty;
            }
            $meta['manufacturing_daily_qty'] = $globalDaily;
            
            // Only null out manufacturing_added_at if NO branch has a non-zero qty remaining
            if ($totalQty <= 0) {
                $meta['manufacturing_added_at'] = null;
            }
            
            $product->metadata = $meta;
            $product->save();
            
            // Restore raw materials back to stock
            if ($bottleId && $bottleQtyToRestore > 0) {
                $this->restoreRawMaterial($bottleId, $bottleQtyToRestore);
            }
            
            if ($seedId && $seedQtyToRestore > 0) {
                $this->restoreRawMaterial($seedId, $seedQtyToRestore);
            }
            
            if ($packingId && $packingQtyToRestore > 0) {
                $this->restoreRawMaterial($packingId, $packingQtyToRestore);
            }
            
            // Log stock movements for deletion audit trail
            if ($currentBranchQty > 0) {
                // Get location for this branch
                $location = Location::where('branch_id', $userBranchId)->first();
                if ($location) {
                    DB::table('stock_movements')->insert([
                        'location_id' => $location->id,
                        'item_id' => $product->id,
                        'item_type' => 'product',
                        'movement_type' => 'manufactured_quantity_deleted',
                        'direction' => 'out',
                        'quantity' => abs($currentBranchQty),
                        'moved_at' => now(),
                        'notes' => "Deleted manufactured item: {$currentBranchQty} units removed. Reason: {$this->deleteReason}",
                        'created_by' => $user->id,
                        'business_id' => $product->business_id,
                        'branch_id' => $userBranchIds[0] ?? null,
                    ]);
                }
            }
            
            if ($currentBranchSeedQty > 0) {
                // Get location for this branch
                $location = Location::where('branch_id', $userBranchId)->first();
                if ($location) {
                    DB::table('stock_movements')->insert([
                        'location_id' => $location->id,
                        'item_id' => $product->id,
                        'item_type' => 'product',
                        'movement_type' => 'seed_used_deleted',
                        'direction' => 'in',
                        'quantity' => abs($currentBranchSeedQty),
                        'moved_at' => now(),
                        'notes' => "Deleted seed used: {$currentBranchSeedQty} units restored. Reason: {$this->deleteReason}",
                        'created_by' => $user->id,
                        'business_id' => $product->business_id,
                        'branch_id' => $userBranchIds[0] ?? null,
                    ]);
                }
            }
        } else {
            // Fallback for super admin or edge cases
            $meta['manufacturing_quantity'] = 0;
            $meta['new_mfr_quantity'] = 0;
            $meta['current_seed_deduction'] = 0;
            $meta['manufacturing_added_at'] = null;
            $product->metadata = $meta;
            $product->save();
        }

        $this->closeDeleteConfirmModal();
        $this->dispatch('notify', ['type' => 'success', 'message' => '✅ Item deleted successfully! Raw materials restored.']);
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
            // Show categories that have actual stock transferred to this branch
            // Get categories from BOTH:
            // 1. Transfer records (stock_movements with reference_type='StockTransfer')
            // 2. Old Purchase records (for backwards compatibility)
            
            $transferCategoryIds = \DB::table('stock_movements')
                ->where('movement_type', StockMovement::TYPE_TRANSFER_IN)
                ->where('reference_type', 'StockTransfer')
                ->where('direction', 'in')
                ->when(!empty($this->filterBranchId), function($q) {
                    $locationIds = Location::where('branch_id', (int)$this->filterBranchId)->pluck('id');
                    return $q->whereIn('location_id', $locationIds);
                })
                ->join('products', 'stock_movements.item_id', '=', 'products.id')
                ->whereNull('products.deleted_at')
                ->pluck('products.category_id')
                ->filter()
                ->unique();
            
            $purchaseCategoryIds = Purchase::where('purchases.purchase_type', Purchase::TYPE_PRODUCT)
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

            $categoryIds = $transferCategoryIds->merge($purchaseCategoryIds)->unique();

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
    // Get ALL items belonging to user's branch (no search filter) - for Item dropdown
    #[Computed]
    public function allSellProducts(): \Illuminate\Support\Collection
    {
        $user = auth()->user();
        $userBranchIds = $user->assignedBranchIds();

        // Get all active products
        $products = Product::with('category')
            ->where('status', 'active')
            ->get();

        return $products->filter(function ($product) use ($userBranchIds) {
            // Check if this product belongs to user's branch (any source: manufactured, transferred, or purchased)
            // Show all items belonging to the branch, no search filter
            $belongsToBranch = $this->productBelongsToBranch($product, $userBranchIds);
            
            return $belongsToBranch;

        })->map(function ($product) use ($userBranchIds) {
            $product->available_qty = $this->calculateBranchAvailableQty($product, $userBranchIds);
            return $product;
        })->values();
    }

    #[Computed]
    public function filteredSellProducts(): \Illuminate\Support\Collection
    {
        $user = auth()->user();
        $userBranchIds = $user->assignedBranchIds();
        $search = strtolower($this->sellItemSearch);

        // Get all active products
        $products = Product::with('category')
            ->where('status', 'active')
            ->get();

        return $products->filter(function ($product) use ($userBranchIds, $search) {
            // Apply search filter if entered
            if (!empty($search)) {
                $productName = strtolower($product->name);
                if (strpos($productName, $search) === false) {
                    return false;
                }
            }

            // Check if this product belongs to user's branch (any source: manufactured, transferred, or purchased)
            // Even if quantity is 0, show it if it belongs to the branch
            $belongsToBranch = $this->productBelongsToBranch($product, $userBranchIds);
            
            return $belongsToBranch;

        })->map(function ($product) use ($userBranchIds) {
            $product->available_qty = $this->calculateBranchAvailableQty($product, $userBranchIds);
            return $product;
        })->values();
    }

    // Check if product belongs to user's branch (has any stock or history in branch)
    private function productBelongsToBranch(Product $product, array $userBranchIds): bool
    {
        if (empty($userBranchIds)) {
            return false;
        }

        // Check manufactured items
        $meta = $product->metadata ?? [];
        $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
        foreach ($userBranchIds as $branchId) {
            if (isset($manufacturingByBranch[(int)$branchId])) {
                return true;
            }
        }

        // Check transferred items
        $locationIds = Location::whereIn('branch_id', $userBranchIds)->pluck('id')->toArray();
        if (!empty($locationIds)) {
            $transferredCount = \DB::table('stock_movements')
                ->where('item_type', 'product')
                ->where('item_id', $product->id)
                ->where('movement_type', StockMovement::TYPE_TRANSFER_IN)
                ->where('reference_type', 'StockTransfer')
                ->where('direction', 'in')
                ->whereIn('location_id', $locationIds)
                ->count();
            
            if ($transferredCount > 0) {
                return true;
            }
        }

        // Check purchased items
        $purchasedCount = Purchase::where('purchases.product_id', $product->id)
            ->where('purchases.purchase_type', Purchase::TYPE_PRODUCT)
            ->whereIn('purchases.branch_id', $userBranchIds)
            ->where('purchases.status', Purchase::STATUS_COMPLETED)
            ->count();
        
        if ($purchasedCount > 0) {
            return true;
        }

        return false;
    }

    // Calculate total available quantity (stock movements only, matching ProductTransfer::getSourceBalance exactly)
    private function calculateBranchAvailableQty(Product $product, array $userBranchIds): float
    {
        if (empty($userBranchIds)) {
            return 0;
        }

        $locations = \App\Models\Location::whereIn('branch_id', $userBranchIds)->pluck('id');
        if ($locations->isEmpty()) {
            return 0;
        }

        $bestBalance = 0.0;

        foreach ($locations as $locationId) {
            $stockIn = (float) \App\Models\StockMovement::where('location_id', $locationId)
                ->where('item_type', 'product')
                ->where('item_id', $product->id)
                ->where('direction', 'in')
                ->whereNotIn('movement_type', \App\Models\StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND)
                ->sum('quantity');

            $stockOut = (float) \App\Models\StockMovement::where('location_id', $locationId)
                ->where('item_type', 'product')
                ->where('item_id', $product->id)
                ->where('direction', 'out')
                ->whereIn('movement_type', \App\Models\StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND)
                ->sum('quantity');

            $balance = max(0, $stockIn - $stockOut);
            if ($balance > $bestBalance) {
                $bestBalance = $balance;
            }
        }

        // Legacy fallback: if no StockMovement records, check metadata
        if ($bestBalance == 0) {
            $prod = \App\Models\Product::withoutGlobalScopes()->find($product->id);
            if ($prod) {
                $metadata = $prod->metadata ?? [];
                $mfgQty = (float) ($metadata['manufacturing_quantity'] ?? 0);
                return max(0, $mfgQty);
            }
        }

        return max(0, $bestBalance);
    }

    // Get products for selected sell category — stock-aware, per type
    #[Computed]
    public function sellProducts(): \Illuminate\Support\Collection
    {
        if (!$this->sellCategoryId) {
            return collect();
        }

        $user = auth()->user();
        $userBranchIds = $user->assignedBranchIds();

        if ($this->sellType === 'purchase') {
            // For transferred products: query without business filter since transferred products
            // may come from different businesses. Category itself acts as the scope.
            $query = Product::withoutGlobalScopes()
                ->where('category_id', $this->sellCategoryId)
                ->where('status', 'active')
                ->orderBy('name');

            return $query->get()
                ->map(function ($product) use ($userBranchIds) {
                    // Get location IDs for user's assigned branches
                    $locationIds = Location::whereIn('branch_id', $userBranchIds)->pluck('id')->toArray();
                    
                    $totalQty = 0;
                    if (!empty($locationIds)) {
                        // Get transferred-in stock for this product
                        $transferredQty = \DB::table('stock_movements')
                            ->where('item_type', 'product')
                            ->where('item_id', $product->id)
                            ->where('movement_type', StockMovement::TYPE_TRANSFER_IN)
                            ->where('reference_type', 'StockTransfer')
                            ->where('direction', 'in')
                            ->whereIn('location_id', $locationIds)
                            ->sum('quantity');
                        
                        $totalQty = max(0, (float)$transferredQty);
                    }
                    
                    $product->available_qty = max(0, $totalQty);
                    return $product;
                })
                ->filter(fn($p) => $p->available_qty > 0)
                ->values();
        }
        
        // For manufactured products: apply business filter normally
        $query = Product::when(!empty($this->filterBusinessId), fn($q) =>
                    $q->where('business_id', $this->filterBusinessId))
                ->where('category_id', $this->sellCategoryId)
                ->where('status', 'active')
                ->orderBy('name');

        return $query->get()
            ->map(function ($product) use ($userBranchIds) {
                $meta = $product->metadata ?? [];
                $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
                
                $totalQty = 0;
                foreach ($userBranchIds as $branchId) {
                    $branchIdInt = (int)$branchId;
                    if (isset($manufacturingByBranch[$branchIdInt])) {
                        $totalQty += (float)$manufacturingByBranch[$branchIdInt];
                    }
                }
                
                $product->available_qty = max(0, $totalQty);
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
    /**
     * Get the manufactured quantity for the current user's branch(es) only.
     * This is what's available to sell - only what this branch manufactured.
     */
    public function getSellAvailableQuantity(): float
    {
        if (!$this->sellProductId) {
            return 0;
        }

        $product = Product::find($this->sellProductId);
        if (!$product) {
            return 0;
        }

        $user = auth()->user();
        $userBranchIds = $user->assignedBranchIds();

        if ($this->sellType === 'purchase') {
            // For transferred products: get stock from transfers
            // Get location IDs for user's assigned branches
            $locationIds = Location::whereIn('branch_id', $userBranchIds)->pluck('id')->toArray();
            
            if (empty($locationIds)) {
                return 0;
            }

            // Get transferred-in stock for this product
            $transferredQty = \DB::table('stock_movements')
                ->where('item_type', 'product')
                ->where('item_id', $this->sellProductId)
                ->where('movement_type', StockMovement::TYPE_TRANSFER_IN)
                ->where('reference_type', 'StockTransfer')
                ->where('direction', 'in')
                ->whereIn('location_id', $locationIds)
                ->sum('quantity');
            
            return max(0, (float)$transferredQty);
        }

        // For manufactured products: get from manufacturing_by_branch metadata
        $meta = $product->metadata ?? [];
        $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
        
        $totalQty = 0;
        foreach ($userBranchIds as $branchId) {
            $branchIdInt = (int)$branchId;
            if (isset($manufacturingByBranch[$branchIdInt])) {
                $totalQty += (float)$manufacturingByBranch[$branchIdInt];
            }
        }
        
        return max(0, $totalQty);
    }

    // Add item to sales list
    public function addSellItem(): void
    {
        $this->validate([
            'sellProductId' => 'required|integer|exists:products,id',
            'sellQuantity' => 'required|numeric|min:0.001',
        ]);

        $product = Product::findOrFail($this->sellProductId);
        $user = auth()->user();
        $userBranchIds = $user->assignedBranchIds();
        
        // Get available quantity using the correct calculation
        $availableQty = $this->calculateBranchAvailableQty($product, $userBranchIds);
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
                    // Deduct from metadata manufacturing_by_branch for THIS branch only
                    $metadata = $product->metadata ?? [];
                    
                    if (!empty($branchId) && !empty($metadata['manufacturing_by_branch'])) {
                        $manufacturingByBranch = $metadata['manufacturing_by_branch'];
                        
                        // Get current quantity for this branch (check both int and string keys for backwards compatibility)
                        $currentBranchQty = 0;
                        if (isset($manufacturingByBranch[(int)$branchId])) {
                            $currentBranchQty = (float)$manufacturingByBranch[(int)$branchId];
                        } elseif (isset($manufacturingByBranch[(string)$branchId])) {
                            $currentBranchQty = (float)$manufacturingByBranch[(string)$branchId];
                        }
                        
                        // Deduct only from this branch
                        if ($currentBranchQty > 0) {
                            $manufacturingByBranch[(int)$branchId] = max(0, $currentBranchQty - $sellQty);
                            
                            // Recalculate manufacturing_quantity as the sum of all branch values
                            $totalQty = 0;
                            foreach ($manufacturingByBranch as $qty_value) {
                                if (is_numeric($qty_value)) {
                                    $totalQty += (float)$qty_value;
                                }
                            }
                            $metadata['manufacturing_quantity'] = $totalQty;
                            $metadata['manufacturing_by_branch'] = $manufacturingByBranch;
                            
                            $product->metadata = $metadata;
                            $product->save();
                        }
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

    public function getDailyManufactured(): \Illuminate\Support\Collection
    {
        $user = auth()->user();
        $today = now()->toDateString();

        // Get user's assigned branch IDs (not super admin)
        $userBranchIds = $user->isSuperAdmin()
            ? []
            : $user->assignedBranchIds();

        // Query all products with manufacturing metadata
        $products = Product::with('category')
            ->where(function ($q) {
                // Look for products with manufacturing metadata
                $q->whereNotNull('metadata')
                  ->where('metadata', '!=', '{}');
            })
            ->get();

        $filtered = $products->filter(function ($product) use ($userBranchIds, $today) {
            $meta = $product->metadata ?? [];
            
            // Must have new_mfr_quantity to be considered manufactured today
            if (!isset($meta['new_mfr_quantity']) || (float)$meta['new_mfr_quantity'] <= 0) {
                return false;
            }

            // Must have manufacturing_added_at set
            if (!isset($meta['manufacturing_added_at'])) {
                return false;
            }

            // Check if manufacturing_added_at is today
            try {
                $addedDate = \Carbon\Carbon::parse($meta['manufacturing_added_at'])->toDateString();
                if ($addedDate !== $today) {
                    return false;
                }
            } catch (\Exception $e) {
                return false;
            }

            // For non-super-admin: ONLY show items manufactured BY THIS USER'S BRANCH TODAY
            if (!empty($userBranchIds)) {
                // Check manufacturing_daily_qty_by_branch for TODAY's quantities per branch
                $manufacturingDailyByBranch = $meta['manufacturing_daily_qty_by_branch'] ?? [];
                if (empty($manufacturingDailyByBranch)) {
                    return false;
                }

                // Check if ANY of user's branches have manufactured this product TODAY
                $hasUserBranchDailyData = false;
                foreach ($userBranchIds as $branchId) {
                    $dailyQty = (float)($manufacturingDailyByBranch[$branchId] ?? $manufacturingDailyByBranch[(string)$branchId] ?? 0);
                    if ($dailyQty > 0) {
                        $hasUserBranchDailyData = true;
                        break;
                    }
                }
                
                if (!$hasUserBranchDailyData) {
                    return false;
                }
            }

            // Super admin sees all products manufactured today
            return true;
        })->map(function ($product) use ($userBranchIds) {
            $meta = $product->metadata ?? [];
            
            // Use last_mfr_quantity (the ACTUAL daily entry, not accumulated)
            // This is the value shown in "New Mfr" column
            $qty = (float)($meta['last_mfr_quantity'] ?? 0);
            
            $seedQty = (float) ($meta['seed_quantity_used'] ?? 0);

            return (object) [
                'item_name'     => $product->name,
                'category'      => optional($product->category)->name,
                'quantity'      => $qty,
                'seed_qty_used' => $seedQty,
                'selling_price' => (float) ($product->selling_price ?? 0),
                'date_added'    => $meta['manufacturing_added_at'] ?? now()->toIso8601String(),
            ];
        })->values();

        return $filtered;
    }

    public function __get($name)
    {
        if ($name === 'dailyManufactured') {
            return $this->getDailyManufactured();
        }
        return parent::__get($name);
    }

    /**
     * Get the manufactured-by information from product metadata.
     * Returns formatted string of branch names and their quantities.
     */
    public function getManufacturedByInfo($product): string
    {
        $meta = $product->metadata ?? [];
        $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
        
        if (empty($manufacturingByBranch)) {
            return '—';
        }
        
        $branches = \App\Models\Branch::whereIn('id', array_keys($manufacturingByBranch))->get();
        $branchMap = $branches->keyBy('id');
        
        $items = [];
        foreach ($manufacturingByBranch as $branchId => $qty) {
            if ((float)$qty > 0) {
                $branchId = (int)$branchId;
                $branch = $branchMap->get($branchId);
                $branchName = $branch ? $branch->name : "Branch #{$branchId}";
                $qtyFormatted = rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.');
                $items[] = "{$branchName} ({$qtyFormatted})";
            }
        }
        
        return !empty($items) ? implode(', ', $items) : '—';
    }

    /**
     * Get the manufactured quantity for the current user's branch(es).
     * Uses stock_movements to calculate ACTUAL current available stock,
     * not stale metadata values.
     */
    public function getManufacturedQtyForUser($product): string
    {
        $user = auth()->user();
        $userBranchIds = $user->assignedBranchIds();
        
        // Calculate actual available stock from stock_movements
        // This matches the calculation used in the Super Admin report
        $availableData = \DB::table('stock_movements')
            ->where('item_id', $product->id)
            ->whereIn('branch_id', $userBranchIds)
            ->select(\DB::raw('SUM(CASE WHEN direction = "in" THEN quantity ELSE -quantity END) as available_qty'))
            ->first();
        
        $mfgQty = $availableData && $availableData->available_qty ? max(0, (float)$availableData->available_qty) : 0;
        
        return rtrim(rtrim(number_format($mfgQty, 3, '.', ''), '0'), '.');
    }

    public function render()
    {
        return view('livewire.admin.manufacturing.user-dashboard')
            ->title('Manufacturer Products');
    }
}