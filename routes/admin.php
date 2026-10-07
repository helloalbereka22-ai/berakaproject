<?php

use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────────────────────────
// All routes here are protected by 'admin' middleware
// (EnsureIsAdmin checks that the user has the Admin or Super Admin role).
// Individual routes further enforce granular permissions.
// ─────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // ─── Dashboard ──────────────────────────────────────────────
    Route::get('/dashboard',      \App\Livewire\Admin\Dashboard\MainDashboard::class)->name('dashboard');
    Route::get('/overview',       \App\Livewire\Admin\SuperAdminDashboard::class)->name('overview')->middleware('permission:report.view');
    Route::get('/branch-seller',  \App\Livewire\Admin\Dashboard\BranchSellerDashboard::class)->name('branch-seller.dashboard');
    Route::get('/seller/sales',   \App\Livewire\Admin\Seller\SellerSales::class)->name('seller.sales');
    Route::get('/low-stock',      \App\Livewire\Admin\Dashboard\LowStock::class)->name('low-stock');
    Route::get('/profile',        \App\Livewire\Admin\Profile\UserProfile::class)->name('profile');
    Route::get('/page-settings',  \App\Livewire\Admin\System\GmPageSettings::class)->name('page-settings');
    Route::get('/news',           \App\Livewire\Admin\Cms\GmNewsManager::class)->name('news');
    Route::get('/messages',       \App\Livewire\Admin\Cms\GmMessagesInbox::class)->name('messages');
    Route::get('/zakat',          \App\Livewire\Admin\Zakat\ZakatManager::class)->name('zakat');

    // ─── Business Management ─────────────────────────────────────
    Route::prefix('businesses')->name('businesses.')->middleware('permission:business.view')->group(function () {
        Route::get('/', \App\Livewire\Admin\Businesses\BusinessIndex::class)->name('index');
        Route::get('/create', \App\Livewire\Admin\Businesses\BusinessCreate::class)->name('create')->middleware('permission:business.create');
        Route::get('/{id}/edit', \App\Livewire\Admin\Businesses\BusinessEdit::class)->name('edit')->middleware('permission:business.update');
    });

    // ─── Branch Management ────────────────────────────────────────
    Route::prefix('branches')->name('branches.')->middleware('permission:branch.view')->group(function () {
        Route::get('/', \App\Livewire\Admin\Branches\BranchIndex::class)->name('index');
        Route::get('/create', \App\Livewire\Admin\Branches\BranchCreate::class)->name('create')->middleware('permission:branch.create');
        Route::get('/{id}/edit', \App\Livewire\Admin\Branches\BranchEdit::class)->name('edit')->middleware('permission:branch.update');
    });

    // ─── Super Admin: Category & Item Managers ────────────────────
    Route::get('/category-manager', \App\Livewire\Admin\Categories\CategoryManager::class)
        ->name('category-manager')
        ->middleware('permission:category.create');

    Route::get('/item-manager', \App\Livewire\Admin\Items\ItemManager::class)
        ->name('item-manager')
        ->middleware('permission:product.create');

    // ─── Factories (reuses BranchIndex - filtered by type on that component) ─
    Route::prefix('factories')->name('factories.')->middleware('permission:branch.view')->group(function () {
        Route::get('/', \App\Livewire\Admin\Branches\BranchIndex::class)->name('index');
    });

    // ─── Warehouses ──────────────────────────────────────────────
    Route::prefix('warehouses')->name('warehouses.')->middleware('permission:inventory.view')->group(function () {
        Route::get('/', \App\Livewire\Admin\Inventory\WarehouseIndex::class)->name('index');
    });

    // ─── Product Catalog ───────────────────────────────────────────
    Route::prefix('products')->name('products.')->middleware('permission:product.view')->group(function () {
        Route::get('/', \App\Livewire\Admin\Products\ProductIndex::class)->name('index');
        Route::get('/sale', \App\Livewire\Admin\Products\ProductIndex::class)->name('sale');
        Route::get('/create', \App\Livewire\Admin\Products\ProductCreate::class)->name('create')->middleware('permission:product.create');
        Route::get('/{id}/edit', \App\Livewire\Admin\Products\ProductEdit::class)->name('edit')->middleware('permission:product.update');
    });

    // Product Units & Pricing (stub - reuse ProductIndex with filter context)
    Route::get('/product-units', \App\Livewire\Admin\Products\ProductIndex::class)
        ->name('product-units.index')
        ->middleware('permission:product.view');
    Route::get('/product-pricing', \App\Livewire\Admin\Products\ProductIndex::class)
        ->name('product-pricing.index')
        ->middleware('permission:product.view');

    // ─── Categories ───────────────────────────────────────────────
    Route::prefix('categories')->name('categories.')->middleware('permission:category.view')->group(function () {
        Route::get('/', \App\Livewire\Admin\Categories\CategoryIndex::class)->name('index');
    });

    // ─── Saleable Products (Books, Stationery, Fashion) ─────────────
    Route::prefix('saleable-products')->name('saleable-products.')->middleware('permission:product.view')->group(function () {
        Route::get('/', \App\Livewire\Admin\SaleableProducts\SaleableProductIndex::class)->name('index');
        Route::get('/create', \App\Livewire\Admin\SaleableProducts\SaleableProductCreate::class)
            ->name('create')->middleware('permission:product.create');
        Route::get('/{id}/edit', \App\Livewire\Admin\SaleableProducts\SaleableProductEdit::class)
            ->name('edit')->middleware('permission:product.update');
    });

    // ─── Books (legacy alias — keep for backward compat) ─────────────
    Route::prefix('books')->name('books.')->middleware('permission:product.view')->group(function () {
        Route::get('/create', \App\Livewire\Admin\SaleableProducts\SaleableProductCreate::class)->name('create')->middleware('permission:product.create');
        Route::get('/{id}/edit', \App\Livewire\Admin\SaleableProducts\SaleableProductEdit::class)->name('edit')->middleware('permission:product.update');
    });

    // ─── Raw Materials ────────────────────────────────────────────
    Route::prefix('materials')->name('materials.')->middleware('permission:product.view')->group(function () {
        Route::get('/', \App\Livewire\Admin\Materials\MaterialIndex::class)->name('index');
        Route::get('/create', \App\Livewire\Admin\Materials\MaterialCreate::class)->name('create');
        Route::get('/raw', \App\Livewire\Admin\Materials\MaterialIndex::class)->name('raw');
        Route::get('/packaging', \App\Livewire\Admin\Materials\MaterialIndex::class)->name('packaging');
    });

    Route::get('/material-categories', \App\Livewire\Admin\Categories\CategoryIndex::class)
        ->name('material-categories.index')
        ->middleware('permission:category.view');
    Route::get('/material-units', \App\Livewire\Admin\Products\ProductIndex::class)
        ->name('material-units.index')
        ->middleware('permission:product.view');

    // ─── Purchases ───────────────────────────────────────────────
    Route::prefix('purchases')->name('purchases.')->middleware('permission:inventory.view')->group(function () {
        Route::get('/', \App\Livewire\Admin\Purchases\PurchaseList::class)->name('index');
        Route::get('/create', \App\Livewire\Admin\Purchases\PurchaseForm::class)->name('create');
        Route::get('/summary', \App\Livewire\Admin\Purchases\PurchaseSummary::class)->name('summary');
        Route::get('/transfer', \App\Livewire\Admin\Purchases\ProductTransfer::class)->name('transfer');
    });

    // ─── Inventory ───────────────────────────────────────────────
    Route::prefix('inventory')->name('inventory.')->middleware('permission:inventory.view')->group(function () {
        Route::get('/', \App\Livewire\Admin\Inventory\InventoryIndex::class)->name('index');
        Route::get('/overview', \App\Livewire\Admin\Inventory\InventoryIndex::class)->name('overview');
        Route::get('/category', \App\Livewire\Admin\Inventory\InventoryIndex::class)->name('category');
        Route::get('/movements', \App\Livewire\Admin\Inventory\InventoryIndex::class)->name('movements');
        Route::get('/transfers', \App\Livewire\Admin\Inventory\InventoryIndex::class)->name('transfers');
        Route::get('/adjustments', \App\Livewire\Admin\Inventory\InventoryIndex::class)->name('adjustments');
    });

    // ─── Manufacturing ────────────────────────────────────────────
    Route::prefix('manufacturing')->name('manufacturing.')->middleware('permission:manufacturing.view')->group(function () {
        Route::get('/dashboard',      \App\Livewire\Admin\Manufacturing\ManufacturingDashboard::class)->name('dashboard');
        // Manufacturing user: finished goods + raw material deduction
        Route::get('/products',     \App\Livewire\Admin\Manufacturing\ManufacturingUserDashboard::class)->name('products');
        // Transferred products (for manufacturing staff to see products transferred to their branch)
        Route::get('/transferred-products', \App\Livewire\Admin\Manufacturing\TransferredProducts::class)->name('transferred-products');
        // Order creation
        Route::get('/orders/create',  \App\Livewire\Admin\Manufacturing\ManufacturingOrderCreate::class)
            ->name('orders.create')->middleware('permission:manufacturing.manage');
        // Batch processing (full workflow UI)
        Route::get('/batch-process',           \App\Livewire\Admin\Manufacturing\BatchProcess::class)->name('batch-process');
        Route::get('/batch-process/{batchId}', \App\Livewire\Admin\Manufacturing\BatchProcess::class)->name('batch-process.show');
        // Batch traceability / history
        Route::get('/production',   \App\Livewire\Admin\Manufacturing\BatchTraceability::class)->name('production');
        Route::get('/batches',      \App\Livewire\Admin\Manufacturing\BatchTraceability::class)->name('batches');
        Route::get('/consumption',  \App\Livewire\Admin\Manufacturing\BatchTraceability::class)->name('consumption');
        Route::get('/finished',     \App\Livewire\Admin\Manufacturing\BatchTraceability::class)->name('finished');
        Route::get('/traceability', \App\Livewire\Admin\Manufacturing\BatchTraceability::class)->name('traceability');
    });

    // ─── Sales ───────────────────────────────────────────────────
    Route::prefix('sales')->name('sales.')->middleware('permission:order.view')->group(function () {
        Route::get('/overview', \App\Livewire\Admin\Orders\OrderIndex::class)->name('overview');
        Route::get('/orders', \App\Livewire\Admin\Orders\OrderIndex::class)->name('orders');
        Route::get('/reports', \App\Livewire\Admin\Reports\ReportDashboard::class)->name('reports');
    });

    Route::prefix('orders')->name('orders.')->middleware('permission:order.view')->group(function () {
        Route::get('/', \App\Livewire\Admin\Orders\OrderIndex::class)->name('index');
        Route::get('/create', \App\Livewire\Admin\Orders\OrderCreate::class)->name('create');
        Route::get('/{id}', \App\Livewire\Admin\Orders\OrderShow::class)->name('show');

        // Stock check for branch (AJAX)
        Route::get('/guest/stock-check', function (\Illuminate\Http\Request $request) {
            $branchId = (int)$request->branch_id;
            $orderId  = (int)$request->order_id;

            $order = \Illuminate\Support\Facades\DB::table('guest_orders')->where('id', $orderId)->first();
            if (!$order) return response()->json([]);

            $items = json_decode($order->items, true) ?? [];
            $result = [];

            foreach ($items as $item) {
                $name    = $item['name'] ?? '';
                $ordered = (float)($item['quantity'] ?? 1);

                $product = \App\Models\Product::withoutGlobalScopes()->where('name', $name)->first();
                if (!$product) {
                    $result[] = [
                        'name'        => $name,
                        'ordered'     => $ordered,
                        'available'   => 0,
                        'insufficient'=> true,
                        'in_branch'   => false,
                    ];
                    continue;
                }

                // Get available qty STRICTLY for this branch only
                $available = 0;

                if (in_array($product->type, ['finished_product', 'bottle_product'])) {
                    // Check branch-specific purchases ONLY
                    $branchPurQty = (float)\Illuminate\Support\Facades\DB::table('purchases')
                        ->where('product_id', $product->id)
                        ->where('branch_id', $branchId)
                        ->where('status', 'completed')
                        ->where('quantity', '>', 0)
                        ->sum('quantity');

                    if ($branchPurQty > 0) {
                        // Branch has its own purchase record
                        $available = $branchPurQty;
                    } else {
                        // Check StockMovement balance for manufactured items at this branch
                        $locationIds = \Illuminate\Support\Facades\DB::table('locations')
                            ->where('branch_id', $branchId)
                            ->pluck('id');

                        if ($locationIds->isNotEmpty()) {
                            $stockIn = (float) \App\Models\StockMovement::whereIn('location_id', $locationIds)
                                ->where('item_type', 'product')
                                ->where('item_id', $product->id)
                                ->where('direction', 'in')
                                ->whereNotIn('movement_type', \App\Models\StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND)
                                ->sum('quantity');

                            $stockOut = (float) \App\Models\StockMovement::whereIn('location_id', $locationIds)
                                ->where('item_type', 'product')
                                ->where('item_id', $product->id)
                                ->where('direction', 'out')
                                ->whereIn('movement_type', \App\Models\StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND)
                                ->sum('quantity');

                            $available = max(0, (float)$stockIn - (float)$stockOut);
                        }
                    }

                    $inBranch = $available > 0;

                } else {
                    // Purchased items: ONLY from this specific branch
                    $available = (float)\Illuminate\Support\Facades\DB::table('purchases')
                        ->where('product_id', $product->id)
                        ->where('branch_id', $branchId)
                        ->where('status', 'completed')
                        ->where('quantity', '>', 0)
                        ->sum('quantity');

                    $inBranch = $available > 0;
                }

                $result[] = [
                    'name'        => $name,
                    'ordered'     => $ordered,
                    'available'   => $available,
                    'insufficient'=> $available < $ordered,
                    'in_branch'   => $inBranch,
                ];
            }

            return response()->json($result);
        });

        // Confirm with branch selection (supports partial confirmation)
        Route::post('/guest/{id}/confirm-branch', function ($id, \Illuminate\Http\Request $request) {
            try {
                $order = \Illuminate\Support\Facades\DB::table('guest_orders')->where('id', $id)->first();
                if (!$order) return back()->with('error', 'Order not found.');

                $branchId = (int)$request->branch_id;
                if (!$branchId) return back()->with('error', 'Please select a branch.');

                $isPartial  = $request->input('partial') === '1';
                $items      = json_decode($order->items, true) ?? [];
                $deducted   = 0;
                $confirmed  = []; // items that were fulfilled
                $pending    = []; // items that couldn't be fulfilled
                $warnings   = [];

                foreach ($items as $item) {
                    $name = $item['name'] ?? '';
                    $qty  = (float)($item['quantity'] ?? 1);

                    $product = \App\Models\Product::withoutGlobalScopes()->where('name', $name)->first();
                    if (!$product) {
                        $pending[] = $item;
                        $warnings[] = "'{$name}' not found.";
                        continue;
                    }

                    // Check available stock
                    $available = 0;
                    if (in_array($product->type, ['finished_product', 'bottle_product'])) {
                        // Check branch-specific purchases first
                        $branchPurQty = (float)\Illuminate\Support\Facades\DB::table('purchases')
                            ->where('product_id', $product->id)
                            ->where('branch_id', $branchId)
                            ->where('status', 'completed')
                            ->where('quantity', '>', 0)
                            ->sum('quantity');

                        if ($branchPurQty > 0) {
                            $available = $branchPurQty;
                        } else {
                            // Check StockMovement balance for manufactured items
                            $locationIds = \Illuminate\Support\Facades\DB::table('locations')
                                ->where('branch_id', $branchId)
                                ->pluck('id');

                            if ($locationIds->isNotEmpty()) {
                                $stockIn = (float) \App\Models\StockMovement::whereIn('location_id', $locationIds)
                                    ->where('item_type', 'product')
                                    ->where('item_id', $product->id)
                                    ->where('direction', 'in')
                                    ->whereNotIn('movement_type', \App\Models\StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND)
                                    ->sum('quantity');

                                $stockOut = (float) \App\Models\StockMovement::whereIn('location_id', $locationIds)
                                    ->where('item_type', 'product')
                                    ->where('item_id', $product->id)
                                    ->where('direction', 'out')
                                    ->whereIn('movement_type', \App\Models\StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND)
                                    ->sum('quantity');

                                $available = max(0, (float)$stockIn - (float)$stockOut);
                            }
                        }
                    } else {
                        $available = (float)\Illuminate\Support\Facades\DB::table('purchases')
                            ->where('product_id', $product->id)
                            ->where('branch_id', $branchId)
                            ->where('status', 'completed')
                            ->where('quantity', '>', 0)
                            ->sum('quantity');
                        if ($available == 0) {
                            $available = (float)\Illuminate\Support\Facades\DB::table('purchases')
                                ->where('product_id', $product->id)
                                ->where('status', 'completed')
                                ->where('quantity', '>', 0)
                                ->sum('quantity');
                        }
                    }

                    $insufficient = $available < $qty;

                    // If partial mode and item is insufficient → keep it pending
                    if ($isPartial && $insufficient) {
                        $pending[] = $item;
                        $warnings[] = "{$name}: only {$available} available (ordered {$qty}) — kept pending.";
                        continue;
                    }

                    // Deduct stock
                    if (in_array($product->type, ['finished_product', 'bottle_product'])) {
                        // Check if this is the oil factory branch
                        $branch  = \Illuminate\Support\Facades\DB::table('branches')->where('id', $branchId)->first();
                        $bizName = strtolower(\Illuminate\Support\Facades\DB::table('businesses')->where('id', $branch?->business_id)->value('name') ?? '');
                        $isOilFactory = str_contains($bizName, 'oil') || str_contains($bizName, 'factory') || str_contains($bizName, 'lubricat');

                        // Try branch-specific purchase record first
                        $purchase = \Illuminate\Support\Facades\DB::table('purchases')
                            ->where('product_id', $product->id)
                            ->where('branch_id', $branchId)
                            ->where('status', 'completed')
                            ->where('quantity', '>', 0)
                            ->orderByDesc('quantity')
                            ->first();

                        if ($purchase) {
                            // Deduct from branch purchase record
                            $deductQty = min($qty, (float)$purchase->quantity);
                            $newQty    = (float)$purchase->quantity - $deductQty;
                            \Illuminate\Support\Facades\DB::table('purchases')
                                ->where('id', $purchase->id)
                                ->update([
                                    'quantity'    => $newQty,
                                    'total_price' => $newQty * (float)$purchase->unit_price,
                                    'updated_at'  => now(),
                                ]);
                        } elseif ($isOilFactory) {
                            // Oil factory: deduct from metadata
                            $meta      = $product->metadata ?? [];
                            $mfgQty    = (float)($meta['manufacturing_quantity'] ?? 0);
                            $deductQty = min($qty, $mfgQty);
                            $meta['manufacturing_quantity'] = max(0, $mfgQty - $deductQty);
                            $product->metadata = $meta;
                            $product->save();
                        } else {
                            $warnings[] = "{$name}: not available at this branch.";
                            continue;
                        }
                        if (($deductQty ?? 0) < $qty) $warnings[] = "{$name}: only " . ($deductQty ?? 0) . " deducted.";
                        $deducted++;
                    } else {
                        $purchase = \Illuminate\Support\Facades\DB::table('purchases')
                            ->where('product_id', $product->id)
                            ->where('branch_id', $branchId)
                            ->where('status', 'completed')
                            ->where('quantity', '>', 0)
                            ->orderByDesc('quantity')
                            ->first();
                        if (!$purchase) {
                            $purchase = \Illuminate\Support\Facades\DB::table('purchases')
                                ->where('product_id', $product->id)
                                ->where('status', 'completed')
                                ->where('quantity', '>', 0)
                                ->orderByDesc('quantity')
                                ->first();
                        }
                        if ($purchase) {
                            $deductQty = min($qty, (float)$purchase->quantity);
                            $newQty    = (float)$purchase->quantity - $deductQty;
                            \Illuminate\Support\Facades\DB::table('purchases')
                                ->where('id', $purchase->id)
                                ->update([
                                    'quantity'    => $newQty,
                                    'total_price' => $newQty * (float)$purchase->unit_price,
                                    'updated_at'  => now(),
                                ]);
                            $deducted++;
                        } else {
                            if ($isPartial) {
                                $pending[] = $item;
                            }
                            $warnings[] = "No stock for '{$name}'.";
                        }
                    }
                    $confirmed[] = $item;
                }

                $branch = \App\Models\Branch::find($branchId);

                if ($isPartial && !empty($pending)) {
                    // Split order: mark original as confirmed with only fulfilled items
                    // Create a NEW pending order for the unfulfilled items
                    $pendingTotal = array_sum(array_map(fn($i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 1), $pending));
                    $newOrderNumber = 'ORD-' . strtoupper(substr(md5(uniqid()), 0, 8));

                    \Illuminate\Support\Facades\DB::table('guest_orders')->insert([
                        'order_number'   => $newOrderNumber,
                        'customer_name'  => $order->customer_name,
                        'customer_phone' => $order->customer_phone,
                        'customer_email' => $order->customer_email,
                        'address'        => $order->address,
                        'notes'          => ($order->notes ? $order->notes . ' | ' : '') . 'Partial split from ' . $order->order_number,
                        'items'          => json_encode($pending),
                        'total_amount'   => $pendingTotal,
                        'status'         => 'pending',
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);

                    // Update original order with only confirmed items
                    $confirmedTotal = array_sum(array_map(fn($i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 1), $confirmed));
                    \Illuminate\Support\Facades\DB::table('guest_orders')
                        ->where('id', $id)
                        ->update([
                            'status'       => 'confirmed',
                            'items'        => json_encode($confirmed),
                            'total_amount' => $confirmedTotal,
                            'updated_at'   => now(),
                        ]);

                    $msg = "✅ Order #{$order->order_number} partially confirmed from {$branch?->name}. {$deducted} item(s) deducted. ";
                    $msg .= "⏳ " . count($pending) . " insufficient item(s) moved to new order #{$newOrderNumber} (Pending).";
                } else {
                    // Full confirmation
                    \Illuminate\Support\Facades\DB::table('guest_orders')
                        ->where('id', $id)
                        ->update(['status' => 'confirmed', 'updated_at' => now()]);

                    $msg = "✅ Order #{$order->order_number} confirmed from {$branch?->name}. {$deducted} item(s) deducted.";
                }

                if (!empty($warnings)) $msg .= ' — ' . implode('; ', $warnings);
                return back()->with('success', $msg);

            } catch (\Throwable $e) {
                return back()->with('error', 'Error: ' . $e->getMessage());
            }
        })->name('guest.order.confirm.branch');

        // Confirm guest order — deduct stock for each item
        Route::post('/guest/{id}/confirm', function ($id) {
            try {
                if (!\Illuminate\Support\Facades\Schema::hasTable('guest_orders')) {
                    return back()->with('error', 'Guest orders table not found.');
                }
                $order = \Illuminate\Support\Facades\DB::table('guest_orders')->where('id', $id)->first();
                if (!$order) return back()->with('error', 'Order not found.');

                $items = json_decode($order->items, true) ?? [];
                $deducted = 0;
                $errors = [];

                foreach ($items as $item) {
                    $productName = $item['name'] ?? '';
                    $qty = (float)($item['quantity'] ?? 1);

                    $product = \App\Models\Product::withoutGlobalScopes()
                        ->where('name', $productName)->first();
                    if (!$product) {
                        $errors[] = "'{$productName}' not found.";
                        continue;
                    }

                    $type = $product->type;

                    if (in_array($type, ['finished_product', 'bottle_product'])) {
                        $meta   = $product->metadata ?? [];
                        $mfgQty = (float)($meta['manufacturing_quantity'] ?? 0);
                        $meta['manufacturing_quantity'] = max(0, $mfgQty - $qty);
                        $product->metadata = $meta;
                        $product->save();
                        $deducted++;
                    } else {
                        // Deduct from purchase record with highest qty (not zeroed out)
                        $purchase = \Illuminate\Support\Facades\DB::table('purchases')
                            ->where('product_id', $product->id)
                            ->where('status', 'completed')
                            ->where('quantity', '>', 0)
                            ->orderByDesc('quantity')
                            ->first();

                        if (!$purchase) {
                            $purchase = \Illuminate\Support\Facades\DB::table('purchases')
                                ->where('product_id', $product->id)
                                ->where('status', 'completed')
                                ->orderByDesc('purchase_date')
                                ->first();
                        }

                        if ($purchase) {
                            $newQty = max(0, (float)$purchase->quantity - $qty);
                            \Illuminate\Support\Facades\DB::table('purchases')
                                ->where('id', $purchase->id)
                                ->update([
                                    'quantity'    => $newQty,
                                    'total_price' => $newQty * (float)$purchase->unit_price,
                                    'updated_at'  => now(),
                                ]);
                            $deducted++;
                        } else {
                            $errors[] = "No purchase record for '{$productName}'.";
                        }
                    }
                }

                \Illuminate\Support\Facades\DB::table('guest_orders')
                    ->where('id', $id)
                    ->update(['status' => 'confirmed', 'updated_at' => now()]);

                $msg = "Order #{$order->order_number} confirmed! {$deducted} item(s) deducted.";
                if (!empty($errors)) $msg .= ' Issues: ' . implode(', ', $errors);
                return back()->with('success', $msg);

            } catch (\Throwable $e) {
                return back()->with('error', 'Error confirming order: ' . $e->getMessage());
            }
        })->name('guest.order.confirm');

        // Reject guest order
        Route::post('/guest/{id}/reject', function ($id) {
            $order = \Illuminate\Support\Facades\DB::table('guest_orders')->where('id', $id)->first();
            if (!$order) return back()->with('error', 'Order not found.');
            \Illuminate\Support\Facades\DB::table('guest_orders')
                ->where('id', $id)
                ->update(['status' => 'rejected', 'updated_at' => now()]);
            return back()->with('success', "Order #{$order->order_number} rejected.");
        })->name('guest.order.reject');

        // Return confirmed order back to pending — restore stock
        Route::post('/guest/{id}/pending', function ($id) {
            $order = \Illuminate\Support\Facades\DB::table('guest_orders')->where('id', $id)->first();
            if (!$order) return back()->with('error', 'Order not found.');

            $items = json_decode($order->items, true) ?? [];
            $restored = 0;

            foreach ($items as $item) {
                $productName = $item['name'] ?? '';
                $qty = (float)($item['quantity'] ?? 1);

                $product = \App\Models\Product::withoutGlobalScopes()
                    ->where('name', $productName)->first();
                if (!$product) continue;

                $type = $product->type;

                if (in_array($type, ['finished_product', 'bottle_product'])) {
                    // ── Restore manufactured qty back to metadata
                    $meta   = $product->metadata ?? [];
                    $mfgQty = (float)($meta['manufacturing_quantity'] ?? 0);
                    $meta['manufacturing_quantity'] = $mfgQty + $qty;
                    $product->metadata = $meta;
                    $product->save();
                    $restored++;

                } else {
                    // Restore to the purchase record with highest qty
                    $purchase = \Illuminate\Support\Facades\DB::table('purchases')
                        ->where('product_id', $product->id)
                        ->where('status', 'completed')
                        ->orderByDesc('quantity')
                        ->first();

                    if ($purchase) {
                        $newQty = (float)$purchase->quantity + $qty;
                        \Illuminate\Support\Facades\DB::table('purchases')
                            ->where('id', $purchase->id)
                            ->update([
                                'quantity'    => $newQty,
                                'total_price' => $newQty * (float)$purchase->unit_price,
                                'updated_at'  => now(),
                            ]);
                        $restored++;
                    }
                }
            }

            \Illuminate\Support\Facades\DB::table('guest_orders')
                ->where('id', $id)
                ->update(['status' => 'pending', 'updated_at' => now()]);

            return back()->with('success', "Order #{$order->order_number} returned to Pending. {$restored} item(s) stock restored.");
        })->name('guest.order.pending');

        // Update guest order
        Route::put('/guest/{id}/update', function ($id, \Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\DB::table('guest_orders')->where('id', $id)->update([
                'customer_name'  => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'address'        => $request->address,
                'notes'          => $request->notes,
                'updated_at'     => now(),
            ]);
            return back()->with('success', 'Order updated successfully!');
        })->name('guest.order.update');

        // Delete guest order
        Route::delete('/guest/{id}/delete', function ($id) {
            $order = \Illuminate\Support\Facades\DB::table('guest_orders')->where('id', $id)->first();
            \Illuminate\Support\Facades\DB::table('guest_orders')->where('id', $id)->delete();
            return back()->with('success', "Order #{$order->order_number} deleted.");
        })->name('guest.order.delete');
    });

    Route::prefix('customers')->name('customers.')->middleware('permission:customer.view')->group(function () {
        Route::get('/', \App\Livewire\Business\Crm\CustomerDirectory::class)->name('index');
        Route::get('/{id}', \App\Livewire\Business\Crm\CustomerProfile::class)->name('show');
    });

    Route::prefix('reports')->name('reports.')->middleware('permission:report.view')->group(function () {
        Route::get('/', \App\Livewire\Admin\Reports\ReportDashboard::class)->name('index');
        Route::get('/inventory', \App\Livewire\Admin\Reports\ReportDashboard::class)->name('inventory');
        Route::get('/purchase', \App\Livewire\Admin\Reports\ReportDashboard::class)->name('purchase');
        Route::get('/sales', \App\Livewire\Admin\Reports\ReportDashboard::class)->name('sales');
        Route::get('/manufacturing', \App\Livewire\Admin\Reports\ReportDashboard::class)->name('manufacturing');
        Route::get('/business', \App\Livewire\Admin\Reports\ReportDashboard::class)->name('business');
    });

    // ─── User & Access Management ─────────────────────────────────
    Route::prefix('users')->name('users.')->middleware('permission:user.view')->group(function () {
        Route::get('/', \App\Livewire\Admin\Users\UserIndex::class)->name('index');
        Route::get('/create', \App\Livewire\Admin\Users\UserCreate::class)->name('create')->middleware('permission:user.create');
        Route::get('/{id}/edit', \App\Livewire\Admin\Users\UserEdit::class)->name('edit')->middleware('permission:user.update');
    });

    Route::prefix('roles')->name('roles.')->middleware('permission:role.view')->group(function () {
        Route::get('/', \App\Livewire\Admin\Roles\RoleManager::class)->name('index');
    });

    // ─── System (Super Admin only) ────────────────────────────────
    Route::prefix('system')->name('system.')->middleware('permission:system.settings.view')->group(function () {
        Route::get('/settings', \App\Livewire\Admin\System\Settings::class)->name('settings');
        Route::get('/contact',  \App\Livewire\Admin\System\ContactSettings::class)->name('contact')
            ->middleware('permission:system.settings.update');
        Route::get('/audit-logs', \App\Livewire\Admin\System\AuditLogs::class)->name('audit-logs')
            ->middleware('permission:audit-log.view');
    });

    // ─── Settings sidebar links ───────────────────────────────────
    Route::prefix('settings')->name('settings.')->middleware('permission:system.settings.view')->group(function () {
        Route::get('/organization', \App\Livewire\Admin\System\Settings::class)->name('organization');
        Route::get('/contacts', \App\Livewire\Admin\System\ContactSettings::class)->name('contacts')
            ->middleware('permission:system.settings.update');
        Route::get('/localization', \App\Livewire\Admin\System\Settings::class)->name('localization');
        Route::get('/system', \App\Livewire\Admin\System\Settings::class)->name('system');
    });

    // ─── Translation Manager (Super Admin only) ───────────────────
    Route::get('/translations', \App\Livewire\Admin\TranslationManager::class)
        ->name('translations')
        ->middleware(['admin:super', 'permission:system.settings.update']);
});
