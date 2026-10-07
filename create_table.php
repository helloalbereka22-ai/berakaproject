<?php
// Run this script to create the manufacturing_items table
// Access: http://localhost/albareka/public/create_table.php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    DB::statement("
        CREATE TABLE IF NOT EXISTS manufacturing_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            business_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            category_id BIGINT UNSIGNED NOT NULL,
            quantity DECIMAL(15,4) DEFAULT 0,
            added_by BIGINT UNSIGNED NULL,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            INDEX idx_business_added (business_id, added_at),
            INDEX idx_product (product_id),
            CONSTRAINT fk_mf_items_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
            CONSTRAINT fk_mf_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            CONSTRAINT fk_mf_items_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
            CONSTRAINT fk_mf_items_user FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table 'manufacturing_items' created successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}