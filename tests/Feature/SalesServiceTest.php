<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Location;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Role;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\SalesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesServiceTest extends TestCase
{
    use RefreshDatabase;

    private SalesService    $sales;
    private InventoryService $inventory;
    private Customer         $customer;
    private Business         $oilBusiness;
    private Business         $bookBusiness;
    private Location         $oilLocation;
    private Location         $bookLocation;
    private Product          $oilProduct;
    private Product          $bookProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventory = app(InventoryService::class);
        $this->sales     = app(SalesService::class);

        // ── Businesses ────────────────────────────────────────────────────
        $this->oilBusiness  = Business::create(['name' => 'Oil Factory',  'code' => 'OIL', 'status' => 'active']);
        $this->bookBusiness = Business::create(['name' => 'Bookstore',    'code' => 'BOOK','status' => 'active']);

        $oilBranch  = Branch::create(['business_id' => $this->oilBusiness->id,  'name' => 'Oil Branch',  'code' => 'OB1', 'status' => 'active']);
        $bookBranch = Branch::create(['business_id' => $this->bookBusiness->id, 'name' => 'Book Branch', 'code' => 'BB1', 'status' => 'active']);

        $this->oilLocation  = Location::create(['branch_id' => $oilBranch->id,  'name' => 'Oil WH',  'code' => 'OWH1', 'type' => 'warehouse', 'status' => 'active']);
        $this->bookLocation = Location::create(['branch_id' => $bookBranch->id, 'name' => 'Book WH', 'code' => 'BWH1', 'type' => 'warehouse', 'status' => 'active']);

        // ── Categories & Products ─────────────────────────────────────────
        $oilCat  = Category::withoutGlobalScopes()->create(['business_id' => $this->oilBusiness->id,  'name' => 'Oil',  'slug' => 'oil-s',  'type' => 'oil']);
        $bookCat = Category::withoutGlobalScopes()->create(['business_id' => $this->bookBusiness->id, 'name' => 'Book', 'slug' => 'book-s', 'type' => 'book']);

        $this->oilProduct = Product::withoutGlobalScopes()->create([
            'business_id' => $this->oilBusiness->id, 'category_id' => $oilCat->id,
            'name' => 'Sesame Oil 500ml', 'sku' => 'OIL-S01', 'type' => Product::TYPE_FINISHED_PRODUCT,
            'unit' => 'ml', 'selling_price' => 10.00, 'cost_price' => 4.00, 'status' => 'active',
        ]);
        $this->bookProduct = Product::withoutGlobalScopes()->create([
            'business_id' => $this->bookBusiness->id, 'category_id' => $bookCat->id,
            'name' => 'Islamic Fiqh Vol 1', 'sku' => 'BK-S01', 'type' => Product::TYPE_BOOK,
            'unit' => 'piece', 'selling_price' => 25.00, 'cost_price' => 10.00, 'status' => 'active',
        ]);

        // ── Customer ──────────────────────────────────────────────────────
        $this->customer = Customer::create(['name' => 'Ahmed Ali', 'email' => 'ahmed@test.com', 'phone' => '0911111111', 'status' => 'active']);

        // ── Seed stock ────────────────────────────────────────────────────
        $this->inventory->receivePurchase($this->oilLocation->id,  $this->oilProduct->id,  100.0, 4.0);
        $this->inventory->receivePurchase($this->bookLocation->id, $this->bookProduct->id, 50.0,  10.0);

        // Auth user (required for audit logs)
        $role = Role::firstOrCreate(['name' => 'Staff']);
        $user = User::create(['name' => 'Staff', 'email' => 'staff@test.com', 'password' => bcrypt('p')]);
        $user->roles()->attach($role->id);
        $this->actingAs($user);
    }

    // ── Cart Tests ────────────────────────────────────────────────────────

    /** @test */
    public function oil_only_cart_is_created_correctly(): void
    {
        $this->sales->addToCart($this->customer->id, $this->oilProduct->id, 5.0);

        $summary = $this->sales->getCartSummary($this->customer->id);

        $this->assertEquals(1,     $summary['item_count']);
        $this->assertEquals(50.0,  $summary['subtotal']);    // 5 × 10
        $this->assertEquals(50.0,  $summary['total']);
        $this->assertEquals($this->oilBusiness->id, $summary['items'][0]['business_id']);
    }

    /** @test */
    public function book_only_cart_is_created_correctly(): void
    {
        $this->sales->addToCart($this->customer->id, $this->bookProduct->id, 2.0);

        $summary = $this->sales->getCartSummary($this->customer->id);

        $this->assertEquals(1,    $summary['item_count']);
        $this->assertEquals(50.0, $summary['subtotal']);  // 2 × 25
        $this->assertEquals(50.0, $summary['total']);
        $this->assertEquals($this->bookBusiness->id, $summary['items'][0]['business_id']);
    }

    /** @test */
    public function mixed_cart_accepts_products_from_multiple_businesses(): void
    {
        $this->sales->addToCart($this->customer->id, $this->oilProduct->id,  3.0);
        $this->sales->addToCart($this->customer->id, $this->bookProduct->id, 1.0);

        $summary = $this->sales->getCartSummary($this->customer->id);

        $this->assertEquals(2,     $summary['item_count']);
        $this->assertEquals(55.0,  $summary['subtotal']); // (3×10) + (1×25)

        $businessIds = collect($summary['items'])->pluck('business_id')->unique()->sort()->values()->toArray();
        $this->assertCount(2, $businessIds);
    }

    /** @test */
    public function discount_is_applied_on_backend_not_frontend(): void
    {
        // Create a 20% product-level discount for the oil product
        $discount = Discount::create(['name' => 'Oil20', 'type' => 'percentage', 'value' => 20, 'status' => 'active']);
        Promotion::create([
            'name' => 'Oil Promo', 'scope_type' => 'product', 'scope_id' => $this->oilProduct->id,
            'discount_id' => $discount->id, 'status' => 'active',
        ]);

        $this->sales->addToCart($this->customer->id, $this->oilProduct->id, 5.0);
        $summary = $this->sales->getCartSummary($this->customer->id);

        // 5 × (10 - 20%) = 5 × 8 = 40
        $this->assertEquals(40.0, $summary['subtotal']);
        $this->assertGreaterThan(0, $summary['items'][0]['discount_amount']);
    }

    /** @test */
    public function order_level_discount_is_applied(): void
    {
        $discount = Discount::create([
            'name' => 'ORDER10', 'code' => 'ORDER10', 'type' => 'percentage',
            'value' => 10, 'status' => 'active',
        ]);

        $this->sales->addToCart($this->customer->id, $this->oilProduct->id, 10.0); // subtotal = 100

        $summary = $this->sales->getCartSummary($this->customer->id, 'ORDER10');

        $this->assertEquals(100.0, $summary['subtotal']);
        $this->assertEquals(10.0,  $summary['order_discount']); // 10% of 100
        $this->assertEquals(90.0,  $summary['total']);
    }

    // ── Checkout Tests ────────────────────────────────────────────────────

    /** @test */
    public function oil_only_checkout_creates_order_and_deducts_stock(): void
    {
        $this->sales->addToCart($this->customer->id, $this->oilProduct->id, 5.0);

        $result = $this->sales->checkout($this->customer->id);

        $order   = $result['order'];
        $invoice = $result['invoice'];

        // Order created
        $this->assertEquals(Order::STATUS_CONFIRMED, $order->status);
        $this->assertEquals(50.0, (float) $order->total_amount);
        $this->assertCount(1, $order->items);

        // Invoice generated
        $this->assertNotNull($invoice);
        $this->assertStringStartsWith('INV-', $invoice->invoice_number);
        $this->assertEquals(50.0, (float) $invoice->total_amount);

        // Stock deducted: 100 - 5 = 95
        $balance = $this->inventory->getBalance($this->oilLocation->id, 'product', $this->oilProduct->id);
        $this->assertEquals(95.0, $balance);
    }

    /** @test */
    public function book_only_checkout_deducts_book_stock(): void
    {
        $this->sales->addToCart($this->customer->id, $this->bookProduct->id, 3.0);

        $result = $this->sales->checkout($this->customer->id);

        $this->assertEquals(75.0, (float) $result['order']->total_amount); // 3 × 25

        $balance = $this->inventory->getBalance($this->bookLocation->id, 'product', $this->bookProduct->id);
        $this->assertEquals(47.0, $balance); // 50 - 3
    }

    /** @test */
    public function mixed_cart_checkout_deducts_stock_from_both_businesses(): void
    {
        $this->sales->addToCart($this->customer->id, $this->oilProduct->id,  2.0);
        $this->sales->addToCart($this->customer->id, $this->bookProduct->id, 1.0);

        $result = $this->sales->checkout($this->customer->id);
        $order  = $result['order'];

        // Order total = (2×10) + (1×25) = 45
        $this->assertEquals(45.0, (float) $order->total_amount);
        $this->assertCount(2, $order->items);

        // Both businesses' stock deducted
        $oilBalance  = $this->inventory->getBalance($this->oilLocation->id,  'product', $this->oilProduct->id);
        $bookBalance = $this->inventory->getBalance($this->bookLocation->id, 'product', $this->bookProduct->id);
        $this->assertEquals(98.0, $oilBalance);
        $this->assertEquals(49.0, $bookBalance);

        // Internal split preserved
        $businessIds = $order->items->pluck('business_id')->unique()->sort()->values()->toArray();
        $this->assertCount(2, $businessIds);
    }

    /** @test */
    public function checkout_is_blocked_when_stock_insufficient(): void
    {
        $this->sales->addToCart($this->customer->id, $this->oilProduct->id, 9999.0);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Insufficient stock/');

        $this->sales->checkout($this->customer->id);
    }

    /** @test */
    public function customer_sees_consolidated_order_status(): void
    {
        $this->sales->addToCart($this->customer->id, $this->oilProduct->id,  2.0);
        $this->sales->addToCart($this->customer->id, $this->bookProduct->id, 1.0);
        $result = $this->sales->checkout($this->customer->id);

        $status = $this->sales->getConsolidatedOrderStatus($result['order']->id);

        // Customer sees one order with one status
        $this->assertArrayHasKey('order_number',    $status);
        $this->assertArrayHasKey('status',          $status);
        $this->assertArrayHasKey('items',           $status);
        $this->assertArrayHasKey('invoice_number',  $status);
        $this->assertArrayHasKey('balance_due',     $status);
        $this->assertArrayHasKey('delivery_status', $status);
        $this->assertCount(2, $status['items']);
        $this->assertEquals(45.0, $status['total_amount']);

        // Internal split is present but not part of customer-visible contract
        $this->assertEquals(2, $status['_internal']['businesses_involved']);
    }

    /** @test */
    public function payment_is_recorded_and_updates_invoice_status(): void
    {
        $this->sales->addToCart($this->customer->id, $this->oilProduct->id, 5.0);
        $result  = $this->sales->checkout($this->customer->id);
        $invoice = $result['invoice'];

        $this->assertEquals(Invoice::STATUS_ISSUED, $invoice->status);

        $payment = $this->sales->recordPayment($invoice->id, 50.0, 'cash', 'Full payment at counter');

        $this->assertEquals(Payment::STATUS_COMPLETED, $payment->status);
        $this->assertEquals(50.0, (float) $payment->amount);

        // Invoice should now be paid
        $this->assertEquals(Invoice::STATUS_PAID, $invoice->fresh()->status);
        // Order should be completed
        $this->assertEquals(Order::STATUS_COMPLETED, $result['order']->fresh()->status);
    }

    /** @test */
    public function overpayment_is_rejected(): void
    {
        $this->sales->addToCart($this->customer->id, $this->oilProduct->id, 5.0);
        $result  = $this->sales->checkout($this->customer->id);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/exceeds balance due/');

        $this->sales->recordPayment($result['invoice']->id, 999.0, 'cash');
    }

    /** @test */
    public function empty_cart_checkout_is_rejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/empty cart/');

        $this->sales->checkout($this->customer->id);
    }

    /** @test */
    public function cart_is_cleared_after_checkout(): void
    {
        $this->sales->addToCart($this->customer->id, $this->oilProduct->id, 2.0);
        $this->sales->checkout($this->customer->id);

        // A new checkout should fail — cart is cleared
        $this->expectException(\RuntimeException::class);
        $this->sales->checkout($this->customer->id);
    }
}
