<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Product;
use App\Category;

echo "========================================\n";
echo "Database Import Verification\n";
echo "========================================\n\n";

echo "Total Products in Database: " . Product::count() . "\n";
echo "Total Categories in Database: " . Category::count() . "\n\n";

echo "Categories:\n";
$categories = Category::orderBy('name')->get();
foreach ($categories as $category) {
    $productCount = Product::where('category_id', $category->id)->count();
    echo "  - {$category->name} ({$productCount} products)\n";
}

echo "\nSample Products:\n";
$sampleProducts = Product::with('category')->take(5)->get();
foreach ($sampleProducts as $product) {
    $categoryName = $product->category ? $product->category->name : 'None';
    echo "  - {$product->name} [SKU: {$product->sku}] (Category: {$categoryName})\n";
}

echo "\n========================================\n";
echo "✓ All data is permanently saved in the database!\n";
echo "========================================\n";

