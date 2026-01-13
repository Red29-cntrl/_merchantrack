<?php
/**
 * Quick script to verify database configuration
 * Run: php fix-database.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Database Configuration Check ===\n";
echo "DB_CONNECTION: " . env('DB_CONNECTION') . "\n";
echo "DB_HOST: " . env('DB_HOST') . "\n";
echo "DB_PORT: " . env('DB_PORT') . "\n";
echo "DB_DATABASE: " . env('DB_DATABASE') . "\n";
echo "DB_USERNAME: " . env('DB_USERNAME') . "\n";
echo "\n";

echo "=== Config Values ===\n";
echo "Database: " . config('database.connections.mysql.database') . "\n";
echo "Host: " . config('database.connections.mysql.host') . "\n";
echo "\n";

try {
    DB::connection()->getPdo();
    echo "✓ Database connection successful!\n";
    echo "Connected to: " . DB::connection()->getDatabaseName() . "\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
}

