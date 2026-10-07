<?php
// Setup sessions table in SQLite database
$dbPath = __DIR__ . '/database/database.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create sessions table
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS "sessions" (
  "id" varchar NOT NULL PRIMARY KEY,
  "user_id" integer,
  "ip_address" varchar(45),
  "user_agent" text,
  "payload" text NOT NULL,
  "last_activity" integer NOT NULL
);

CREATE INDEX IF NOT EXISTS "sessions_user_id_index" ON "sessions" ("user_id");
CREATE INDEX IF NOT EXISTS "sessions_last_activity_index" ON "sessions" ("last_activity");
SQL;

    $pdo->exec($sql);
    echo "✓ Sessions table created successfully!\n";
    
    // Verify table exists
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='sessions';")->fetch();
    if ($result) {
        echo "✓ Verified sessions table exists in database\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
