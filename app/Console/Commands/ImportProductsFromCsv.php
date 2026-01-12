<?php

namespace App\Console\Commands;

use App\Category;
use App\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class ImportProductsFromCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:import {source? : CSV file path or Google Sheets URL}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products from a CSV file or Google Sheets URL, auto-filling missing data and creating categories';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $source = $this->argument('source') ?? 'products.csv';
        
        // Check if it's a Google Sheets URL
        if (filter_var($source, FILTER_VALIDATE_URL) && strpos($source, 'docs.google.com/spreadsheets') !== false) {
            $this->info('Fetching data from Google Sheets...');
            $contents = $this->fetchFromGoogleSheets($source);
            if ($contents === false) {
                return 1;
            }
        } else {
            // Local file
            $relativePath = $source;
            if (!Storage::exists($relativePath)) {
                $this->error("CSV not found in storage/app at '{$relativePath}'.");
                return 1;
            }
            $contents = Storage::get($relativePath);
        }
        $rows = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $contents)));

        if (empty($rows)) {
            $this->warn('CSV is empty.');
            return 0;
        }

        // Header parsing
        $headerLine = array_shift($rows);
        $headers = str_getcsv($this->cleanLine($headerLine));
        $expected = ['sku', 'name', 'category', 'price', 'description'];

        // If header malformed, fall back to expected headers
        if (count(array_intersect($headers, $expected)) < 3) {
            $headers = $expected;
            // Put back the line if it actually contains data (heuristic: starts with SKU pattern)
            if ($this->looksLikeDataRow($headerLine)) {
                array_unshift($rows, $headerLine);
            }
        }

        $inserted = 0;
        $updated = 0;

        foreach ($rows as $line) {
            $line = $this->cleanLine($line);
            if ($line === '') {
                continue;
            }

            $data = array_combine($headers, array_pad(str_getcsv($line), count($headers), null));
            if (!$data || empty($data['sku']) || empty($data['name'])) {
                continue;
            }

            $sku = trim($data['sku']);
            $name = trim($data['name']);
            $categoryName = trim($data['category'] ?? '') ?: 'Uncategorized';
            $price = is_numeric($data['price'] ?? null) ? (float) $data['price'] : 0;
            $description = trim($data['description'] ?? '') ?: null;

            // Find or create category
            $category = Category::firstOrCreate(
                ['name' => $categoryName],
                ['description' => null]
            );

            // Defaults for incomplete data
            $payload = [
                'name' => $name,
                'sku' => $sku,
                'description' => $description,
                'category_id' => $category->id,
                'price' => $price,
                'cost' => null,
                'quantity' => 0,
                'reorder_level' => 20,
                'unit' => 'pcs',
                'is_active' => true,
            ];

            $product = Product::where('sku', $sku)->first();
            if ($product) {
                $product->update($payload);
                $updated++;
            } else {
                Product::create($payload);
                $inserted++;
            }
        }

        $this->info("Products imported. Inserted: {$inserted}, Updated: {$updated}.");
        return 0;
    }

    private function cleanLine(string $line): string
    {
        // Remove any leading stray characters (e.g., BOM or accidental prefix)
        return ltrim($line, "\xEF\xBB\xBF \t");
    }

    private function looksLikeDataRow(string $line): bool
    {
        // Heuristic: CSV with first field containing letters/digits/hyphen
        $first = str_getcsv($line)[0] ?? '';
        return (bool) preg_match('/[A-Za-z0-9]/', $first);
    }

    /**
     * Fetch CSV data from Google Sheets URL
     *
     * @param string $url
     * @return string|false
     */
    private function fetchFromGoogleSheets(string $url)
    {
        // Convert edit URL to export URL
        if (preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
            $spreadsheetId = $matches[1];
            $exportUrl = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/export?format=csv";
            
            $this->info("Fetching from: {$exportUrl}");
            
            try {
                $response = Http::timeout(30)->get($exportUrl);
                
                if ($response->successful()) {
                    $this->info('Successfully fetched data from Google Sheets.');
                    return $response->body();
                } else {
                    $this->error("Failed to fetch Google Sheets. Status: {$response->status()}");
                    $this->warn("Make sure the Google Sheet is set to 'Anyone with the link can view'");
                    return false;
                }
            } catch (\Exception $e) {
                $this->error("Error fetching Google Sheets: {$e->getMessage()}");
                return false;
            }
        } else {
            $this->error("Invalid Google Sheets URL format.");
            return false;
        }
    }
}

