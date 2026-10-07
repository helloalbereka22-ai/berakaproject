<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    \Illuminate\Support\Facades\Schema::create('zakat_cycles', function ($table) {
        $table->id();
        $table->date('start_date');
        $table->date('due_date');
        $table->string('start_date_hijri')->nullable();
        $table->string('due_date_hijri')->nullable();
        $table->decimal('nisab_amount',    18, 2)->default(0);
        $table->decimal('zakatable_amount',18, 2)->default(0);
        $table->decimal('zakat_rate',       8, 4)->default(2.5);
        $table->decimal('zakat_due',       18, 2)->default(0);
        $table->decimal('amount_paid',     18, 2)->default(0);
        $table->date('paid_date')->nullable();
        $table->string('payment_reference')->nullable();
        $table->text('notes')->nullable();
        $table->string('status')->default('active');
        $table->unsignedBigInteger('recorded_by')->nullable();
        $table->unsignedBigInteger('paid_by')->nullable();
        $table->timestamps();
    });
    echo "✅ zakat_cycles table created successfully.";
} catch (\Exception $e) {
    if (str_contains($e->getMessage(), 'already exists')) {
        echo "ℹ️ zakat_cycles table already exists.";
    } else {
        echo "❌ Error: " . $e->getMessage();
    }
}
