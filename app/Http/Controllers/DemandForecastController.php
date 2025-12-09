<?php

namespace App\Http\Controllers;

use App\Product;
use App\SaleItem;
use App\DemandForecast;
use App\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DemandForecastController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function index(Request $request)
    {
        // Generate forecast for 2025 based on Google Sheets data
        $this->generateForecastFromGoogleSheets();

        $availableYears = DemandForecast::selectRaw('DISTINCT YEAR(forecast_date) as year')
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();
        if (empty($availableYears)) {
            $availableYears = [2025];
        }

        $year = (int) $request->get('year', $availableYears[0]);
        $sort = $request->get('sort', 'desc') === 'asc' ? 'asc' : 'desc'; // default: most demand first
        $search = $request->get('search');
        $categoryId = $request->get('category_id');
        $confidenceKey = $request->get('confidence'); // e.g., '90', '75', '50'

        $confidenceMin = null;
        if (in_array($confidenceKey, ['90', '75', '50'], true)) {
            $confidenceMin = (int) $confidenceKey;
        }

        $productsQuery = Product::with(['category', 'demandForecasts' => function ($q) use ($year, $confidenceMin) {
                $q->whereYear('forecast_date', $year)->orderBy('forecast_date');
                if ($confidenceMin !== null) {
                    $q->where('confidence_level', '>=', $confidenceMin);
                }
            }])
            ->select('products.*')
            ->selectSub(
                DemandForecast::selectRaw('COALESCE(SUM(predicted_demand), 0)')
                    ->whereColumn('product_id', 'products.id')
                    ->whereYear('forecast_date', $year)
                    ->when($confidenceMin !== null, function ($q) use ($confidenceMin) {
                        $q->where('confidence_level', '>=', $confidenceMin);
                    }),
                'forecast_total'
            );

        if ($categoryId) {
            $productsQuery->where('category_id', $categoryId);
        }

        if ($search) {
            $productsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%');
            });
        }

        $forecastProducts = $productsQuery
            ->orderBy('forecast_total', $sort)
            ->orderBy('name')
            ->paginate(15)
            ->appends($request->query());

        // Get forecast data for graph (selected year)
        $forecastData = $this->getForecastDataForGraph($year, $confidenceMin);

        $allProducts = Product::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('forecasts.index', [
            'forecastProducts' => $forecastProducts,
            'forecastData' => $forecastData,
            'year' => $year,
            'sort' => $sort,
            'search' => $search,
            'products' => $allProducts,
            'categories' => $categories,
            'availableYears' => $availableYears,
            'categoryId' => $categoryId,
            'confidenceKey' => $confidenceKey,
        ]);
    }

    /**
     * Fetch data from Google Sheets and generate forecasts
     */
    private function generateForecastFromGoogleSheets()
    {
        // Google Sheets URL - export as CSV
        $sheetId = '1Nya0XLzN38Fnlkzrj8-hmJcsBXOexJ8rQInzH8jTqlA';
        $csvUrl = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid=0";
        
        try {
            $response = Http::timeout(10)->get($csvUrl);
            if ($response->successful()) {
                $csvData = $response->body();
                $this->processGoogleSheetsData($csvData);
            }
        } catch (\Exception $e) {
            // If Google Sheets fetch fails, use database sales data
            \Log::warning('Failed to fetch Google Sheets data: ' . $e->getMessage());
            $this->generateForecastFromDatabase();
        }
    }

    /**
     * Process CSV data from Google Sheets
     */
    private function processGoogleSheetsData($csvData)
    {
        $lines = explode("\n", $csvData);
        $headers = str_getcsv(array_shift($lines));
        
        $monthlySales = [];
        $productSales = [];
        
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $data = str_getcsv($line);
            if (count($data) < 3) continue;
            
            // Assuming format: Date, Product, Quantity
            $date = isset($data[0]) ? $data[0] : null;
            $product = isset($data[1]) ? $data[1] : null;
            $quantity = isset($data[2]) ? (int)$data[2] : 0;
            
            if ($date && $product) {
                $yearMonth = date('Y-m', strtotime($date));
                if (!isset($monthlySales[$yearMonth])) {
                    $monthlySales[$yearMonth] = [];
                }
                if (!isset($monthlySales[$yearMonth][$product])) {
                    $monthlySales[$yearMonth][$product] = 0;
                }
                $monthlySales[$yearMonth][$product] += $quantity;
                
                if (!isset($productSales[$product])) {
                    $productSales[$product] = [];
                }
                if (!isset($productSales[$product][$yearMonth])) {
                    $productSales[$product][$yearMonth] = 0;
                }
                $productSales[$product][$yearMonth] += $quantity;
            }
        }
        
        // Generate forecasts for 2025
        $this->createForecastsFromData($productSales);
    }

    /**
     * Generate forecasts from processed data
     */
    private function createForecastsFromData($productSales)
    {
        foreach ($productSales as $productName => $monthlyData) {
            // Find product by name
            $product = Product::where('name', 'like', '%' . $productName . '%')->first();
            if (!$product) continue;
            
            // Get data from 2019-2024
            $historicalMonths = [];
            foreach ($monthlyData as $month => $quantity) {
                if (preg_match('/^(2019|2020|2021|2022|2023|2024)-\d{2}$/', $month)) {
                    $historicalMonths[] = ['month' => $month, 'quantity' => $quantity];
                }
            }
            
            if (count($historicalMonths) < 6) continue; // Need at least 6 months of data
            
            // Calculate average monthly sales
            $avgMonthly = array_sum(array_column($historicalMonths, 'quantity')) / count($historicalMonths);
            
            // Predict for each month in 2025
            for ($month = 1; $month <= 12; $month++) {
                $forecastDate = "2025-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
                
                // Check if forecast already exists
                $existing = DemandForecast::where('product_id', $product->id)
                    ->where('forecast_date', $forecastDate)
                    ->first();
                
                if (!$existing) {
                    // Apply seasonal adjustment (simple: use trend from last year)
                    $lastYearMonth = "2024-" . str_pad($month, 2, '0', STR_PAD_LEFT);
                    $lastYearSales = $monthlyData[$lastYearMonth] ?? $avgMonthly;
                    
                    // Predict with 10% growth assumption
                    $predicted = round($lastYearSales * 1.1);
                    
                    DemandForecast::create([
                        'product_id' => $product->id,
                        'forecast_date' => $forecastDate,
                        'predicted_demand' => $predicted,
                        'confidence_level' => 75.0,
                        'method' => 'Historical Trend Analysis (2019-2024)',
                        'historical_data' => $historicalMonths,
                    ]);
                }
            }
        }
    }

    /**
     * Generate forecast from database sales if Google Sheets unavailable
     */
    private function generateForecastFromDatabase()
    {
        $products = Product::all();
        
        foreach ($products as $product) {
            // Get historical sales from 2019-2024
            $historicalSales = SaleItem::where('product_id', $product->id)
                ->whereYear('created_at', '>=', 2019)
                ->whereYear('created_at', '<=', 2024)
                ->select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('SUM(quantity) as total')
                )
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();
            
            if ($historicalSales->count() < 6) continue;
            
            $avgMonthly = $historicalSales->avg('total') ?? 0;
            
            // Predict for 2025
            for ($month = 1; $month <= 12; $month++) {
                $forecastDate = "2025-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
                
                $existing = DemandForecast::where('product_id', $product->id)
                    ->where('forecast_date', $forecastDate)
                    ->first();
                
                if (!$existing) {
                    DemandForecast::create([
                        'product_id' => $product->id,
                        'forecast_date' => $forecastDate,
                        'predicted_demand' => round($avgMonthly * 1.1),
                        'confidence_level' => 70.0,
                        'method' => 'Database Sales Analysis (2019-2024)',
                        'historical_data' => $historicalSales->toArray(),
                    ]);
                }
            }
        }
    }

    /**
     * Get forecast data formatted for graph
     */
    private function getForecastDataForGraph(int $year = 2025, ?int $confidenceMin = null)
    {
        $forecasts = DemandForecast::with('product')
            ->whereHas('product') // Only get forecasts where product still exists
            ->whereYear('forecast_date', $year)
            ->when($confidenceMin !== null, function ($q) use ($confidenceMin) {
                $q->where('confidence_level', '>=', $confidenceMin);
            })
            ->orderBy('forecast_date')
            ->get();
        
        $data = [
            'labels' => [],
            'datasets' => []
        ];
        
        $products = $forecasts->groupBy('product_id');
        
        foreach ($products as $productId => $productForecasts) {
            $product = $productForecasts->first()->product;
            if (!$product) continue; // Skip if product doesn't exist
            
            $dataset = [
                'label' => $product->name,
                'data' => [],
                'borderColor' => $this->getRandomColor(),
                'backgroundColor' => $this->getRandomColor(0.1),
                'tension' => 0.4
            ];
            
            foreach ($productForecasts as $forecast) {
                $month = $forecast->forecast_date->format('M Y');
                if (!in_array($month, $data['labels'])) {
                    $data['labels'][] = $month;
                }
                $dataset['data'][] = $forecast->predicted_demand;
            }
            
            $data['datasets'][] = $dataset;
        }
        
        return $data;
    }

    private function getRandomColor($alpha = 1)
    {
        $colors = [
            'rgba(75, 192, 192, ' . $alpha . ')',
            'rgba(255, 99, 132, ' . $alpha . ')',
            'rgba(54, 162, 235, ' . $alpha . ')',
            'rgba(255, 206, 86, ' . $alpha . ')',
            'rgba(153, 102, 255, ' . $alpha . ')',
            'rgba(255, 159, 64, ' . $alpha . ')',
        ];
        return $colors[array_rand($colors)];
    }

    public function show(DemandForecast $forecast)
    {
        $forecast->load('product');
        
        // If product doesn't exist, redirect back with error
        if (!$forecast->product) {
            return redirect()->route('forecasts.index')
                ->with('error', 'The product associated with this forecast has been deleted.');
        }
        
        return view('forecasts.show', compact('forecast'));
    }
}
