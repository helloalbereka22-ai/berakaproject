<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    // Create guest_orders table directly
    if (!Schema::hasTable('guest_orders')) {
        Schema::create('guest_orders', function ($table) {
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
        echo "✅ guest_orders table created!";
    } else {
        echo "✅ guest_orders table already exists.";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
