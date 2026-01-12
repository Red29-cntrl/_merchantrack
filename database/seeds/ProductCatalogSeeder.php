<?php

use App\Category;
use App\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProductCatalogSeeder extends Seeder
{
    /**
     * Seed the products table from a CSV export.
     */
    public function run()
    {
        $path = storage_path('app/products.csv');

        if (! file_exists($path)) {
            $this->command->warn("products.csv not found at {$path}. Please export the sheet as CSV to that location.");
            return;
        }

        $rows = array_map('str_getcsv', file($path));
        if (empty($rows)) {
            $this->command->warn('products.csv is empty.');
            return;
        }

        $headers = array_map('trim', array_shift($rows));

        foreach ($rows as $index => $row) {
            // Skip completely empty rows
            if (count(array_filter($row, function ($value) {
                return trim((string) $value) !== '';
            })) === 0) {
                continue;
            }

            $data = array_combine($headers, $row);
            if ($data === false) {
                $this->command->warn("Row {$index} has a column mismatch and was skipped.");
                continue;
            }

            $sku = $data['sku'] ?? null;
            $name = $data['name'] ?? null;
            $categoryName = $data['category'] ?? null;
            $price = $data['price'] ?? null;

            if (! $sku || ! $name || ! $categoryName || $price === null) {
                $this->command->warn("Row {$index} missing required fields (sku, name, category, price) and was skipped.");
                continue;
            }

            $category = Category::firstOrCreate(
                ['name' => trim($categoryName)],
                ['description' => null]
            );

            Product::updateOrCreate(
                ['sku' => trim($sku)],
                [
                    'name' => trim($name),
                    'description' => $data['description'] ?? null,
                    'category_id' => $category->id,
                    'supplier_id' => null,
                    'price' => (float) $price,
                    'cost' => null,
                    'quantity' => isset($data['quantity']) && $data['quantity'] !== '' ? (int) $data['quantity'] : 0,
                    'reorder_level' => isset($data['reorder_level']) && $data['reorder_level'] !== '' ? (int) $data['reorder_level'] : 20,
                    'unit' => $data['unit'] ?? 'pcs',
                    'image' => $data['image'] ?? null,
                    'is_active' => true,
                ]
            );
        }
    }
}

