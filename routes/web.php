<?php

use Illuminate\Support\Facades\Route;

// ── Sitemap for SEO ──────────────────────────────────────────────────────
Route::get('/sitemap.xml', function () {
    $products = \App\Models\Product::withoutGlobalScopes()
        ->where('status', 'active')
        ->where('is_marketplace_visible', true)
        ->select('id', 'updated_at')
        ->get();

    $pages = [
        ['url' => route('home'), 'changefreq' => 'daily', 'priority' => '1.0'],
        ['url' => route('store.products'), 'changefreq' => 'daily', 'priority' => '0.9'],
        ['url' => route('manufacturing'), 'changefreq' => 'weekly', 'priority' => '0.8'],
        ['url' => route('bookstore'), 'changefreq' => 'weekly', 'priority' => '0.8'],
        ['url' => route('about'), 'changefreq' => 'monthly', 'priority' => '0.7'],
        ['url' => route('contact'), 'changefreq' => 'monthly', 'priority' => '0.6'],
    ];

    return response()->view('seo.sitemap', compact('pages', 'products'))->header('Content-Type', 'application/xml');
})->name('sitemap');

// Root → Show homepage
Route::get('/', \App\Livewire\Store\Storefront::class)->name('home');

// ── Public corporate website ──────────────────────────────────────────────
Route::get('/about',           \App\Livewire\Store\AboutPage::class)->name('about');
Route::get('/organizations',   \App\Livewire\Store\OrganizationsPage::class)->name('organizations');
Route::get('/manufacturing',   \App\Livewire\Store\ManufacturingPage::class)->name('manufacturing');
Route::get('/bookstore',       \App\Livewire\Store\BookstorePage::class)->name('bookstore');
Route::get('/contact',         \App\Livewire\Store\ContactPage::class)->name('contact');

// Contact form submission (POST)
Route::post('/contact/submit', [\App\Http\Controllers\ContactController::class, 'submit'])->name('contact.submit');

// CMS — Articles & Blog
Route::get('/blog',              \App\Livewire\Store\ArticleList::class)->name('blog');
Route::get('/blog/{slug}',       \App\Livewire\Store\ArticleDetail::class)->name('blog.show');
Route::get('/announcements',     \App\Livewire\Store\ArticleList::class)->name('announcements');
Route::get('/announcements/{slug}', \App\Livewire\Store\ArticleDetail::class)->name('announcements.show');

// News — dedicated public news pages
Route::get('/news',              \App\Livewire\Store\NewsPage::class)->name('news');
Route::get('/news/{slug}',       \App\Livewire\Store\NewsDetail::class)->name('news.show');

// CMS — FAQs
Route::get('/faqs', \App\Livewire\Store\FaqPage::class)->name('faqs');

// CMS — Dynamic static pages (About, Privacy Policy, Terms, etc.)
Route::get('/page/{slug}', \App\Livewire\Store\PublicPageViewer::class)->name('page');

// Public search
Route::get('/search', \App\Livewire\Store\PublicSearch::class)->name('search');


// ── Language switcher ────────────────────────────────────────────────────
Route::get('/set-language', function () {
    $code    = request('lang', 'en');
    $allowed = ['en', 'am', 'ar', 'om'];
    
    $response = redirect(request()->headers->get('referer', url('/')));
    
    if (in_array($code, $allowed)) {
        $lang = \App\Models\Language::where('code', $code)->first();
        // Store in both session AND cookie for reliability
        session(['locale' => $code]);
        session(['is_rtl'  => $lang ? (bool)$lang->is_rtl : false]);
        
        // Also set cookies that persist (30 days)
        $response->cookie('locale', $code, 60 * 24 * 30, '/', null, false, true);
        $response->cookie('is_rtl', $lang ? (bool)$lang->is_rtl : false, 60 * 24 * 30, '/', null, false, true);
    }
    
    return $response;
})->name('language.switch')->middleware('web');

// ── Guest Cart (no login required) ───────────────────────────────────────
Route::match(['GET', 'POST'], '/guest/cart/add', function (\Illuminate\Http\Request $request) {
    $productId = (int)($request->product_id ?? $request->get('product_id'));
    $quantity  = (float)($request->quantity ?? 1);
    
    $success = false;
    
    if ($productId > 0) {
        $success = app(\App\Services\GuestCartService::class)->addToCart($productId, $quantity);
    }
    
    // For regular page redirects, just go back
    if (!$request->header('X-Requested-With') && !$request->wantsJson()) {
        return back()->with('cart_added', $success);
    }
    
    // For AJAX/JSON requests, return data
    $summary = app(\App\Services\GuestCartService::class)->getSummary();
    return response()->json([
        'success' => $success,
        'count' => $summary['count'],
        'subtotal' => $summary['subtotal'],
        'message' => $success ? 'Added to cart' : 'Product not available'
    ]);
})->name('guest.cart.add');

Route::get('/guest/cart', function () {
    return redirect()->route('guest.checkout');
})->name('guest.cart');

// Dedicated checkout page
Route::get('/guest/checkout', function () {
    $summary = app(\App\Services\GuestCartService::class)->getSummary();
    $old     = session()->getOldInput();
    return view('store.guest-cart-plain', compact('summary', 'old'));
})->name('guest.checkout');

// Update qty
Route::get('/guest/cart/update', function () {
    $productId = (int) request('product_id');
    $qty = (float) request('qty', 1);
    app(\App\Services\GuestCartService::class)->updateQuantity($productId, $qty);
    if (request()->header('X-Requested-With') === 'XMLHttpRequest' || request()->wantsJson()) {
        $summary = app(\App\Services\GuestCartService::class)->getSummary();
        return response()->json(['ok' => true, 'count' => $summary['count'], 'subtotal' => $summary['subtotal']]);
    }
    return redirect()->route('guest.checkout');
});

// Remove item
Route::get('/guest/cart/remove', function () {
    $productId = (int) request('product_id');
    app(\App\Services\GuestCartService::class)->removeFromCart($productId);
    if (request()->header('X-Requested-With') === 'XMLHttpRequest' || request()->wantsJson()) {
        $summary = app(\App\Services\GuestCartService::class)->getSummary();
        return response()->json(['ok' => true, 'count' => $summary['count'], 'subtotal' => $summary['subtotal']]);
    }
    return redirect()->route('guest.checkout');
});

// Guest cart order submission (plain HTML form — no Livewire)
Route::post('/guest/cart/order', function (\Illuminate\Http\Request $request) {
    try {
        $request->validate([
            'customer_name'  => 'required|min:2',
            'customer_phone' => 'required|min:7',
            'address'        => 'required|min:5',
        ]);

        $cart = app(\App\Services\GuestCartService::class)->getSummary();

        if (empty($cart['items'])) {
            return back()->withErrors(['cart' => 'Your cart is empty. Please add items first.']);
        }

        // Create table if missing
        if (!\Illuminate\Support\Facades\Schema::hasTable('guest_orders')) {
            \Illuminate\Support\Facades\Schema::create('guest_orders', function ($table) {
                $table->id();
                $table->string('order_number')->unique();
                $table->string('customer_name');
                $table->string('customer_phone');
                $table->string('customer_email')->nullable();
                $table->text('address');
                $table->text('notes')->nullable();
                $table->json('items');
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        $orderNumber = 'ORD-' . strtoupper(substr(md5(uniqid()), 0, 8));

        \Illuminate\Support\Facades\DB::table('guest_orders')->insert([
            'order_number'   => $orderNumber,
            'customer_name'  => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'customer_email' => $request->customer_email ?: null,
            'address'        => $request->address,
            'notes'          => $request->notes ?: null,
            'items'          => json_encode($cart['items']),
            'total_amount'   => $cart['subtotal'],
            'status'         => 'pending',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        app(\App\Services\GuestCartService::class)->clearCart();

        return redirect()->route('home')->with('order_success', $orderNumber);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return back()->withErrors($e->errors())->withInput();
    } catch (\Throwable $e) {
        return back()->withErrors(['error' => 'Failed to place order: ' . $e->getMessage()])->withInput();
    }
})->name('guest.cart.order');

// Order success page
Route::get('/guest/order/success/{order}', function (string $order) {
    return view('store.order-success', ['orderNumber' => $order]);
})->name('guest.order.success');

// ── Customer authentication (marketplace) ────────────────────────────────
// Sign In redirects to the unified login page (/login) which handles all user types
Route::get('/customer/login', fn() => redirect()->route('login'))->name('customer.login');

Route::middleware('guest:customer')->group(function () {
    Route::get('/customer/register', \App\Livewire\Store\CustomerRegister::class)->name('customer.register');
});

Route::post('/customer/logout', function () {
    \Illuminate\Support\Facades\Auth::guard('customer')->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('store.index');
})->name('customer.logout');

// ── Marketplace (public) ──────────────────────────────────────────────────
Route::name('store.')->prefix('store')->group(function () {
    // Public pages
    Route::get('/',             \App\Livewire\Store\Storefront::class)->name('index');
    Route::get('/products',     \App\Livewire\Store\ProductList::class)->name('products');
    Route::get('/product/{id}', \App\Livewire\Store\ProductDetail::class)->name('product');
    Route::get('/cart',         \App\Livewire\Store\CartPage::class)->name('cart');
    Route::get('/wishlist',     \App\Livewire\Store\WishlistPage::class)->name('wishlist');

    // Add to cart (POST) — redirects to login if not authenticated
    Route::post('/cart/add', function (\Illuminate\Http\Request $request) {
        $customerId = auth('customer')->id();
        if (!$customerId) {
            session()->put('url.intended', url()->previous());
            return redirect()->route('login');
        }
        $request->validate(['product_id' => 'required|integer', 'quantity' => 'required|numeric|min:1']);
        app(\App\Services\SalesService::class)->addToCart($customerId, $request->product_id, $request->quantity);
        return back()->with('cart_success', 'Product added to cart!');
    })->name('cart.add');
    // Checkout (requires customer auth)
    Route::get('/checkout', \App\Livewire\Store\CheckoutPage::class)
        ->middleware('auth:customer')
        ->name('checkout');

    // Order confirmation — requires customer auth (prevents order detail exposure via guessable order numbers)
    Route::get('/order/confirmation/{orderNumber}', \App\Livewire\Store\OrderConfirmation::class)
        ->middleware('auth:customer')
        ->name('order.confirmation');

    // Customer account (requires customer auth)
    Route::middleware('auth:customer')->prefix('account')->name('account.')->group(function () {
        Route::get('/profile', \App\Livewire\Store\AccountProfile::class)->name('profile');
        Route::get('/orders',  \App\Livewire\Store\AccountOrders::class)->name('orders');
        Route::get('/orders/{orderNumber}', \App\Livewire\Store\AccountOrderDetail::class)->name('order');
    });
});

// Test route for debugging
Route::get('/test-login-link', function () {
    return view('test-login-link');
});

// Simple login page (fallback if Livewire has issues)
Route::get('/simple-login', function () {
    return view('simple-login');
});
Route::post('/simple-login', function (\Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (\Illuminate\Support\Facades\Auth::attempt($credentials, $request->filled('remember'))) {
        $request->session()->regenerate();
        
        $user = \Illuminate\Support\Facades\Auth::user();
        
        // Role-based redirect (same logic as Livewire login)
        if ($user->roles()->whereIn('name', ['Super Admin', 'Admin', 'Business Manager'])->exists()) {
            return redirect()->route('admin.dashboard');
        }
        
        if ($user->roles()->whereIn('name', [
            'Manufacture', 'Manufacturing Manager', 'Factory Supervisor',
            'Manufacturing Staff', 'Warehouse Staff',
        ])->exists()) {
            return redirect()->route('admin.manufacturing.dashboard');
        }
        
        if ($user->roles()->whereIn('name', ['Branch Seller', 'Seller', 'Sales Staff'])->exists()) {
            return redirect()->route('admin.branch-seller.dashboard');
        }
        
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        
        return redirect()->route('home');
    }

    return back()->withErrors([
        'email' => 'These credentials do not match our records.',
    ])->withInput($request->only('email'));
});

Route::get('/login', \App\Livewire\Public\Login::class)->name('login');
Route::get('/register', \App\Livewire\Public\Register::class)->name('register')->middleware('guest');
Route::get('/forgot-password', \App\Livewire\Public\ForgotPassword::class)->name('password.request')->middleware('guest');
Route::get('/reset-password/{token}', \App\Livewire\Public\ResetPassword::class)->name('password.reset')->middleware('guest');

Route::middleware('auth')->group(function () {
    Route::get('/profile', \App\Livewire\Business\ProfileManager::class)->name('profile');
    
    Route::post('/logout', function () {
        \Illuminate\Support\Facades\Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect('/login');
    })->name('logout');
});

Route::get('/robots.txt', function () {
    return response("User-agent: *\nDisallow:", 200)
        ->header('Content-Type', 'text/plain');
});

Route::get('/sitemap.xml', function () {
    $urls = collect([['loc' => url('/'), 'priority' => '1.0', 'freq' => 'daily']]);

    // Articles
    \App\Models\Article::published()->orderByDesc('published_at')->limit(200)->get(['slug', 'updated_at'])
        ->each(fn($a) => $urls->push(['loc' => route('blog.show', $a->slug), 'priority' => '0.7', 'freq' => 'weekly', 'lastmod' => $a->updated_at?->toAtomString()]));

    // Products
    \App\Models\Product::withoutGlobalScopes()
        ->where('status', 'active')->where('is_marketplace_visible', true)
        ->orderBy('name')->limit(500)->get(['id', 'updated_at'])
        ->each(fn($p) => $urls->push(['loc' => route('store.product', $p->id), 'priority' => '0.8', 'freq' => 'weekly', 'lastmod' => $p->updated_at?->toAtomString()]));

    // Static pages
    foreach (['about', 'manufacturing', 'bookstore', 'faqs', 'contact', 'blog'] as $page) {
        $urls->push(['loc' => route($page), 'priority' => '0.6', 'freq' => 'monthly']);
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($urls as $u) {
        $xml .= '<url><loc>' . htmlspecialchars($u['loc']) . '</loc>';
        if (!empty($u['lastmod'])) $xml .= '<lastmod>' . $u['lastmod'] . '</lastmod>';
        $xml .= '<changefreq>' . ($u['freq'] ?? 'monthly') . '</changefreq>';
        $xml .= '<priority>' . ($u['priority'] ?? '0.5') . '</priority></url>';
    }
    $xml .= '</urlset>';

    return response($xml, 200)->header('Content-Type', 'text/xml');
});

// Storage image serving route (workaround for missing symlink)
Route::get('/storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    
    if (!file_exists($fullPath)) {
        abort(404);
    }
    
    return response()->file($fullPath);
})->where('path', '.*')->name('storage.serve');
