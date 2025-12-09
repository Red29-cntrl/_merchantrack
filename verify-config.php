<?php
/**
 * Verify Database Configuration
 * Run: php verify-config.php
 */

echo "========================================\n";
echo "Database Configuration Verification\n";
echo "========================================\n\n";

// Bootstrap Laravel first
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "From .env file:\n";
echo "  DB_DATABASE = " . env('DB_DATABASE') . "\n";
echo "  DB_HOST = " . env('DB_HOST') . "\n";
echo "  DB_USERNAME = " . env('DB_USERNAME') . "\n\n";

echo "From Laravel config():\n";
echo "  Database = " . config('database.connections.mysql.database') . "\n";
echo "  Host = " . config('database.connections.mysql.host') . "\n";
echo "  Username = " . config('database.connections.mysql.username') . "\n\n";

// Test connection
echo "Testing database connection...\n";
try {
    $pdo = DB::connection()->getPdo();
    echo "✓ SUCCESS: Connected to database!\n";
    echo "  Database name: " . DB::connection()->getDatabaseName() . "\n";
    
    // Verify it's the correct database
    if (DB::connection()->getDatabaseName() === 'alnes_database') {
        echo "\n✓ Configuration is CORRECT!\n";
        echo "  The server should work now.\n";
    } else {
        echo "\n✗ WARNING: Connected to wrong database!\n";
        echo "  Expected: alnes_database\n";
        echo "  Got: " . DB::connection()->getDatabaseName() . "\n";
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    echo "\nPlease check:\n";
    echo "  1. MySQL/XAMPP is running\n";
    echo "  2. Database 'alnes_database' exists\n";
    echo "  3. Username and password in .env are correct\n";
}

echo "\n========================================\n";
echo "IMPORTANT: Restart your server after this!\n";
echo "Run: php artisan serve\n";
echo "========================================\n";

