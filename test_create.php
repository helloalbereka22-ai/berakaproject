<?php
try {
    $c = app(\App\Services\ProductService::class)->createCategory([
        'business_id' => 2,
        'name' => 'Test Cat',
        'type' => 'book',
        'slug' => 'test-cat-1234',
        'is_active' => true,
        'sort_order' => 0
    ], 2);
    echo "SUCCESS\n";
} catch (\Throwable $e) {
    echo $e->getMessage() . "\n";
}
