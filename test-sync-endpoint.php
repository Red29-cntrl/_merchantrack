<?php
/**
 * Quick test script to verify sync endpoint is working
 * Run: php test-sync-endpoint.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a test request
$lastSync = date('Y-m-d H:i:s', strtotime('-1 minute'));
$request = Illuminate\Http\Request::create("/api/sync/changes?last_sync=" . urlencode($lastSync), 'GET');

try {
    $response = $kernel->handle($request);
    $content = $response->getContent();
    $data = json_decode($content, true);
    
    echo "✅ Sync endpoint is working!\n\n";
    echo "Response:\n";
    echo "  Sales: " . count($data['sales'] ?? []) . "\n";
    echo "  Products: " . count($data['products'] ?? []) . "\n";
    echo "  Timestamp: " . ($data['timestamp'] ?? 'N/A') . "\n\n";
    
    if (count($data['sales'] ?? []) > 0) {
        echo "Recent Sales:\n";
        foreach (array_slice($data['sales'], 0, 3) as $sale) {
            echo "  - {$sale['sale_number']}: ₱{$sale['total']} ({$sale['created_at']})\n";
        }
    }
    
    if (count($data['products'] ?? []) > 0) {
        echo "\nRecent Product Updates:\n";
        foreach (array_slice($data['products'], 0, 3) as $product) {
            echo "  - {$product['name']}: Qty {$product['quantity']} ({$product['updated_at']})\n";
        }
    }
    
    echo "\n✅ Test passed! Sync endpoint is accessible.\n";
    
} catch (Exception $e) {
    echo "❌ Error testing sync endpoint:\n";
    echo "  " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
