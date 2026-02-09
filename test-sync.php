<?php
/**
 * Sync Diagnostic Script
 * Run this to check if real-time sync is properly configured
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== MerchantTrack Sync Diagnostic ===\n\n";

// 1. Check .env configuration
echo "1. Checking .env configuration...\n";
$broadcastDriver = env('BROADCAST_DRIVER', 'null');
$pusherKey = env('PUSHER_APP_KEY', 'not set');
$pusherSecret = env('PUSHER_APP_SECRET', 'not set');
$pusherId = env('PUSHER_APP_ID', 'not set');
$wsHost = env('WEBSOCKET_HOST', 'not set');
$wsPort = env('WEBSOCKET_PORT', 'not set');

echo "   BROADCAST_DRIVER: " . ($broadcastDriver === 'pusher' ? "✓ pusher" : "✗ $broadcastDriver (should be 'pusher')") . "\n";
echo "   PUSHER_APP_KEY: " . ($pusherKey !== 'not set' ? "✓ Set" : "✗ Not set") . "\n";
echo "   PUSHER_APP_SECRET: " . ($pusherSecret !== 'not set' ? "✓ Set" : "✗ Not set") . "\n";
echo "   PUSHER_APP_ID: " . ($pusherId !== 'not set' ? "✓ Set" : "✗ Not set") . "\n";
echo "   WEBSOCKET_HOST: $wsHost\n";
echo "   WEBSOCKET_PORT: $wsPort\n\n";

// 2. Check config
echo "2. Checking config values...\n";
$configDriver = config('broadcasting.default');
echo "   Config broadcasting.default: " . ($configDriver === 'pusher' ? "✓ pusher" : "✗ $configDriver") . "\n";
$pusherConfig = config('broadcasting.connections.pusher');
echo "   Pusher config exists: " . ($pusherConfig ? "✓ Yes" : "✗ No") . "\n";
if ($pusherConfig) {
    echo "   Pusher key: " . ($pusherConfig['key'] ?? 'missing') . "\n";
    echo "   Pusher host: " . ($pusherConfig['options']['host'] ?? 'missing') . "\n";
    echo "   Pusher port: " . ($pusherConfig['options']['port'] ?? 'missing') . "\n";
}
echo "\n";

// 3. Check if WebSocket server is running
echo "3. Checking WebSocket server...\n";
$wsUrl = "http://$wsHost:" . ($wsPort ?: 6001);
$ch = curl_init($wsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
$response = @curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode > 0) {
    echo "   WebSocket server reachable: ✓ Yes (HTTP $httpCode)\n";
} else {
    echo "   WebSocket server reachable: ✗ No (may not be running)\n";
    echo "   → Start with: php artisan websockets:serve --host=0.0.0.0 --port=6001\n";
}
echo "\n";

// 4. Check events
echo "4. Checking event classes...\n";
$events = [
    'App\Events\SaleCreated',
    'App\Events\ProductUpdated',
    'App\Events\InventoryUpdated',
    'App\Events\CategoryUpdated',
];

foreach ($events as $event) {
    $exists = class_exists($event);
    echo "   $event: " . ($exists ? "✓ Exists" : "✗ Missing") . "\n";
}
echo "\n";

// 5. Check channels
echo "5. Checking broadcast channels...\n";
$channelsFile = __DIR__ . '/routes/channels.php';
if (file_exists($channelsFile)) {
    $content = file_get_contents($channelsFile);
    $hasSales = strpos($content, "'sales'") !== false;
    $hasProducts = strpos($content, "'products'") !== false;
    $hasInventory = strpos($content, "'inventory'") !== false;
    $hasCategories = strpos($content, "'categories'") !== false;
    
    echo "   sales channel: " . ($hasSales ? "✓ Defined" : "✗ Missing") . "\n";
    echo "   products channel: " . ($hasProducts ? "✓ Defined" : "✗ Missing") . "\n";
    echo "   inventory channel: " . ($hasInventory ? "✓ Defined" : "✗ Missing") . "\n";
    echo "   categories channel: " . ($hasCategories ? "✓ Defined" : "✗ Missing") . "\n";
}
echo "\n";

// 6. Test broadcast
echo "6. Testing broadcast capability...\n";
try {
    if ($configDriver === 'pusher') {
        echo "   ✓ Broadcasting is enabled\n";
        echo "   → To test: Create a sale or update a product and check if events are broadcast\n";
    } else {
        echo "   ✗ Broadcasting is disabled (driver: $configDriver)\n";
        echo "   → Set BROADCAST_DRIVER=pusher in .env\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Summary
echo "=== Summary ===\n";
$issues = [];
if ($broadcastDriver !== 'pusher') {
    $issues[] = "BROADCAST_DRIVER is not set to 'pusher'";
}
if ($pusherKey === 'not set') {
    $issues[] = "PUSHER_APP_KEY is not set";
}
if ($httpCode == 0) {
    $issues[] = "WebSocket server is not running or not reachable";
}

if (empty($issues)) {
    echo "✓ All checks passed! Sync should be working.\n";
    echo "\nNext steps:\n";
    echo "1. Make sure WebSocket server is running: php artisan websockets:serve --host=0.0.0.0 --port=6001\n";
    echo "2. Open browser console (F12) and check for WebSocket connection\n";
    echo "3. Test by creating a sale on one device and watching another device\n";
} else {
    echo "✗ Issues found:\n";
    foreach ($issues as $issue) {
        echo "  - $issue\n";
    }
    echo "\nFix these issues and run this script again.\n";
}
