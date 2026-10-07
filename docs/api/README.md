# Al-Baraka Platform — REST API v1
## Global API Reference

**Version:** 1.0.0  
**Base URL:** `https://your-domain.com/api/v1/`  
**Protocol:** HTTPS only  
**Auth:** Laravel Sanctum — Bearer token  
**Format:** `Content-Type: application/json` · `Accept: application/json`  
**Charset:** UTF-8  
**Total endpoints:** 202

---

## Table of Contents

1. [Getting Started](#1-getting-started)
2. [Authentication](#2-authentication)
3. [Response Envelope](#3-response-envelope)
4. [HTTP Status Codes](#4-http-status-codes)
5. [Pagination](#5-pagination)
6. [Filtering & Sorting](#6-filtering--sorting)
7. [Security & Business Isolation](#7-security--business-isolation)
8. [Role Hierarchy & Permissions](#8-role-hierarchy--permissions)
9. [Rate Limiting](#9-rate-limiting)
10. [Organisation — Businesses](#10-organisation--businesses)
11. [Organisation — Branches](#11-organisation--branches)
12. [Organisation — Warehouses](#12-organisation--warehouses)
13. [Organisation — Locations](#13-organisation--locations)
14. [Users & Access](#14-users--access)
15. [Roles & Permissions](#15-roles--permissions)
16. [Product Catalog — Categories](#16-product-catalog--categories)
17. [Product Catalog — Products](#17-product-catalog--products)
18. [Product Attributes & Images](#18-product-attributes--images)
19. [Product Collections (Bundles)](#19-product-collections-bundles)
20. [Inventory — Stock Levels](#20-inventory--stock-levels)
21. [Inventory — Stock Movements](#21-inventory--stock-movements)
22. [Inventory — Thresholds](#22-inventory--thresholds)
23. [CRM — Customers](#23-crm--customers)
24. [CRM — Addresses](#24-crm--addresses)
25. [Sales — Orders](#25-sales--orders)
26. [Sales — Invoices](#26-sales--invoices)
27. [Sales — Payments](#27-sales--payments)
28. [Sales — Deliveries](#28-sales--deliveries)
29. [Sales — Branch POS](#29-sales--branch-pos)
30. [Sales — Discounts & Promotions](#30-sales--discounts--promotions)
31. [Manufacturing — Orders](#31-manufacturing--orders)
32. [Manufacturing — Production Batches](#32-manufacturing--production-batches)
33. [Manufacturing — Batch Actions](#33-manufacturing--batch-actions)
34. [Manufacturing — Quality Control](#34-manufacturing--quality-control)
35. [Manufacturing — Finished Goods](#35-manufacturing--finished-goods)
36. [Manufacturing — Distributions](#36-manufacturing--distributions)
37. [Manufacturing — Batch Returns](#37-manufacturing--batch-returns)
38. [Manufacturing — Reconciliations](#38-manufacturing--reconciliations)
39. [Manufacturing — Consumption Records](#39-manufacturing--consumption-records)
40. [Financials — Profit Ledger](#40-financials--profit-ledger)
41. [CMS — Articles](#41-cms--articles)
42. [CMS — FAQs](#42-cms--faqs)
43. [CMS — Media Files](#43-cms--media-files)
44. [CMS — Tags](#44-cms--tags)
45. [CMS — Languages](#45-cms--languages)
46. [CMS — Translations](#46-cms--translations)
47. [Reports](#47-reports)
48. [System — Settings](#48-system--settings)
49. [System — Notifications](#49-system--notifications)
50. [System — Audit Logs](#50-system--audit-logs)
51. [Approval Workflows](#51-approval-workflows)
52. [Error Reference](#52-error-reference)

---

## 1. Getting Started

### Quick-start checklist

```
1. POST /api/v1/auth/login          → receive Bearer token
2. Add header: Authorization: Bearer <token>
3. Add header: Accept: application/json
4. Add header: Content-Type: application/json
5. All responses return JSON with a consistent envelope (see §3)
```

### Environment variables

| Variable | Default | Purpose |
|---|---|---|
| `SANCTUM_TOKEN_EXPIRY` | `43200` (30 days in minutes) | Token lifetime |
| `APP_URL` | — | Base URL used in pagination links |
| `APP_ENV` | `local` | Set to `production` in live |

### Test your token

```http
GET /api/v1/auth/me
Authorization: Bearer 1|your-token-here
Accept: application/json
```

---

## 2. Authentication

All endpoints except `POST /auth/login` require a valid Bearer token.

### 2.1 Login

```
POST /api/v1/auth/login
```

**Rate limit:** 5 attempts / minute / IP. Exceeding returns `429`.

**Request body:**

| Field | Type | Required | Description |
|---|---|---|---|
| `email` | string | ✅ | Staff account email |
| `password` | string | ✅ | Minimum 6 characters |
| `device_name` | string | ✗ | Token label (default: `api-client-{ip}`) |

```json
{
  "email": "admin@albareka.com",
  "password": "Albarecka1122@",
  "device_name": "mobile-app-v1"
}
```

**Response 200 — Authenticated:**

```json
{
  "success": true,
  "message": "Authenticated.",
  "data": {
    "user": {
      "id": 1,
      "name": "System Super Admin",
      "email": "admin@albareka.com",
      "status": "active",
      "roles": [
        {
          "id": 1,
          "name": "Super Admin",
          "pivot": { "business_id": null, "branch_id": null }
        }
      ]
    },
    "token": "1|abc123def456..."
  }
}
```

**Response 401 — Wrong credentials:**

```json
{ "success": false, "message": "Invalid credentials." }
```

**Response 429 — Too many attempts:**

```json
{ "success": false, "message": "Too many login attempts. Please try again in 47 seconds." }
```

---

### 2.2 Logout

```
POST /api/v1/auth/logout
```

Revokes the current token only. All other tokens for the same user remain valid.

**Response 200:**

```json
{ "success": true, "message": "Logged out successfully.", "data": null }
```

---

### 2.3 Get current user

```
GET /api/v1/auth/me
```

Returns the authenticated user's profile with roles.

**Response 200:**

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "id": 1,
    "name": "System Super Admin",
    "email": "admin@albareka.com",
    "status": "active",
    "roles": [...],
    "assigned_business_ids": []
  }
}
```

---

### 2.4 Refresh token

```
POST /api/v1/auth/refresh
```

Revokes the current token and issues a new one with the same device name.

**Response 200:**

```json
{ "success": true, "message": "Token refreshed.", "data": { "token": "2|newtoken..." } }
```

---

## 3. Response Envelope

Every response — success or error — wraps its payload in a consistent envelope.

### 3.1 Single resource (200 / 201)

```json
{
  "success": true,
  "message": "OK",
  "data": { ...resource fields... }
}
```

### 3.2 Paginated collection (200)

```json
{
  "success": true,
  "data": [ ...array of resources... ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 8,
    "per_page": 25,
    "to": 25,
    "total": 198
  },
  "links": {
    "first": "https://your-domain.com/api/v1/products?page=1",
    "last":  "https://your-domain.com/api/v1/products?page=8",
    "prev":  null,
    "next":  "https://your-domain.com/api/v1/products?page=2"
  }
}
```

### 3.3 Validation error (422)

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "email":    ["The email field is required."],
    "quantity": ["The quantity must be at least 0.0001."]
  }
}
```

### 3.4 Generic error (4xx / 5xx)

```json
{ "success": false, "message": "You do not have access to this business." }
```

### 3.5 Empty success (delete / cancel)

```json
{ "success": true, "message": "Order cancelled.", "data": null }
```

---

## 4. HTTP Status Codes

| Code | When used |
|---|---|
| `200 OK` | GET, PATCH, PUT succeeded; DELETE with message |
| `201 Created` | POST created a new resource |
| `204 No Content` | Successful operation with no body |
| `401 Unauthorized` | Missing, expired, or invalid Bearer token |
| `403 Forbidden` | Authenticated but no permission for this resource/business |
| `404 Not Found` | Resource does not exist or is not visible to this user |
| `409 Conflict` | Operation not allowed in current state (e.g. deleting a paid invoice) |
| `422 Unprocessable Entity` | Validation errors — see `errors` object |
| `429 Too Many Requests` | Rate limit exceeded |
| `500 Internal Server Error` | Unexpected server-side error |

---

## 5. Pagination

All list endpoints are paginated. Control with:

| Parameter | Type | Default | Max |
|---|---|---|---|
| `page` | int | 1 | — |
| `per_page` | int | 25 | 100 |

```
GET /api/v1/products?page=2&per_page=50
```

Pagination metadata is in the `meta` block of every collection response (see §3.2).

---

## 6. Filtering & Sorting

### Universal filters (all list endpoints)

| Parameter | Type | Description |
|---|---|---|
| `business_id` | int | Filter by business — verified server-side |
| `branch_id` | int | Filter by branch |
| `status` | string | Filter by entity status |
| `search` | string | Full-text / LIKE search |
| `from` | date (Y-m-d) | Date range start |
| `to` | date (Y-m-d) | Date range end |
| `per_page` | int | Page size (default 25, max 100) |

### Endpoint-specific filters

Additional filters are documented under each endpoint section.

### Sorting

Most endpoints sort by a sensible default (e.g. `placed_at DESC`, `name ASC`). Custom sort is not yet exposed as a query parameter but will be added in v1.1.

---

## 7. Security & Business Isolation

### Five-layer enforcement

```
Layer 1 — Route          throttle + auth:sanctum middleware
Layer 2 — Controller     AuthorizationService::assertBusinessAccess() on every action
Layer 3 — Resource       Never exposes password, tokens, or cross-business data
Layer 4 — Service        All business logic validates ownership internally
Layer 5 — Database       Queries always include business_id / branch_id scope
```

### Business ID trust rule

> **Never send `business_id` as a trusted client value for write operations.**
> The API always resolves business context from the authenticated user's roles.
> Sending an incorrect `business_id` for a resource you don't own returns `403`.

### Cross-business access

Users are scoped to their assigned businesses. A user assigned to Business A:
- Cannot read, write, or delete any data from Business B
- Will receive an empty result set (not a 403) on list endpoints for unassigned businesses
- Will receive `403` on direct access to a specific Business B resource

Super Administrators have platform-wide access and can filter any endpoint by any `business_id`.

### Token lifetime

Tokens expire after **30 days** by default. Configurable via `SANCTUM_TOKEN_EXPIRY` (minutes) in `.env`. Expired tokens return `401`.

---

## 8. Role Hierarchy & Permissions

| Role | Scope | Typical capabilities |
|---|---|---|
| **Super Administrator** | Platform-wide | Full access to all endpoints |
| **Business Manager** | Assigned business | Manage own business, branches, products, orders |
| **Branch Manager** | Assigned branch | Manage branch inventory, POS sales, staff |
| **Warehouse Staff** | Assigned warehouse | Stock movements, adjustments |
| **Sales Staff** | Assigned branch | POS sales, orders, invoices |
| **Factory Supervisor** | Manufacturing module | Batches, QC, distributions |
| **Customer** | Marketplace only | Public catalog, own orders |

Permission names follow the pattern `{resource}.{action}` e.g. `product.create`, `order.view`.

---

## 9. Rate Limiting

| Endpoint group | Limit |
|---|---|
| `POST /auth/login` | 5 requests / minute / IP |
| `GET /reports/*` | 30 requests / minute / token |
| All other `/api/v1/*` | 120 requests / minute / token |

Exceeded limit returns `429` with a `Retry-After` header.

---

---

## 10. Organisation — Businesses

### Endpoints

| Method | URI | Description | Auth required |
|---|---|---|---|
| `GET` | `/businesses` | List businesses scoped to user | Any authenticated |
| `POST` | `/businesses` | Create business | Super Admin |
| `GET` | `/businesses/{id}` | Get business with branches | Assigned users |
| `PATCH` | `/businesses/{id}` | Update business | Assigned managers |
| `DELETE` | `/businesses/{id}` | Soft-delete business | Super Admin |

### GET /businesses

**Filters:** `status` (active|inactive), `search`

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Al-Baraka Oil Factory",
      "code": "OIL-FACTORY",
      "description": "Lubricating oil manufacturing",
      "status": "active",
      "branches_count": 2
    }
  ],
  "meta": { "total": 2, ... }
}
```

### POST /businesses

**Body:**

| Field | Type | Required | Rules |
|---|---|---|---|
| `name` | string | ✅ | max 255 |
| `code` | string | ✅ | unique, max 50 |
| `description` | string | ✗ | max 1000 |
| `status` | string | ✗ | active \| inactive |

### PATCH /businesses/{id}

All fields optional (PATCH semantics). `code` must remain unique.

### DELETE /businesses/{id}

Soft-deletes. Returns `409` if business has active orders.

---

## 11. Organisation — Branches

| Method | URI | Description |
|---|---|---|
| `GET` | `/branches` | List branches |
| `POST` | `/branches` | Create branch |
| `GET` | `/branches/{id}` | Get branch with locations |
| `PATCH` | `/branches/{id}` | Update branch |
| `DELETE` | `/branches/{id}` | Delete branch |

**Filters:** `business_id`, `status`

### POST /branches

| Field | Type | Required | Rules |
|---|---|---|---|
| `business_id` | int | ✅ | exists, user must have access |
| `name` | string | ✅ | max 255 |
| `code` | string | ✅ | unique, max 50 |
| `description` | string | ✗ | max 1000 |
| `manager_id` | int | ✗ | exists in users |
| `status` | string | ✗ | active \| inactive |

**Response 201:**
```json
{
  "success": true,
  "message": "Branch created.",
  "data": {
    "id": 3,
    "business_id": 1,
    "name": "Main Factory Branch",
    "code": "FAC-MAIN",
    "status": "active",
    "business": { "id": 1, "name": "Al-Baraka Oil Factory" }
  }
}
```

---

## 12. Organisation — Warehouses

| Method | URI | Description |
|---|---|---|
| `GET` | `/warehouses` | List warehouses |
| `POST` | `/warehouses` | Create warehouse |
| `GET` | `/warehouses/{id}` | Get warehouse |
| `PATCH` | `/warehouses/{id}` | Update warehouse |
| `DELETE` | `/warehouses/{id}` | Delete warehouse |

**Filters:** `business_id`, `branch_id`, `status`

### POST /warehouses

| Field | Type | Required | Rules |
|---|---|---|---|
| `business_id` | int | ✅ | verified against user |
| `branch_id` | int | ✅ | must belong to `business_id` |
| `name` | string | ✅ | max 255 |
| `code` | string | ✗ | unique, max 50 |
| `status` | string | ✗ | active \| inactive |

---

## 13. Organisation — Locations

Physical locations (bins, shelves, warehouses) within branches.

| Method | URI | Description |
|---|---|---|
| `GET` | `/locations` | List locations |
| `POST` | `/locations` | Create location |
| `GET` | `/locations/{id}` | Get location |
| `PATCH` | `/locations/{id}` | Update location |
| `DELETE` | `/locations/{id}` | Delete location |

| Field | Type | Required |
|---|---|---|
| `branch_id` | int | ✅ |
| `name` | string | ✅ |
| `code` | string | ✗ |
| `type` | string | ✗ — warehouse \| shelf \| bin |
| `status` | string | ✗ — active \| inactive |

---

## 14. Users & Access

| Method | URI | Description |
|---|---|---|
| `GET` | `/users` | List staff users (scoped) |
| `POST` | `/users` | Create staff user |
| `GET` | `/users/{id}` | Get user |
| `PATCH` | `/users/{id}` | Update user |
| `DELETE` | `/users/{id}` | Delete user |

### POST /users

| Field | Type | Required | Rules |
|---|---|---|---|
| `name` | string | ✅ | max 255 |
| `email` | string | ✅ | unique, valid email |
| `password` | string | ✅ | min 8 chars |
| `status` | string | ✗ | active \| inactive |
| `role_id` | int | ✗ | exists in roles |
| `business_id` | int | ✗ | for role scoping |
| `branch_id` | int | ✗ | for role scoping |

**The `UserResource` never exposes `password`, `remember_token`, or API tokens.**

---

## 15. Roles & Permissions

Read-only. Role assignment is managed through the admin panel.

| Method | URI | Description |
|---|---|---|
| `GET` | `/roles` | List all roles |
| `GET` | `/roles/{id}` | Get role with permissions |
| `GET` | `/permissions` | List all permissions |

---

---

## 16. Product Catalog — Categories

| Method | URI | Description |
|---|---|---|
| `GET` | `/categories` | List categories |
| `POST` | `/categories` | Create category |
| `GET` | `/categories/{id}` | Get category |
| `PATCH` | `/categories/{id}` | Update category |
| `DELETE` | `/categories/{id}` | Delete category |

**Filters:** `business_id`, `type`, `is_active`, `parent_id`

### POST /categories

| Field | Type | Required | Rules |
|---|---|---|---|
| `business_id` | int | ✅ | verified server-side |
| `name` | string | ✅ | max 255 |
| `type` | string | ✗ | product \| book \| raw_material \| packing \| general |
| `parent_id` | int | ✗ | exists in categories |
| `is_active` | bool | ✗ | default true |

**Category types for raw materials:** Use `type=raw_material` and name the category `Selit`, `Tukur Azmud`, `Telba`, or any custom name — new categories require no code changes.

---

## 17. Product Catalog — Products

| Method | URI | Description |
|---|---|---|
| `GET` | `/products` | List products |
| `POST` | `/products` | Create product |
| `GET` | `/products/{id}` | Get product with attributes, images |
| `PATCH` | `/products/{id}` | Update product |
| `DELETE` | `/products/{id}` | Soft-delete product |

**Filters:** `type`, `category_id`, `status`, `search`, `business_id`, `is_marketplace_visible`, `is_featured`

### Product types

| Type | Description |
|---|---|
| `finished_oil` | Packaged oil products for sale |
| `raw_material` | Sesame, flaxseed, etc. (purchased by kg) |
| `packing_material` | Bottles, caps, labels, cartons |
| `book` | Bookstore products |
| `bundle` | Product collection |
| `general` | Other |

### POST /products

| Field | Type | Required | Rules |
|---|---|---|---|
| `business_id` | int | ✅ | verified server-side |
| `category_id` | int | ✅ | exists |
| `name` | string | ✅ | max 255 |
| `type` | string | ✅ | see types above |
| `sku` | string | ✗ | unique |
| `description` | string | ✗ | |
| `selling_price` | decimal | ✅ | min 0 |
| `cost_price` | decimal | ✅ | min 0 |
| `unit` | string | ✗ | kg, litre, ml, piece, etc. |
| `package_size` | string | ✗ | e.g. "500ml", "1L", "5L" |
| `barcode` | string | ✗ | |
| `status` | string | ✗ | active \| inactive \| draft |
| `is_featured` | bool | ✗ | default false |
| `is_marketplace_visible` | bool | ✗ | default false |

**Response 201:**
```json
{
  "success": true,
  "message": "Created",
  "data": {
    "id": 12,
    "business_id": 1,
    "category_id": 3,
    "name": "Al-Baraka Premium Oil 1L",
    "type": "finished_oil",
    "type_label": "Finished Oil",
    "sku": "OIL-1L-001",
    "selling_price": 45.00,
    "cost_price": 28.50,
    "unit": "litre",
    "package_size": "1L",
    "status": "active",
    "status_label": "Active",
    "is_featured": true,
    "is_marketplace_visible": true,
    "is_sellable": true,
    "category": { "id": 3, "name": "Lubricating Oils" }
  }
}
```

---

## 18. Product Attributes & Images

### Attributes

| Method | URI | Description |
|---|---|---|
| `GET` | `/products/{id}/attributes` | List product attributes |
| `POST` | `/products/{id}/attributes` | Add attribute |
| `DELETE` | `/products/{id}/attributes/{attr_id}` | Remove attribute |

**POST body:** `{ "attribute_id": 5, "value": "Author Name" }`

### Images

| Method | URI | Description |
|---|---|---|
| `GET` | `/products/{id}/images` | List product images |
| `POST` | `/products/{id}/images` | Upload product image |
| `DELETE` | `/products/{id}/images/{image_id}` | Delete image |

**POST body (multipart/form-data):** `file` (image), `is_primary` (bool), `sort_order` (int)

---

## 19. Product Collections (Bundles)

| Method | URI | Description |
|---|---|---|
| `GET` | `/product-collections` | List collections |
| `POST` | `/product-collections` | Create collection |
| `GET` | `/product-collections/{id}` | Get collection with products |
| `PATCH` | `/product-collections/{id}` | Update collection |
| `DELETE` | `/product-collections/{id}` | Delete collection |

**POST body:** `{ "name": "Starter Oil Pack", "price": 120.00, "product_ids": [1, 2, 3] }`

Bundle stock validation checks every included product before confirming sale.

---

## 20. Inventory — Stock Levels

Current balance per product per location. **Read-only** — balances are managed exclusively through stock movements.

| Method | URI | Description |
|---|---|---|
| `GET` | `/stock-levels` | List all stock levels |
| `GET` | `/stock-levels/valuation` | Inventory value summary |
| `POST` | `/stock-levels/reconcile` | Trigger balance reconciliation |
| `GET` | `/stock-levels/{id}` | Get single stock level |

**Filters:** `business_id`, `branch_id`, `location_id`, `item_type`, `item_id`

**Response data shape per stock level:**
```json
{
  "id": 5,
  "business_id": 1,
  "branch_id": 2,
  "location_id": 3,
  "item_type": "product",
  "item_id": 12,
  "quantity_on_hand": 250.00,
  "quantity_reserved": 10.00,
  "available_quantity": 240.00,
  "unit_cost": 28.50,
  "stock_value": 7125.00,
  "reorder_level": 50.00,
  "is_low_stock": false,
  "is_out_of_stock": false
}
```

### GET /stock-levels/valuation

Returns total inventory value grouped by business.

```json
{
  "success": true,
  "data": {
    "total_value": 450000.00,
    "by_business": [
      { "business_id": 1, "name": "Al-Baraka Oil Factory", "value": 320000.00 },
      { "business_id": 2, "name": "Al-Baraka Bookstore",   "value": 130000.00 }
    ],
    "low_stock_count": 5,
    "out_of_stock_count": 2
  }
}
```

---

## 21. Inventory — Stock Movements

Every stock change generates an immutable movement record.

| Method | URI | Description |
|---|---|---|
| `GET` | `/stock-movements` | List movements |
| `GET` | `/stock-movements/{id}` | Get movement |
| `POST` | `/stock-movements/opening` | Set opening stock |
| `POST` | `/stock-movements/adjustment` | Manual adjustment |
| `POST` | `/stock-movements/transfer` | Transfer between locations |

**Filters:** `business_id`, `branch_id`, `location_id`, `item_id`, `movement_type`, `direction`, `from`, `to`

### Movement types

| Type | Direction | Trigger |
|---|---|---|
| `purchase` | in | Raw material / product receipt |
| `production` | in | Finished goods from batch |
| `production_consumption` | out | Raw materials consumed by batch |
| `sale` | out | Customer order |
| `return` | in | Customer return |
| `transfer_in` | in | Incoming transfer |
| `transfer_out` | out | Outgoing transfer |
| `adjustment_in` | in | Manual increase |
| `adjustment_out` | out | Manual decrease |
| `distribution` | out | Factory distribution |
| `packing_consumption` | out | Packing materials used |

### POST /stock-movements/opening

| Field | Type | Required |
|---|---|---|
| `location_id` | int | ✅ |
| `item_type` | string | ✅ — `product` |
| `item_id` | int | ✅ |
| `quantity` | decimal | ✅ — min 0.0001 |
| `unit_cost` | decimal | ✗ |

### POST /stock-movements/adjustment

| Field | Type | Required | Rules |
|---|---|---|---|
| `location_id` | int | ✅ | |
| `item_type` | string | ✅ | `product` |
| `item_id` | int | ✅ | |
| `direction` | string | ✅ | `in` \| `out` |
| `quantity` | decimal | ✅ | min 0.0001 |
| `reason` | string | ✅ | max 500 — required for audit |
| `notes` | string | ✗ | |

### POST /stock-movements/transfer

| Field | Type | Required | Rules |
|---|---|---|---|
| `from_location_id` | int | ✅ | must differ from `to_location_id` |
| `to_location_id` | int | ✅ | |
| `item_type` | string | ✅ | `product` |
| `item_id` | int | ✅ | |
| `quantity` | decimal | ✅ | min 0.0001 |
| `notes` | string | ✗ | |

Both sides (`transfer_in` and `transfer_out`) are written atomically. If source has insufficient stock, returns `422`.

---

---

## 22. Inventory — Thresholds

Configure low-stock alert levels per product per location.

| Method | URI | Description |
|---|---|---|
| `GET` | `/inventory-thresholds` | List thresholds |
| `POST` | `/inventory-thresholds` | Create threshold |
| `GET` | `/inventory-thresholds/{id}` | Get threshold |
| `PATCH` | `/inventory-thresholds/{id}` | Update threshold |
| `DELETE` | `/inventory-thresholds/{id}` | Delete threshold |

**POST body:**
```json
{
  "location_id": 3,
  "item_type": "product",
  "item_id": 12,
  "reorder_point": 50.0,
  "min_stock": 20.0,
  "max_stock": 500.0
}
```

---

## 23. CRM — Customers

| Method | URI | Description |
|---|---|---|
| `GET` | `/customers` | List customers (scoped by business orders) |
| `POST` | `/customers` | Create customer |
| `GET` | `/customers/{id}` | Get customer |
| `PATCH` | `/customers/{id}` | Update customer |
| `DELETE` | `/customers/{id}` | Delete customer (Super Admin) |

**Filters:** `search`, `status`

### POST /customers

| Field | Type | Required | Rules |
|---|---|---|---|
| `name` | string | ✅ | max 255 |
| `email` | string | ✗ | unique, valid email |
| `phone` | string | ✗ | max 30 |
| `status` | string | ✗ | active \| inactive |

**CustomerResource never exposes `password` or `remember_token`.**

**Response 201:**
```json
{
  "success": true,
  "message": "Customer created.",
  "data": {
    "id": 15,
    "name": "Ahmed Mohammed",
    "email": "ahmed@example.com",
    "phone": "+251911234567",
    "status": "active",
    "created_at": "2026-08-15T10:30:00.000000Z"
  }
}
```

---

## 24. CRM — Addresses

| Method | URI | Description |
|---|---|---|
| `GET` | `/addresses` | List addresses |
| `POST` | `/addresses` | Create address |
| `GET` | `/addresses/{id}` | Get address |
| `PATCH` | `/addresses/{id}` | Update address |
| `DELETE` | `/addresses/{id}` | Delete address |

**POST body:**
```json
{
  "customer_id": 15,
  "address_type": "delivery",
  "city": "Addis Ababa",
  "sub_city": "Bole",
  "street": "Bole Road, Building 5",
  "is_default": true
}
```

---

## 25. Sales — Orders

Orders can be placed via API (checkout) or updated (status changes). Internal order splitting by business/branch is transparent to the customer.

| Method | URI | Description |
|---|---|---|
| `GET` | `/orders` | List orders (business-scoped) |
| `POST` | `/orders` | Place order from cart |
| `GET` | `/orders/{id}` | Get order with full detail |
| `PATCH` | `/orders/{id}` | Update order status/notes |
| `DELETE` | `/orders/{id}` | Cancel order |

**Filters:** `business_id`, `status`, `customer_id`, `from`, `to`

### Order statuses

| Status | Description |
|---|---|
| `pending` | Just placed, awaiting confirmation |
| `confirmed` | Confirmed by staff |
| `processing` | Being prepared / picked |
| `completed` | Delivered and paid |
| `cancelled` | Cancelled |

### POST /orders — Place order

```json
{
  "customer_id": 15,
  "delivery_address_id": 8,
  "discount_code": "SUMMER10",
  "notes": "Please call before delivery"
}
```

The customer's active cart is checked out. Stock is validated atomically. If any item is out of stock the entire checkout fails with `422`.

**Response 201:**
```json
{
  "success": true,
  "message": "Order placed.",
  "data": {
    "id": 42,
    "order_number": "ORD-260815-X7R2K1",
    "status": "confirmed",
    "status_label": "Confirmed",
    "subtotal": 250.00,
    "discount_amount": 25.00,
    "delivery_fee": 0.00,
    "total_amount": 225.00,
    "currency": "USD",
    "placed_at": "2026-08-15T11:00:00.000000Z",
    "items": [
      {
        "id": 101,
        "product_id": 12,
        "quantity": 5.0,
        "unit_price": 45.00,
        "discount_amount": 5.00,
        "total_amount": 200.00,
        "product": { "id": 12, "name": "Al-Baraka Premium Oil 1L" }
      }
    ],
    "invoice": {
      "id": 38,
      "invoice_number": "INV-20260815-ABC123",
      "status": "issued",
      "total_amount": 225.00,
      "balance_due": 225.00
    }
  }
}
```

### PATCH /orders/{id}

Only `status` and `notes` can be updated via API. To cancel, send `{ "status": "cancelled" }` or use `DELETE`.

---

## 26. Sales — Invoices

Invoices are generated automatically on checkout. They cannot be created manually via API.

| Method | URI | Description |
|---|---|---|
| `GET` | `/invoices` | List invoices |
| `GET` | `/invoices/{id}` | Get invoice with payments |
| `PATCH` | `/invoices/{id}` | Update notes or status |
| `DELETE` | `/invoices/{id}` | Delete unpaid invoice |

**Filters:** `order_id`, `customer_id`, `status`, `from`, `to`

### Invoice statuses

`pending` → `issued` → `paid` / `cancelled`

**Response shape:**
```json
{
  "id": 38,
  "invoice_number": "INV-20260815-ABC123",
  "order_id": 42,
  "customer_id": 15,
  "subtotal": 250.00,
  "discount_amount": 25.00,
  "tax_amount": 0.00,
  "total_amount": 225.00,
  "status": "issued",
  "balance_due": 225.00,
  "total_paid": 0.00,
  "issued_at": "2026-08-15T11:00:00.000000Z",
  "payments": []
}
```

---

## 27. Sales — Payments

Payments are recorded manually by staff. No payment gateway integration.

| Method | URI | Description |
|---|---|---|
| `GET` | `/payments` | List payments |
| `POST` | `/payments` | Record manual payment |
| `GET` | `/payments/{id}` | Get payment |
| `PATCH` | `/payments/{id}` | Update notes / reference |
| `DELETE` | `/payments/{id}` | Delete payment |

**Filters:** `invoice_id`, `order_id`, `from`, `to`

### POST /payments

| Field | Type | Required | Rules |
|---|---|---|---|
| `invoice_id` | int | ✅ | exists |
| `amount` | decimal | ✅ | min 0.01, cannot exceed balance due |
| `payment_method` | string | ✅ | cash \| bank_transfer \| cheque \| other |
| `payment_date` | date | ✅ | Y-m-d |
| `notes` | string | ✗ | max 1000 |
| `transaction_reference` | string | ✗ | bank/cheque ref |

When payment equals or exceeds the balance due, the invoice is marked `paid` and the order is marked `completed` automatically.

---

## 28. Sales — Deliveries

Deliveries are tracked manually by staff. No GPS or routing.

| Method | URI | Description |
|---|---|---|
| `GET` | `/deliveries` | List deliveries |
| `POST` | `/deliveries` | Create delivery record |
| `GET` | `/deliveries/{id}` | Get delivery |
| `PATCH` | `/deliveries/{id}` | Update status |
| `DELETE` | `/deliveries/{id}` | Delete delivery record |

**Delivery statuses:** `pending` → `in_transit` → `delivered` / `failed`

### POST /deliveries

| Field | Type | Required |
|---|---|---|
| `order_id` | int | ✅ |
| `address_id` | int | ✅ |
| `fulfilling_branch_id` | int | ✅ |
| `delivery_date` | date | ✅ |
| `notes` | string | ✗ |

Setting `status = delivered` via PATCH automatically marks the order as `completed`.

---

## 29. Sales — Branch POS

Direct point-of-sale transactions at physical branches, independent of the online cart.

| Method | URI | Description |
|---|---|---|
| `GET` | `/branch-sales` | List POS sales |
| `POST` | `/branch-sales` | Record POS sale |
| `GET` | `/branch-sales/{id}` | Get sale |
| `DELETE` | `/branch-sales/{id}` | Void sale |

**Filters:** `branch_id`, `product_id`, `from`, `to`

### POST /branch-sales

| Field | Type | Required |
|---|---|---|
| `branch_id` | int | ✅ — user must have access |
| `location_id` | int | ✅ — warehouse within branch |
| `product_id` | int | ✅ |
| `quantity` | decimal | ✅ |
| `unit_price` | decimal | ✅ |
| `cost_price` | decimal | ✅ — required for profit calculation |
| `sale_date` | date | ✅ |
| `production_batch_id` | int | ✗ — for batch traceability |

`total_amount` and `gross_profit` are computed server-side.

---

## 30. Sales — Discounts & Promotions

| Method | URI | Description |
|---|---|---|
| `GET` | `/discounts` | List discounts |
| `POST` | `/discounts` | Create discount |
| `GET` | `/discounts/{id}` | Get discount |
| `PATCH` | `/discounts/{id}` | Update discount |
| `DELETE` | `/discounts/{id}` | Delete discount |
| `GET` | `/promotions` | List promotions |
| `POST` | `/promotions` | Create promotion |
| `GET` | `/promotions/{id}` | Get promotion |
| `PATCH` | `/promotions/{id}` | Update promotion |
| `DELETE` | `/promotions/{id}` | Delete promotion |

Discount types: `percentage` or `fixed`. Applied at product, category, or order level. Discount calculations never corrupt cost/profit data — `original_price`, `discount_amount`, and `final_price` are always stored separately.

---

---

## 31. Manufacturing — Orders

Manufacturing orders define what to produce. They initiate production batches.

| Method | URI | Description |
|---|---|---|
| `GET` | `/manufacturing-orders` | List manufacturing orders |
| `POST` | `/manufacturing-orders` | Create manufacturing order |
| `GET` | `/manufacturing-orders/{id}` | Get order with batches |
| `PATCH` | `/manufacturing-orders/{id}` | Update order |
| `DELETE` | `/manufacturing-orders/{id}` | Delete order |

**Statuses:** `pending` → `in_progress` → `completed` / `cancelled`

### POST /manufacturing-orders

| Field | Type | Required |
|---|---|---|
| `business_id` | int | ✅ |
| `branch_id` | int | ✅ — factory branch |
| `product_id` | int | ✅ — finished oil product |
| `planned_quantity` | decimal | ✅ |
| `package_size` | string | ✗ — e.g. "1L", "500ml" |
| `planned_start_date` | date | ✗ |
| `planned_end_date` | date | ✗ |
| `notes` | string | ✗ |

---

## 32. Manufacturing — Production Batches

| Method | URI | Description |
|---|---|---|
| `GET` | `/production-batches` | List batches |
| `POST` | `/production-batches` | Open a new batch |
| `GET` | `/production-batches/{id}` | Get batch with full detail |
| `PATCH` | `/production-batches/{id}` | Update batch |
| `DELETE` | `/production-batches/{id}` | Delete batch (open only) |
| `GET` | `/production-batches/yield-report` | Yield summary across batches |
| `POST` | `/production-batches/compare` | Side-by-side batch comparison |

**Filters:** `business_id`, `branch_id`, `status`, `from`, `to`

### Batch statuses

`open` → `in_qc` → `approved` / `rejected` → `packed` → `completed`

### POST /production-batches

| Field | Type | Required |
|---|---|---|
| `manufacturing_order_id` | int | ✅ |
| `planned_quantity` | decimal | ✅ |
| `notes` | string | ✗ |

`batch_number` is auto-generated in format `BATCH-YYYYMMDD-{sequence}`.

**Response 201:**
```json
{
  "id": 7,
  "batch_number": "BATCH-20260815-0007",
  "status": "open",
  "planned_quantity": 1000.00,
  "produced_quantity": 0.00,
  "oil_quantity_produced": 0.00,
  "oil_unit": "ml",
  "raw_material_cost": 0.00,
  "packing_cost": 0.00,
  "revenue": 0.00,
  "profit": 0.00
}
```

### POST /production-batches/compare

Compare two or more batches side-by-side.

```json
{ "batch_ids": [5, 7, 9] }
```

Returns cost, revenue, profit, yield, and sell-through for each batch.

---

## 33. Manufacturing — Batch Actions

All actions use `POST`. Base path: `/production-batches/{id}/`

### consume-raw-material

Record raw material consumption for this batch.

```json
{
  "raw_material_id": 3,
  "quantity_consumed": 150.0,
  "unit_cost": 45.00,
  "lot_number": "LOT-2026-001",
  "warehouse_id": 2
}
```

Creates a `production_consumption` stock movement (OUT). Fails if insufficient raw material stock.

---

### record-oil-output

Record the oil quantity produced.

```json
{
  "oil_quantity_ml": 85000,
  "notes": "Slightly below expected yield"
}
```

---

### quality-check

Submit QC inspection. A batch **cannot** enter finished goods until this returns `passed`.

```json
{
  "status": "passed",
  "quantity_checked": 1000,
  "quantity_passed": 998,
  "quantity_failed": 2,
  "remarks": "2 bottles with minor label defect, accepted",
  "inspection_date": "2026-08-15",
  "rejection_reason": null
}
```

- `status = passed` → batch moves to `approved`, proceeds to packing
- `status = failed` → batch moves to `rejected`, blocked from further steps

---

### complete

Finalize a batch after packing. Deducts packing materials, creates finished goods stock, marks batch `completed`.

```json
{
  "packing_items": [
    { "product_id": 8, "quantity": 1000, "unit_cost": 1.20 },
    { "product_id": 9, "quantity": 1000, "unit_cost": 0.50 }
  ]
}
```

Applies the **fixed profit formula:**
```
Profit = Oil Produced × Selling Price
       − Raw Material Used × Cost/kg
       − Packing Material Cost
```

---

### distribute

Distribute finished goods to a sales point or bazaar.

```json
{
  "finished_good_id": 12,
  "source_location_id": 3,
  "destination_branch_id": 4,
  "destination_location_id": 7,
  "quantity": 200.0,
  "notes": "Bazaar delivery - August batch"
}
```

---

### return

Record returned finished goods.

```json
{
  "product_id": 12,
  "quantity": 15.0,
  "reason": "Customer rejected — packaging damaged"
}
```

Creates a `return` stock movement (IN). Linked to the originating batch for traceability.

---

### reconcile

Reconcile batch quantities. Flags mismatches between finished, distributed, sold, and returned quantities.

```json
{ "notes": "End of month reconciliation" }
```

---

### GET traceability

Returns the complete lifecycle of a batch.

```json
{
  "batch_number": "BATCH-20260815-0007",
  "product": "Al-Baraka Premium Oil 1L",
  "production_date": "2026-08-15",
  "raw_materials_used": [
    { "name": "Sesame (Selit)", "qty_kg": 150.0, "unit_cost": 45.00, "total_cost": 6750.00 }
  ],
  "oil_quantity_produced_ml": 85000,
  "oil_quantity_liters": 85.0,
  "yield_percentage": 56.67,
  "packing_materials": [...],
  "packing_cost": 1700.00,
  "qc_status": "passed",
  "finished_quantity": 1000,
  "distributed_quantity": 800,
  "sold_quantity": 650,
  "returned_quantity": 12,
  "remaining_quantity": 150,
  "revenue": 45000.00,
  "profit": 17300.00
}
```

---

## 34. Manufacturing — Quality Control

| Method | URI | Description |
|---|---|---|
| `GET` | `/quality-checks` | List QC records |
| `POST` | `/quality-checks` | Submit QC (same as batch action) |
| `GET` | `/quality-checks/{id}` | Get QC record |
| `PATCH` | `/quality-checks/{id}` | Update remarks |

**Filters:** `business_id`, `batch_id`, `status` (pending|passed|failed)

---

## 35. Manufacturing — Finished Goods

Read-only. Finished goods are created by the manufacturing workflow after QC approval.

| Method | URI | Description |
|---|---|---|
| `GET` | `/finished-goods` | List finished goods |
| `GET` | `/finished-goods/{id}` | Get finished good |

**Filters:** `business_id`, `batch_id`, `product_id`, `status`

**Statuses:** `available` | `distributed` | `reserved`

---

## 36. Manufacturing — Distributions

| Method | URI | Description |
|---|---|---|
| `GET` | `/distributions` | List distributions |
| `POST` | `/distributions` | Record distribution |
| `GET` | `/distributions/{id}` | Get distribution |
| `PATCH` | `/distributions/{id}` | Update status |
| `DELETE` | `/distributions/{id}` | Delete (pending only) |

**Filters:** `business_id`, `batch_id`, `status`

**Distribution statuses:** `pending` → `in_transit` → `completed`

Setting `status = completed` sets `received_at` and updates the finished good's available quantity.

---

## 37. Manufacturing — Batch Returns

| Method | URI | Description |
|---|---|---|
| `GET` | `/batch-returns` | List returns |
| `POST` | `/batch-returns` | Record return |
| `GET` | `/batch-returns/{id}` | Get return |

**POST body:**
```json
{
  "production_batch_id": 7,
  "product_id": 12,
  "quantity": 15.0,
  "reason": "Customer rejected — damaged packaging"
}
```

---

## 38. Manufacturing — Reconciliations

Read-only. Triggered via batch action `/production-batches/{id}/reconcile`.

| Method | URI | Description |
|---|---|---|
| `GET` | `/batch-reconciliations` | List reconciliations |
| `GET` | `/batch-reconciliations/{id}` | Get reconciliation |

**Statuses:** `clean` (no variance) | `mismatch` (variance detected)

---

## 39. Manufacturing — Consumption Records

Read-only. Records created by `consume-raw-material` batch action.

| Method | URI | Description |
|---|---|---|
| `GET` | `/batch-consumption` | List consumption records |
| `GET` | `/batch-consumption/{id}` | Get record |

---

## 40. Financials — Profit Ledger

Read-only. Entries are written automatically when orders are completed.

| Method | URI | Description |
|---|---|---|
| `GET` | `/profit-ledger` | List profit entries |
| `GET` | `/profit-ledger/{id}` | Get profit entry |

**Filters:** `business_id`, `branch_id`, `from`, `to`

**Entry shape:**
```json
{
  "id": 55,
  "order_item_id": 101,
  "business_id": 1,
  "branch_id": 2,
  "selling_price": 45.00,
  "cost_price": 28.50,
  "quantity": 5.0,
  "revenue": 225.00,
  "total_cost": 142.50,
  "profit": 82.50,
  "recorded_at": "2026-08-15T11:00:00.000000Z"
}
```

---

---

## 41. CMS — Articles

| Method | URI | Description |
|---|---|---|
| `GET` | `/articles` | List articles (published only for non-admins) |
| `POST` | `/articles` | Create article |
| `GET` | `/articles/{id}` | Get article by ID |
| `GET` | `/articles/slug/{slug}` | Get article by slug (increments view count) |
| `PATCH` | `/articles/{id}` | Update article |
| `DELETE` | `/articles/{id}` | Soft-delete article |

**Filters:** `status`, `article_type`, `category_id`, `search`

### Article statuses: `draft` | `published` | `archived`

### Article types: `article` | `guide`

Non-admin users only see `status = published` articles.

### POST /articles

| Field | Type | Required | Notes |
|---|---|---|---|
| `title` | string | ✅ | max 255 |
| `body` | string | ✅ | HTML — dangerous tags stripped server-side |
| `excerpt` | string | ✗ | max 500 |
| `category_id` | int | ✗ | |
| `status` | string | ✅ | draft \| published \| archived |
| `article_type` | string | ✗ | article \| guide |
| `published_at` | datetime | ✗ | ISO 8601 |
| `slug` | string | ✗ | auto-generated if omitted |

**HTML sanitization** — the following tags are preserved: `<p> <br> <b> <i> <strong> <em> <ul> <ol> <li> <h2> <h3> <h4> <a> <img> <table> <thead> <tbody> <tr> <th> <td> <blockquote>`

All other tags including `<script>`, `<iframe>`, `<form>`, `<object>` are stripped.

---

## 42. CMS — FAQs

| Method | URI | Description |
|---|---|---|
| `GET` | `/faqs` | List FAQs |
| `POST` | `/faqs` | Create FAQ |
| `GET` | `/faqs/{id}` | Get FAQ |
| `PATCH` | `/faqs/{id}` | Update FAQ |
| `DELETE` | `/faqs/{id}` | Delete FAQ |

**Filters:** `category`, `is_active`, `search`

### POST /faqs

| Field | Type | Required |
|---|---|---|
| `question` | string | ✅ — max 500 |
| `answer` | string | ✅ |
| `category` | string | ✗ — e.g. "Shipping", "Products" |
| `is_active` | bool | ✗ — default true |
| `sort_order` | int | ✗ — default 0 |

---

## 43. CMS — Media Files

| Method | URI | Description |
|---|---|---|
| `GET` | `/media-files` | List media files |
| `POST` | `/media-files` | Upload file |
| `GET` | `/media-files/{id}` | Get file metadata |
| `DELETE` | `/media-files/{id}` | Delete file |

Files are stored in `storage/app/` and served through an authorized controller — no direct public URLs.

### POST /media-files (multipart/form-data)

| Field | Type | Required | Rules |
|---|---|---|---|
| `file` | file | ✅ | max 10MB, allowed: jpg, png, gif, webp, pdf, docx |
| `alt_text` | string | ✗ | max 255 |
| `title` | string | ✗ | max 255 |

**Response 201:**
```json
{
  "id": 23,
  "file_name": "product-banner.jpg",
  "file_path": "media/2026/08/product-banner.jpg",
  "file_type": "image",
  "mime_type": "image/jpeg",
  "file_size": 245760,
  "human_size": "240 KB",
  "alt_text": "Al-Baraka oil bottle banner",
  "is_image": true
}
```

---

## 44. CMS — Tags

| Method | URI | Description |
|---|---|---|
| `GET` | `/tags` | List tags |
| `POST` | `/tags` | Create tag |
| `GET` | `/tags/{id}` | Get tag |
| `PATCH` | `/tags/{id}` | Update tag |
| `DELETE` | `/tags/{id}` | Delete tag |

**POST body:** `{ "name": "Featured", "slug": "featured" }`

---

## 45. CMS — Languages

| Method | URI | Description |
|---|---|---|
| `GET` | `/languages` | List supported languages |
| `POST` | `/languages` | Add language (Super Admin) |
| `GET` | `/languages/{id}` | Get language |
| `PATCH` | `/languages/{id}` | Update language |

### Supported languages

| Code | Name | Direction |
|---|---|---|
| `en` | English | LTR |
| `ar` | Arabic | RTL (sets `dir="rtl"` on HTML element) |
| `am` | Amharic | LTR |
| `om` | Afan Oromo | LTR |

Disabling a language hides it from the UI but does not delete translations.

---

## 46. CMS — Translations

Database-driven translations for content items (products, articles, FAQs, categories, pages).

| Method | URI | Description |
|---|---|---|
| `GET` | `/translations` | List translations |
| `POST` | `/translations` | Upsert a translation |
| `DELETE` | `/translations/{id}` | Delete translation |

### POST /translations — Upsert

```json
{
  "translatable_type": "App\\Models\\Product",
  "translatable_id": 12,
  "locale": "ar",
  "field": "name",
  "value": "زيت البركة الممتاز 1 لتر"
}
```

The unique constraint on `(translatable_type, translatable_id, locale, field)` ensures upsert semantics.

**Fallback chain:**
1. Requested locale (`ar`)
2. English (`en`)
3. Raw database column value

---

## 47. Reports

All report endpoints require `auth:sanctum`. Rate limit: **30 requests / minute**.

Filters apply to all endpoints: `business_id`, `branch_id`, `from` (Y-m-d), `to` (Y-m-d), `category_id`, `product_id`, `batch_id`.

| Method | URI | Description |
|---|---|---|
| `GET` | `/reports/kpis` | Executive dashboard KPIs |
| `GET` | `/reports/sales` | Sales report |
| `GET` | `/reports/profit` | Profit report |
| `GET` | `/reports/inventory` | Inventory report |
| `GET` | `/reports/manufacturing` | Manufacturing report |
| `GET` | `/reports/purchasing` | Purchasing report |
| `GET` | `/reports/branches` | Branch performance |
| `GET` | `/reports/export/{type}` | Download CSV |

### GET /reports/kpis

```json
{
  "total_revenue": 125000.00,
  "total_orders": 342,
  "pending_orders": 12,
  "total_profit": 38500.00,
  "profit_margin_pct": 30.8,
  "inventory_value": 450000.00,
  "low_stock_count": 5,
  "out_of_stock_count": 2,
  "manufacturing_profit": 22000.00,
  "oil_output_liters": 1850.00,
  "active_batches": 3,
  "total_purchase_cost": 65000.00
}
```

### GET /reports/sales

```json
{
  "summary": {
    "revenue": 125000.00, "order_count": 342, "qty_sold": 8750, "avg_order_value": 365.50
  },
  "top_products": [ { "id": 12, "name": "...", "revenue": 45000.00, "qty_sold": 1000 } ],
  "by_business":  [ { "id": 1, "name": "...", "revenue": 95000.00, "order_count": 285 } ],
  "by_branch":    [ { "id": 2, "name": "...", "revenue": 55000.00, "qty_sold": 1200 } ],
  "trend":        [ { "day": "2026-08-01", "revenue": 4200.00, "orders": 12 } ]
}
```

### GET /reports/profit

```json
{
  "summary": { "revenue": 125000.00, "cost": 86500.00, "profit": 38500.00 },
  "by_product":    [...],
  "by_category":   [...],
  "by_business":   [...],
  "trend":         [...],
  "batch_profits": [
    {
      "batch_number": "BATCH-20260815-0007",
      "oil_quantity_produced": 85000,
      "raw_material_cost": 6750.00,
      "packing_cost": 1700.00,
      "revenue": 38250.00,
      "profit": 29800.00
    }
  ]
}
```

### GET /reports/manufacturing

```json
{
  "summary": {
    "total_batches": 12, "completed_batches": 10,
    "total_oil_liters": 850.0, "total_raw_cost": 67500.00,
    "total_packing_cost": 17000.00, "total_revenue": 382500.00, "total_profit": 298000.00
  },
  "consumption_by_category": [
    { "category_name": "Selit",       "total_kg": 600.0, "total_cost": 27000.00, "avg_cost_per_kg": 45.00 },
    { "category_name": "Tukur Azmud", "total_kg": 300.0, "total_cost": 22500.00, "avg_cost_per_kg": 75.00 },
    { "category_name": "Telba",       "total_kg": 100.0, "total_cost": 8000.00,  "avg_cost_per_kg": 80.00 }
  ],
  "batches": [...]
}
```

### GET /reports/export/{type}

Downloads a UTF-8 CSV file. Valid types: `sales` | `profit` | `inventory` | `manufacturing` | `purchasing` | `branches`

```
Content-Type: text/csv; charset=UTF-8
Content-Disposition: attachment; filename="sales_report_2026-08-15.csv"
```

---

## 48. System — Settings

| Method | URI | Description |
|---|---|---|
| `GET` | `/settings` | List all settings |
| `PATCH` | `/settings/{key}` | Update a setting value |

---

## 49. System — Notifications

| Method | URI | Description |
|---|---|---|
| `GET` | `/notifications` | List user notifications |
| `GET` | `/notifications/{id}` | Get notification |
| `DELETE` | `/notifications/{id}` | Mark as read / delete |

---

## 50. System — Audit Logs

Read-only. Every important action is automatically logged.

| Method | URI | Description |
|---|---|---|
| `GET` | `/audit-logs` | List audit entries |
| `GET` | `/audit-logs/{id}` | Get audit entry |

**Filters:** `business_id`, `user_id`, `action`, `from`, `to`

**Actions logged:** login, logout, created, updated, deleted, approved, rejected, order_placed, qc_submitted, payment_recorded, delivery_completed, stock_adjustment, transfer

---

## 51. Approval Workflows

| Method | URI | Description |
|---|---|---|
| `GET` | `/approval-workflows` | List workflow definitions |
| `POST` | `/approval-workflows` | Create workflow |
| `GET` | `/approval-workflows/{id}` | Get workflow |
| `PATCH` | `/approval-workflows/{id}` | Update workflow |
| `DELETE` | `/approval-workflows/{id}` | Delete workflow |
| `GET` | `/approval-requests` | List approval requests |
| `POST` | `/approval-requests` | Submit for approval |
| `GET` | `/approval-requests/{id}` | Get request |
| `PATCH` | `/approval-requests/{id}` | Approve / reject |
| `DELETE` | `/approval-requests/{id}` | Cancel request |

---

## 52. Error Reference

### Validation error (422)

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "email":    ["The email field is required."],
    "quantity": ["The quantity must be at least 0.0001."],
    "status":   ["The selected status is invalid."]
  }
}
```

### Business access denied (403)

```json
{ "success": false, "message": "You do not have access to this business." }
```

### Branch access denied (403)

```json
{ "success": false, "message": "You do not have access to this branch." }
```

### Not found (404)

```json
{ "success": false, "message": "Product not found." }
```

### Conflict (409)

```json
{ "success": false, "message": "Paid invoices cannot be deleted." }
{ "success": false, "message": "Completed orders cannot be deleted." }
{ "success": false, "message": "Cannot distribute more than available quantity (150.00)." }
```

### Insufficient stock (422)

```json
{ "success": false, "message": "Insufficient stock for 'Al-Baraka Premium Oil 1L'. Requested: 300." }
```

### Rate limit exceeded (429)

```json
{ "success": false, "message": "Too many login attempts. Please try again in 47 seconds." }
```

### Server error (500)

```json
{ "success": false, "message": "Server error." }
```

In `APP_ENV=local` mode, a full stack trace is also returned in the response for debugging.

---

## Appendix A — Full Endpoint Index

| # | Method | URI | Module |
|---|---|---|---|
| 1 | POST | `/auth/login` | Auth |
| 2 | POST | `/auth/logout` | Auth |
| 3 | GET | `/auth/me` | Auth |
| 4 | POST | `/auth/refresh` | Auth |
| 5 | GET | `/businesses` | Organisation |
| 6 | POST | `/businesses` | Organisation |
| 7 | GET | `/businesses/{id}` | Organisation |
| 8 | PATCH | `/businesses/{id}` | Organisation |
| 9 | DELETE | `/businesses/{id}` | Organisation |
| 10 | GET | `/branches` | Organisation |
| 11 | POST | `/branches` | Organisation |
| 12 | GET | `/branches/{id}` | Organisation |
| 13 | PATCH | `/branches/{id}` | Organisation |
| 14 | DELETE | `/branches/{id}` | Organisation |
| 15 | GET | `/warehouses` | Organisation |
| 16 | POST | `/warehouses` | Organisation |
| 17 | GET | `/warehouses/{id}` | Organisation |
| 18 | PATCH | `/warehouses/{id}` | Organisation |
| 19 | DELETE | `/warehouses/{id}` | Organisation |
| 20 | GET | `/locations` | Organisation |
| 21 | POST | `/locations` | Organisation |
| 22 | GET | `/locations/{id}` | Organisation |
| 23 | PATCH | `/locations/{id}` | Organisation |
| 24 | DELETE | `/locations/{id}` | Organisation |
| 25 | GET | `/users` | Users |
| 26 | POST | `/users` | Users |
| 27 | GET | `/users/{id}` | Users |
| 28 | PATCH | `/users/{id}` | Users |
| 29 | DELETE | `/users/{id}` | Users |
| 30 | GET | `/roles` | Access |
| 31 | GET | `/roles/{id}` | Access |
| 32 | GET | `/permissions` | Access |
| 33 | GET | `/categories` | Catalog |
| 34 | POST | `/categories` | Catalog |
| 35 | GET | `/categories/{id}` | Catalog |
| 36 | PATCH | `/categories/{id}` | Catalog |
| 37 | DELETE | `/categories/{id}` | Catalog |
| 38 | GET | `/products` | Catalog |
| 39 | POST | `/products` | Catalog |
| 40 | GET | `/products/{id}` | Catalog |
| 41 | PATCH | `/products/{id}` | Catalog |
| 42 | DELETE | `/products/{id}` | Catalog |
| 43 | GET | `/products/{id}/attributes` | Catalog |
| 44 | POST | `/products/{id}/attributes` | Catalog |
| 45 | DELETE | `/products/{id}/attributes/{attr_id}` | Catalog |
| 46 | GET | `/products/{id}/images` | Catalog |
| 47 | POST | `/products/{id}/images` | Catalog |
| 48 | DELETE | `/products/{id}/images/{image_id}` | Catalog |
| 49 | GET | `/product-collections` | Catalog |
| 50 | POST | `/product-collections` | Catalog |
| 51 | GET | `/product-collections/{id}` | Catalog |
| 52 | PATCH | `/product-collections/{id}` | Catalog |
| 53 | DELETE | `/product-collections/{id}` | Catalog |
| 54 | GET | `/stock-levels` | Inventory |
| 55 | GET | `/stock-levels/valuation` | Inventory |
| 56 | POST | `/stock-levels/reconcile` | Inventory |
| 57 | GET | `/stock-levels/{id}` | Inventory |
| 58 | GET | `/stock-movements` | Inventory |
| 59 | GET | `/stock-movements/{id}` | Inventory |
| 60 | POST | `/stock-movements/opening` | Inventory |
| 61 | POST | `/stock-movements/adjustment` | Inventory |
| 62 | POST | `/stock-movements/transfer` | Inventory |
| 63 | GET | `/inventory-thresholds` | Inventory |
| 64 | POST | `/inventory-thresholds` | Inventory |
| 65 | GET | `/inventory-thresholds/{id}` | Inventory |
| 66 | PATCH | `/inventory-thresholds/{id}` | Inventory |
| 67 | DELETE | `/inventory-thresholds/{id}` | Inventory |
| 68 | GET | `/customers` | CRM |
| 69 | POST | `/customers` | CRM |
| 70 | GET | `/customers/{id}` | CRM |
| 71 | PATCH | `/customers/{id}` | CRM |
| 72 | DELETE | `/customers/{id}` | CRM |
| 73 | GET | `/addresses` | CRM |
| 74 | POST | `/addresses` | CRM |
| 75 | GET | `/addresses/{id}` | CRM |
| 76 | PATCH | `/addresses/{id}` | CRM |
| 77 | DELETE | `/addresses/{id}` | CRM |
| 78 | GET | `/orders` | Sales |
| 79 | POST | `/orders` | Sales |
| 80 | GET | `/orders/{id}` | Sales |
| 81 | PATCH | `/orders/{id}` | Sales |
| 82 | DELETE | `/orders/{id}` | Sales |
| 83 | GET | `/invoices` | Sales |
| 84 | GET | `/invoices/{id}` | Sales |
| 85 | PATCH | `/invoices/{id}` | Sales |
| 86 | DELETE | `/invoices/{id}` | Sales |
| 87 | GET | `/payments` | Sales |
| 88 | POST | `/payments` | Sales |
| 89 | GET | `/payments/{id}` | Sales |
| 90 | PATCH | `/payments/{id}` | Sales |
| 91 | DELETE | `/payments/{id}` | Sales |
| 92 | GET | `/deliveries` | Sales |
| 93 | POST | `/deliveries` | Sales |
| 94 | GET | `/deliveries/{id}` | Sales |
| 95 | PATCH | `/deliveries/{id}` | Sales |
| 96 | DELETE | `/deliveries/{id}` | Sales |
| 97 | GET | `/branch-sales` | POS |
| 98 | POST | `/branch-sales` | POS |
| 99 | GET | `/branch-sales/{id}` | POS |
| 100 | DELETE | `/branch-sales/{id}` | POS |
| 101 | GET | `/discounts` | Marketing |
| 102 | POST | `/discounts` | Marketing |
| 103 | GET | `/discounts/{id}` | Marketing |
| 104 | PATCH | `/discounts/{id}` | Marketing |
| 105 | DELETE | `/discounts/{id}` | Marketing |
| 106 | GET | `/promotions` | Marketing |
| 107 | POST | `/promotions` | Marketing |
| 108 | GET | `/promotions/{id}` | Marketing |
| 109 | PATCH | `/promotions/{id}` | Marketing |
| 110 | DELETE | `/promotions/{id}` | Marketing |
| 111 | GET | `/manufacturing-orders` | Manufacturing |
| 112 | POST | `/manufacturing-orders` | Manufacturing |
| 113 | GET | `/manufacturing-orders/{id}` | Manufacturing |
| 114 | PATCH | `/manufacturing-orders/{id}` | Manufacturing |
| 115 | DELETE | `/manufacturing-orders/{id}` | Manufacturing |
| 116 | GET | `/production-batches/yield-report` | Manufacturing |
| 117 | POST | `/production-batches/compare` | Manufacturing |
| 118 | GET | `/production-batches` | Manufacturing |
| 119 | POST | `/production-batches` | Manufacturing |
| 120 | GET | `/production-batches/{id}` | Manufacturing |
| 121 | PATCH | `/production-batches/{id}` | Manufacturing |
| 122 | DELETE | `/production-batches/{id}` | Manufacturing |
| 123 | POST | `/production-batches/{id}/consume-raw-material` | Manufacturing |
| 124 | POST | `/production-batches/{id}/record-oil-output` | Manufacturing |
| 125 | POST | `/production-batches/{id}/quality-check` | Manufacturing |
| 126 | POST | `/production-batches/{id}/complete` | Manufacturing |
| 127 | POST | `/production-batches/{id}/distribute` | Manufacturing |
| 128 | POST | `/production-batches/{id}/return` | Manufacturing |
| 129 | POST | `/production-batches/{id}/reconcile` | Manufacturing |
| 130 | GET | `/production-batches/{id}/traceability` | Manufacturing |
| 131 | GET | `/quality-checks` | QC |
| 132 | POST | `/quality-checks` | QC |
| 133 | GET | `/quality-checks/{id}` | QC |
| 134 | PATCH | `/quality-checks/{id}` | QC |
| 135 | GET | `/finished-goods` | Manufacturing |
| 136 | GET | `/finished-goods/{id}` | Manufacturing |
| 137 | GET | `/distributions` | Manufacturing |
| 138 | POST | `/distributions` | Manufacturing |
| 139 | GET | `/distributions/{id}` | Manufacturing |
| 140 | PATCH | `/distributions/{id}` | Manufacturing |
| 141 | DELETE | `/distributions/{id}` | Manufacturing |
| 142 | GET | `/batch-returns` | Manufacturing |
| 143 | POST | `/batch-returns` | Manufacturing |
| 144 | GET | `/batch-returns/{id}` | Manufacturing |
| 145 | GET | `/batch-reconciliations` | Manufacturing |
| 146 | GET | `/batch-reconciliations/{id}` | Manufacturing |
| 147 | GET | `/batch-consumption` | Manufacturing |
| 148 | GET | `/batch-consumption/{id}` | Manufacturing |
| 149 | GET | `/profit-ledger` | Financials |
| 150 | GET | `/profit-ledger/{id}` | Financials |
| 151 | GET | `/articles` | CMS |
| 152 | POST | `/articles` | CMS |
| 153 | GET | `/articles/{id}` | CMS |
| 154 | GET | `/articles/slug/{slug}` | CMS |
| 155 | PATCH | `/articles/{id}` | CMS |
| 156 | DELETE | `/articles/{id}` | CMS |
| 157 | GET | `/faqs` | CMS |
| 158 | POST | `/faqs` | CMS |
| 159 | GET | `/faqs/{id}` | CMS |
| 160 | PATCH | `/faqs/{id}` | CMS |
| 161 | DELETE | `/faqs/{id}` | CMS |
| 162 | GET | `/media-files` | CMS |
| 163 | POST | `/media-files` | CMS |
| 164 | GET | `/media-files/{id}` | CMS |
| 165 | DELETE | `/media-files/{id}` | CMS |
| 166 | GET | `/tags` | CMS |
| 167 | POST | `/tags` | CMS |
| 168 | GET | `/tags/{id}` | CMS |
| 169 | PATCH | `/tags/{id}` | CMS |
| 170 | DELETE | `/tags/{id}` | CMS |
| 171 | GET | `/languages` | CMS |
| 172 | POST | `/languages` | CMS |
| 173 | GET | `/languages/{id}` | CMS |
| 174 | PATCH | `/languages/{id}` | CMS |
| 175 | GET | `/translations` | CMS |
| 176 | POST | `/translations` | CMS |
| 177 | DELETE | `/translations/{id}` | CMS |
| 178 | GET | `/reports/kpis` | Reports |
| 179 | GET | `/reports/sales` | Reports |
| 180 | GET | `/reports/profit` | Reports |
| 181 | GET | `/reports/inventory` | Reports |
| 182 | GET | `/reports/manufacturing` | Reports |
| 183 | GET | `/reports/purchasing` | Reports |
| 184 | GET | `/reports/branches` | Reports |
| 185 | GET | `/reports/export/{type}` | Reports |
| 186 | GET | `/settings` | System |
| 187 | PATCH | `/settings/{key}` | System |
| 188 | GET | `/notifications` | System |
| 189 | GET | `/notifications/{id}` | System |
| 190 | DELETE | `/notifications/{id}` | System |
| 191 | GET | `/audit-logs` | System |
| 192 | GET | `/audit-logs/{id}` | System |
| 193 | GET | `/approval-workflows` | Workflows |
| 194 | POST | `/approval-workflows` | Workflows |
| 195 | GET | `/approval-workflows/{id}` | Workflows |
| 196 | PATCH | `/approval-workflows/{id}` | Workflows |
| 197 | DELETE | `/approval-workflows/{id}` | Workflows |
| 198 | GET | `/approval-requests` | Workflows |
| 199 | POST | `/approval-requests` | Workflows |
| 200 | GET | `/approval-requests/{id}` | Workflows |
| 201 | PATCH | `/approval-requests/{id}` | Workflows |
| 202 | DELETE | `/approval-requests/{id}` | Workflows |

---

*Al-Baraka Platform API v1 — Generated 2026-08-15*  
*All endpoints prefixed with `/api/v1/`*  
*All endpoints require `Authorization: Bearer <token>` except `POST /auth/login`*
