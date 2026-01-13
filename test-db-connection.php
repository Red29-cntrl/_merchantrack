<?php
/**
 * Test Database Connection Script
 * Run: php test-db-connection.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "========================================\n";
echo "Database Configuration Test\n";
echo "========================================\n\n";

echo "From .env file:\n";
echo "  DB_DATABASE: " . env('DB_DATABASE') . "\n";
echo "  DB_HOST: " . env('DB_HOST') . "\n";
echo "  DB_USERNAME: " . env('DB_USERNAME') . "\n\n";

echo "From config():\n";
echo "  Database: " . config('database.connections.mysql.database') . "\n";
echo "  Host: " . config('database.connections.mysql.host') . "\n";
echo "  Username: " . config('database.connections.mysql.username') . "\n\n";

echo "Testing connection...\n";
try {
    $pdo = DB::connection()->getPdo();
    echo "✓ Connection successful!\n";
    echo "  Connected to database: " . DB::connection()->getDatabaseName() . "\n";
    
    // Test a simple query
    $result = DB::select('SELECT DATABASE() as db');
    echo "  Current database: " . $result[0]->db . "\n";
    
} catch (Exception $e) {
    echo "✗ Connection failed!\n";
    echo "  Error: " . $e->getMessage() . "\n";
    echo "\n";
    echo "Please check:\n";
    echo "  1. MySQL/XAMPP is running\n";
    echo "  2. Database 'alnes_database' exists\n";
    echo "  3. Username and password are correct\n";
    echo "  4. Restart your Laravel server (php artisan serve)\n";
}

echo "\n========================================\n";

