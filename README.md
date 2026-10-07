# Albareka — Enterprise Business Management Platform

A professional, modular Laravel 11 enterprise platform.

---

## System Requirements

| Requirement   | Version     |
|---------------|-------------|
| PHP           | 8.2+        |
| MySQL         | 8.0+        |
| Redis         | 6.0+        |
| Node.js       | 20+         |
| Composer      | 2.x         |
| npm           | 10+         |

---

## Local Installation

```bash
# 1. Clone the repository
git clone <repo-url> albareka && cd albareka

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies
npm install

# 4. Environment setup
cp .env.example .env
php artisan key:generate

# 5. Configure .env
#    Set DB_DATABASE, DB_USERNAME, DB_PASSWORD for your local MySQL instance.

# 6. Run migrations and seed
php artisan migrate --seed

# 7. Build frontend assets
npm run dev

# 8. Start the server
php artisan serve
```

The application works with **Laravel Herd**, **Laravel Valet**, or `php artisan serve`.

---

## Production Environment

| Component     | Recommendation            |
|---------------|---------------------------|
| Web Server    | Nginx or Apache           |
| PHP           | PHP-FPM 8.2+              |
| Database      | MySQL 8.0+                |
| Cache / Queue | Redis                     |
| Asset Build   | `npm run build` (Vite)    |

---

## Module Architecture

```
app/Modules/
├── Identity/           # Users, Roles, Permissions, Authentication (Sanctum), AuthorizationService
├── BusinessOrg/        # Businesses, Branches, Warehouses
├── Catalog/            # Products, Categories, Attributes, Media
├── Localization/       # Multi-language translations, RTL Support
├── Manufacturing/      # (Phase 3) Manufacturing Orders, Production Batches
├── Inventory/          # (Phase 3) Stock Movements, Ledger
├── Sales/              # (Phase 3) Sales Orders
├── Reporting/          # (Phase 3) Reports
├── CMS/                # (Phase 3) Content Management
└── Platform/           # (Phase 3) System-level services
```

Each module follows **clean architecture**:

```
Module/
├── Domain/           # Models, Value Objects
├── Application/      # Services, Jobs, Policies
├── Infrastructure/   # Repositories, External APIs
└── Presentation/     # Livewire Components, Blade Views
```

---

## Authentication & RBAC

### Seeded Roles
- **Super Admin** — Full system access across all businesses.
- **Business Manager** — Scoped to a specific business.
- **Manufacturing Manager** — Scoped to manufacturing within a business.

### Authorization Service
Located at `App\Modules\Identity\Application\Services\AuthorizationService`.

```php
$authService = new AuthorizationService();
$authService->canWriteStock($user);             // Global check
$authService->canWriteStock($user, $business);  // Business-scoped check
```

| Role                    | `canWriteStock()` |
|-------------------------|:-----------------:|
| Super Admin             | ✅ Allowed        |
| Business Manager        | ❌ Denied         |
| Manufacturing Manager   | ❌ Denied         |

Cross-business access is automatically blocked for managers via `AuthorizationService`.

---

## Phase 2 Features

### Shared Product Catalog
- **Dynamic Categories:** Unlimited nesting for product categories.
- **Unified Products Table:** Stores foundational product data (SKU, pricing, unit of measure) and tracks generic inventory targets like `stock_quantity`.
- **EAV Attributes:** Extend product information using `ProductAttribute` and `AttributeValue` dynamically.
- **Product Media:** Supports multiple sorted images with primary image tracking.

### Localization & RTL Support
- Built-in `Language` and `Translation` polymorphic models.
- Fully integrated `LanguageSwitcher` in the UI.
- Dynamically injects `dir="rtl"` into the HTML structure based on active session locale.

---

## Business Types

Business types are **configurable strings**, not separate tables:

```php
Business::create([
    'name' => 'My Factory',
    'slug' => 'my-factory',
    'business_type' => 'Manufacturing', // or Retail, Bookstore, Warehouse, etc.
]);
```

---

## Development Tools

### Laravel Telescope
- Enabled in `local` environment only.
- Access at: `/telescope`
- Disabled automatically in production (`TELESCOPE_ENABLED=false`).

### GitHub Actions CI
Located at `.github/workflows/ci.yml`. Runs on push/PR to `main` and `develop`:

1. Composer Install
2. Node Install
3. Build Assets
4. Laravel Pint (code style)
5. PHPStan / Larastan (static analysis)
6. Run Migrations
7. PHPUnit / Pest Tests



## Default Credentials

| Email                | Password   | Role        |
|----------------------|------------|-------------|
| admin@albareka.com   | password   | Super Admin |

---

## License

Proprietary. All rights reserved.
