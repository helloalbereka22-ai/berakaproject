<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    if (!DB::getSchemaBuilder()->hasTable('contact_messages')) {
        DB::statement("CREATE TABLE contact_messages (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            ip_address VARCHAR(45) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'unread',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        DB::statement("CREATE INDEX idx_status ON contact_messages (status)");
        echo "✅ contact_messages table created!";
    } else {
        echo "ℹ️ contact_messages table already exists.";
        // Ensure status column exists
        try {
            DB::statement("ALTER TABLE contact_messages ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'unread'");
            DB::statement("CREATE INDEX idx_status ON contact_messages (status)");
        } catch (\Throwable $e) {
            // Column may already exist; ignore error
        }
    }
} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage();
}
