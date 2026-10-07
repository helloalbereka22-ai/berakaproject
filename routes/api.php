<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AL-BARAKA PLATFORM — REST API v1
|--------------------------------------------------------------------------
|
| Base URL  : /api/v1/
| Auth      : Laravel Sanctum (Bearer token)
| Isolation : Every business-data endpoint is scoped to the authenticated
|             user's assigned businesses/branches via AuthorizationService.
|             Never trust business_id / branch_id from the client — they
|             are always verified server-side.
|
| Response envelope:
|   { "success": true, "data": {...}, "message": "..." }          200/201
|   { "success": true, "data": [...], "meta": {}, "links": {} }   paginated
|   { "success": false, "message": "...", "errors": {} }          4xx/5xx
|
| Rate limits:
|   POST /auth/login   → throttle:5,1   (5 attempts / minute / IP)
|   GET  /reports/*    → throttle:30,1  (30 requests / minute)
|   All other routes   → throttle:120,1 (120 requests / minute)
|
*/

// ── Public ─────────────────────────────────────────────────────────────────
Route::prefix('v1')->group(function () {

    // Authentication
    Route::post('/auth/login',   [\App\Http\Controllers\Api\V1\AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('api.v1.auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout',  [\App\Http\Controllers\Api\V1\AuthController::class, 'logout'])->name('api.v1.auth.logout');
        Route::get('/auth/me',       [\App\Http\Controllers\Api\V1\AuthController::class, 'me'])->name('api.v1.auth.me');
        Route::post('/auth/refresh', [\App\Http\Controllers\Api\V1\AuthController::class, 'refresh'])->name('api.v1.auth.refresh');
    });

});

// ── Authenticated + Business-Scoped ────────────────────────────────────────
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  ORGANISATION
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    // Businesses
    Route::apiResource('businesses', \App\Http\Controllers\Api\V1\BusinessController::class);

    // Branches
    Route::apiResource('branches', \App\Http\Controllers\Api\V1\BranchController::class);

    // Warehouses (locations with type=warehouse)
    Route::apiResource('warehouses', \App\Http\Controllers\Api\V1\WarehouseController::class);

    // Locations (all physical locations within a branch)
    Route::apiResource('locations', \App\Http\Controllers\Api\V1\LocationController::class);

    // Users (staff accounts)
    Route::apiResource('users', \App\Http\Controllers\Api\V1\UserController::class);

    // Roles & permissions (read-only for non-admins)
    Route::get('/roles',       [\App\Http\Controllers\Api\V1\RoleController::class, 'index']);
    Route::get('/roles/{role}',[\App\Http\Controllers\Api\V1\RoleController::class, 'show']);
    Route::get('/permissions', [\App\Http\Controllers\Api\V1\PermissionController::class, 'index']);

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  PRODUCT CATALOG
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    Route::apiResource('categories', \App\Http\Controllers\Api\V1\CategoryController::class);
    Route::apiResource('products',   \App\Http\Controllers\Api\V1\ProductController::class);

    // Product attributes
    Route::apiResource('products.attributes', \App\Http\Controllers\Api\V1\ProductAttributeController::class)
        ->only(['index', 'store', 'destroy']);

    // Product images — stored in products.metadata['images'], no separate table
    Route::prefix('products/{product}/images')->group(function () {
        Route::get(   '/',           [\App\Http\Controllers\Api\V1\ProductController::class, 'imageIndex']);
        Route::post(  '/',           [\App\Http\Controllers\Api\V1\ProductController::class, 'imageStore']);
        Route::patch( 'set-primary', [\App\Http\Controllers\Api\V1\ProductController::class, 'imageSetPrimary']);
        Route::put(   'reorder',     [\App\Http\Controllers\Api\V1\ProductController::class, 'imageReorder']);
        Route::delete('/',           [\App\Http\Controllers\Api\V1\ProductController::class, 'imageDestroy']);
    });

    // Product collections (bundles)
    Route::apiResource('product-collections', \App\Http\Controllers\Api\V1\ProductCollectionController::class);

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  INVENTORY
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    // Stock levels — read-only current balances derived from stock_movements ledger
    Route::get('stock-levels',              [\App\Http\Controllers\Api\V1\StockLevelController::class, 'index']);
    Route::get('stock-levels/valuation',    [\App\Http\Controllers\Api\V1\StockLevelController::class, 'valuation']);
    Route::post('stock-levels/reconcile',   [\App\Http\Controllers\Api\V1\StockLevelController::class, 'reconcile']);
    Route::get('stock-levels/{productId}',  [\App\Http\Controllers\Api\V1\StockLevelController::class, 'show'])
        ->where('productId', '[0-9]+');

    // Stock movements
    Route::get('stock-movements',                 [\App\Http\Controllers\Api\V1\StockMovementController::class, 'index']);
    Route::get('stock-movements/{stockMovement}', [\App\Http\Controllers\Api\V1\StockMovementController::class, 'show']);
    Route::post('stock-movements/opening',        [\App\Http\Controllers\Api\V1\StockMovementController::class, 'storeOpening']);
    Route::post('stock-movements/adjustment',     [\App\Http\Controllers\Api\V1\StockMovementController::class, 'storeAdjustment']);
    Route::post('stock-movements/transfer',       [\App\Http\Controllers\Api\V1\StockMovementController::class, 'storeTransfer']);

    // Inventory thresholds (low-stock alerts config)
    Route::apiResource('inventory-thresholds', \App\Http\Controllers\Api\V1\InventoryThresholdController::class);

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  CRM & CUSTOMERS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    Route::apiResource('customers', \App\Http\Controllers\Api\V1\CustomerController::class);

    // Customer addresses
    Route::apiResource('addresses', \App\Http\Controllers\Api\V1\AddressController::class);

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  SALES — MARKETPLACE ORDERS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    Route::apiResource('orders',  \App\Http\Controllers\Api\V1\OrderController::class);

    // Order seller workflow actions
    Route::prefix('orders/{order}')->group(function () {
        Route::patch('approve-payment', [\App\Http\Controllers\Api\V1\OrderController::class, 'approvePayment']);
        Route::patch('reject-payment',  [\App\Http\Controllers\Api\V1\OrderController::class, 'rejectPayment']);
        Route::patch('process',         [\App\Http\Controllers\Api\V1\OrderController::class, 'processOrder']);
        Route::patch('complete',        [\App\Http\Controllers\Api\V1\OrderController::class, 'completeOrder']);
    });
    Route::apiResource('invoices',\App\Http\Controllers\Api\V1\InvoiceController::class)->except(['store']);
    Route::apiResource('payments',\App\Http\Controllers\Api\V1\PaymentController::class);
    Route::apiResource('deliveries',\App\Http\Controllers\Api\V1\DeliveryController::class);

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  SALES — BRANCH POS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    Route::apiResource('branch-sales', \App\Http\Controllers\Api\V1\BranchSaleController::class)
        ->except(['update']);

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  DISCOUNTS & PROMOTIONS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    Route::apiResource('discounts',  \App\Http\Controllers\Api\V1\DiscountController::class);
    Route::apiResource('promotions', \App\Http\Controllers\Api\V1\PromotionController::class);

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  MANUFACTURING
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    Route::apiResource('manufacturing-orders', \App\Http\Controllers\Api\V1\ManufacturingOrderController::class);

    // Production batches — must declare static paths BEFORE apiResource to avoid model binding collision
    Route::get( 'production-batches/yield-report', [\App\Http\Controllers\Api\V1\ProductionBatchController::class, 'yieldReport']);
    Route::post('production-batches/compare',      [\App\Http\Controllers\Api\V1\ProductionBatchController::class, 'compare']);

    Route::apiResource('production-batches', \App\Http\Controllers\Api\V1\ProductionBatchController::class);

    // Batch actions
    Route::prefix('production-batches/{productionBatch}')->group(function () {
        Route::post('consume-raw-material', [\App\Http\Controllers\Api\V1\ProductionBatchController::class, 'consumeRawMaterial']);
        Route::post('record-oil-output',    [\App\Http\Controllers\Api\V1\ProductionBatchController::class, 'recordOilOutput']);
        Route::post('quality-check',        [\App\Http\Controllers\Api\V1\ProductionBatchController::class, 'submitQualityCheck']);
        Route::post('complete',             [\App\Http\Controllers\Api\V1\ProductionBatchController::class, 'complete']);
        Route::post('distribute',           [\App\Http\Controllers\Api\V1\ProductionBatchController::class, 'distribute']);
        Route::post('return',               [\App\Http\Controllers\Api\V1\ProductionBatchController::class, 'recordReturn']);
        Route::post('reconcile',            [\App\Http\Controllers\Api\V1\ProductionBatchController::class, 'reconcile']);
        Route::get( 'traceability',         [\App\Http\Controllers\Api\V1\ProductionBatchController::class, 'traceability']);
    });

    // Quality checks (standalone resource — also submittable via batch action above)
    Route::apiResource('quality-checks', \App\Http\Controllers\Api\V1\QualityCheckController::class)
        ->except(['destroy']);

    // Finished goods (read-only — created by manufacturing workflow)
    Route::apiResource('finished-goods', \App\Http\Controllers\Api\V1\FinishedGoodController::class)
        ->only(['index', 'show']);

    // Distributions (factory → sales point)
    Route::apiResource('distributions', \App\Http\Controllers\Api\V1\DistributionController::class);

    // Batch returns
    Route::apiResource('batch-returns', \App\Http\Controllers\Api\V1\BatchReturnController::class)
        ->only(['index', 'show', 'store']);

    // Batch reconciliations
    Route::apiResource('batch-reconciliations', \App\Http\Controllers\Api\V1\BatchReconciliationController::class)
        ->only(['index', 'show']);

    // Batch material consumption records
    Route::apiResource('batch-consumption', \App\Http\Controllers\Api\V1\BatchMaterialConsumptionController::class)
        ->only(['index', 'show']);

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  FINANCIALS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    Route::apiResource('profit-ledger', \App\Http\Controllers\Api\V1\ProfitLedgerController::class)
        ->only(['index', 'show']);

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  CMS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    Route::apiResource('articles', \App\Http\Controllers\Api\V1\ArticleController::class);
    Route::get('articles/slug/{slug}', [\App\Http\Controllers\Api\V1\ArticleController::class, 'showBySlug'])
        ->name('api.v1.articles.slug');

    Route::apiResource('faqs', \App\Http\Controllers\Api\V1\FAQController::class);

    Route::apiResource('media-files', \App\Http\Controllers\Api\V1\MediaFileController::class)
        ->only(['index', 'show', 'store', 'destroy']);

    Route::apiResource('tags', \App\Http\Controllers\Api\V1\TagController::class);

    // Languages (public read — write requires admin)
    Route::apiResource('languages', \App\Http\Controllers\Api\V1\LanguageController::class)
        ->only(['index', 'show', 'store', 'update']);

    // Content translations (for translatable content via DB)
    Route::apiResource('translations', \App\Http\Controllers\Api\V1\TranslationController::class)
        ->only(['index', 'store', 'destroy']);

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  SETTINGS & SYSTEM
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    Route::apiResource('settings',     \App\Http\Controllers\Api\V1\SettingController::class)
        ->only(['index', 'update']);
    Route::apiResource('notifications',\App\Http\Controllers\Api\V1\NotificationController::class)
        ->only(['index', 'show', 'destroy']);

    // Approval workflows
    Route::apiResource('approval-workflows', \App\Http\Controllers\Api\V1\ApprovalWorkflowController::class);
    Route::apiResource('approval-requests',  \App\Http\Controllers\Api\V1\ApprovalRequestController::class);

    // Audit logs (read-only)
    Route::apiResource('audit-logs', \App\Http\Controllers\Api\V1\AuditLogController::class)
        ->only(['index', 'show']);

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  REPORTS (rate-limited separately)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    Route::prefix('reports')->name('api.v1.reports.')->middleware('throttle:30,1')->group(function () {
        Route::get('/kpis',              [\App\Http\Controllers\Api\V1\ReportController::class, 'kpis'])->name('kpis');
        Route::get('/sales',             [\App\Http\Controllers\Api\V1\ReportController::class, 'sales'])->name('sales');
        Route::get('/profit',            [\App\Http\Controllers\Api\V1\ReportController::class, 'profit'])->name('profit');
        Route::get('/inventory',         [\App\Http\Controllers\Api\V1\ReportController::class, 'inventory'])->name('inventory');
        Route::get('/manufacturing',     [\App\Http\Controllers\Api\V1\ReportController::class, 'manufacturing'])->name('manufacturing');
        Route::get('/purchasing',        [\App\Http\Controllers\Api\V1\ReportController::class, 'purchasing'])->name('purchasing');
        Route::get('/branches',          [\App\Http\Controllers\Api\V1\ReportController::class, 'branches'])->name('branches');
        // Retail-specific reports (Step 48)
        Route::get('/retail/period-sales',    [\App\Http\Controllers\Api\V1\ReportController::class, 'retailPeriodSales'])->name('retail.period-sales');
        Route::get('/retail/branch',          [\App\Http\Controllers\Api\V1\ReportController::class, 'retailBranchReport'])->name('retail.branch');
        Route::get('/retail/branch-product',  [\App\Http\Controllers\Api\V1\ReportController::class, 'retailBranchProduct'])->name('retail.branch-product');
        Route::get('/export/{type}',     [\App\Http\Controllers\Api\V1\ReportController::class, 'exportCsv'])->name('export');
    });

});
