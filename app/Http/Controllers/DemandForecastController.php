<?php

namespace App\Http\Controllers;

use App\Product;
use App\SaleItem;
use App\DemandForecast;
use App\Category;
use App\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DemandForecastController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function index(Request $request)
    {
        // Check if forecasts already exist for 2026
        $existingForecasts = DemandForecast::whereYear('forecast_date', 2026)->count();
        
        if ($existingForecasts == 0) {
            // Generate forecasts using Trend Projection Method
            $this->generateForecastUsingTrendProjection();
        }

        $availableYears = DemandForecast::selectRaw('DISTINCT YEAR(forecast_date) as year')
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();
        if (empty($availableYears)) {
            $availableYears = [2026];
        }
        
        // Default to 2026 if not in available years
        if (!in_array(2026, $availableYears)) {
            $availableYears[] = 2026;
            sort($availableYears);
        }

        $year = (int) $request->get('year', 2026);
        $selectedMonth = (int) $request->get('month', 1); // Default to January 2026
        $sort = $request->get('sort', 'desc') === 'asc' ? 'asc' : 'desc'; // default: most demand first
        $search = $request->get('search');
        $categoryId = $request->get('category_id');
        $graphCategoryId = $request->get('graph_category'); // Category filter for graph
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

        // Get forecast data for graph - separate graphs by category
        $forecastData = $this->getForecastDataForGraph($year, $selectedMonth, $confidenceMin, $graphCategoryId);

        $allProducts = Product::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('forecasts.index', [
            'forecastProducts' => $forecastProducts,
            'forecastData' => $forecastData,
            'year' => $year,
            'selectedMonth' => $selectedMonth,
            'sort' => $sort,
            'search' => $search,
            'products' => $allProducts,
            'categories' => $categories,
            'availableYears' => $availableYears,
            'categoryId' => $categoryId,
            'graphCategoryId' => $graphCategoryId,
            'confidenceKey' => $confidenceKey,
        ]);
    }

    /**
     * Generate demand forecasts using Trend Projection Method
     * Uses linear regression to calculate trend and project future demand
     */
    private function generateForecastUsingTrendProjection()
    {
        $products = Product::where('is_active', true)->get();
        $forecastYear = 2026; // Forecast for 2026
        
        $forecastCount = 0;
        
        foreach ($products as $product) {
            // Get historical sales data aggregated by date
            $historicalSales = SaleItem::where('product_id', $product->id)
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(quantity) as total')
                )
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();
            
            // Get historical inventory "out" movements aggregated by date
            $historicalOutMovements = InventoryMovement::where('product_id', $product->id)
                ->where('type', 'out')
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(quantity) as total')
                )
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();
            
            // Combine sales and inventory movements by date
            $dailyDemand = [];
            foreach ($historicalSales as $sale) {
                $date = $sale->date;
                $dailyDemand[$date] = ($dailyDemand[$date] ?? 0) + $sale->total;
            }
            
            foreach ($historicalOutMovements as $movement) {
                $date = $movement->date;
                $dailyDemand[$date] = ($dailyDemand[$date] ?? 0) + $movement->total;
            }
            
            // Need at least 7 data points for trend calculation
            if (count($dailyDemand) < 7) {
                continue;
            }
            
            // Prepare data for linear regression: time (days since first date) vs demand
            $firstDate = min(array_keys($dailyDemand));
            $firstTimestamp = strtotime($firstDate);
            $timeSeries = [];
            
            foreach ($dailyDemand as $date => $demand) {
                $daysSinceStart = (strtotime($date) - $firstTimestamp) / 86400; // Convert to days
                $timeSeries[] = [
                    'x' => $daysSinceStart,
                    'y' => $demand
                ];
            }
            
            // Calculate linear regression (trend line): y = a + bx
            // b = slope, a = intercept
            $trend = $this->calculateLinearRegression($timeSeries);
            
            if ($trend === null) {
                continue; // Skip if regression failed
            }
            
            $slope = $trend['slope']; // Daily trend (units per day)
            $intercept = $trend['intercept'];
            $rSquared = $trend['r_squared']; // Goodness of fit (0-1)
            
            // Calculate base date for forecast (first day of forecast year)
            $forecastStartDate = $forecastYear . '-01-01';
            $forecastStartTimestamp = strtotime($forecastStartDate);
            $daysToForecastStart = ($forecastStartTimestamp - $firstTimestamp) / 86400;
            
            // Generate forecasts for each day in 2026
            for ($month = 1; $month <= 12; $month++) {
                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $forecastYear);
                
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $forecastDate = $forecastYear . "-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                    
                    $existing = DemandForecast::where('product_id', $product->id)
                        ->where('forecast_date', $forecastDate)
                        ->first();
                    
                    if (!$existing) {
                        // Calculate days from first historical date to forecast date
                        $forecastTimestamp = strtotime($forecastDate);
                        $daysFromStart = ($forecastTimestamp - $firstTimestamp) / 86400;
                        
                        // Project demand using trend line: y = intercept + slope * x
                        $baseProjection = $intercept + ($slope * $daysFromStart);
                        
                        // Apply seasonal adjustment based on month (use historical monthly pattern)
                        $monthlyPattern = $this->getMonthlyPattern($dailyDemand, $month);
                        $seasonalFactor = $monthlyPattern > 0 ? $monthlyPattern : 1.0;
                        
                        // Apply weekend adjustment
                        $dayOfWeek = date('w', strtotime($forecastDate));
                        $weekendFactor = ($dayOfWeek == 0 || $dayOfWeek == 6) ? 0.85 : 1.0;
                        
                        // Final prediction with trend, seasonal, and weekend adjustments
                        $predicted = max(1, round($baseProjection * $seasonalFactor * $weekendFactor));
                        
                        // Confidence based on R-squared and data points
                        $dataPoints = count($dailyDemand);
                        $baseConfidence = min(90, 50 + ($rSquared * 30)); // R² contributes up to 30%
                        $dataConfidence = min(10, $dataPoints / 10); // Data points contribute up to 10%
                        $confidence = min(95, round($baseConfidence + $dataConfidence, 1));
                        
                        DemandForecast::create([
                            'product_id' => $product->id,
                            'forecast_date' => $forecastDate,
                            'predicted_demand' => $predicted,
                            'confidence_level' => $confidence,
                            'method' => 'Trend Projection Method (Linear Regression)',
                            'historical_data' => [
                                'slope' => round($slope, 4),
                                'intercept' => round($intercept, 2),
                                'r_squared' => round($rSquared, 4),
                                'data_points' => $dataPoints,
                                'trend_direction' => $slope > 0 ? 'increasing' : ($slope < 0 ? 'decreasing' : 'stable')
                            ],
                        ]);
                        
                        $forecastCount++;
                    }
                }
            }
        }
        
        if ($forecastCount > 0) {
            \Log::info("Generated {$forecastCount} demand forecasts using Trend Projection Method (Linear Regression).");
        }
    }
    
    /**
     * Calculate linear regression for time series data
     * Returns: ['slope' => b, 'intercept' => a, 'r_squared' => R²]
     * Formula: y = a + bx where b is slope, a is intercept
     */
    private function calculateLinearRegression($timeSeries)
    {
        if (count($timeSeries) < 2) {
            return null;
        }
        
        $n = count($timeSeries);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;
        
        foreach ($timeSeries as $point) {
            $x = $point['x'];
            $y = $point['y'];
            
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
            $sumY2 += $y * $y;
        }
        
        // Calculate slope (b)
        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if ($denominator == 0) {
            return null; // Cannot calculate (all x values are the same)
        }
        
        $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        
        // Calculate intercept (a)
        $meanX = $sumX / $n;
        $meanY = $sumY / $n;
        $intercept = $meanY - ($slope * $meanX);
        
        // Calculate R-squared (coefficient of determination)
        $ssRes = 0; // Sum of squares of residuals
        $ssTot = 0; // Total sum of squares
        
        foreach ($timeSeries as $point) {
            $x = $point['x'];
            $y = $point['y'];
            $predicted = $intercept + ($slope * $x);
            $residual = $y - $predicted;
            $ssRes += $residual * $residual;
            $ssTot += ($y - $meanY) * ($y - $meanY);
        }
        
        $rSquared = $ssTot > 0 ? 1 - ($ssRes / $ssTot) : 0;
        
        return [
            'slope' => $slope,
            'intercept' => $intercept,
            'r_squared' => max(0, min(1, $rSquared)) // Clamp between 0 and 1
        ];
    }
    
    /**
     * Get seasonal pattern for a specific month based on historical data
     * Returns average demand for that month relative to overall average
     */
    private function getMonthlyPattern($dailyDemand, $targetMonth)
    {
        $monthlyTotals = [];
        $overallTotal = 0;
        $overallCount = 0;
        
        foreach ($dailyDemand as $date => $demand) {
            $month = (int)date('n', strtotime($date));
            if (!isset($monthlyTotals[$month])) {
                $monthlyTotals[$month] = ['sum' => 0, 'count' => 0];
            }
            $monthlyTotals[$month]['sum'] += $demand;
            $monthlyTotals[$month]['count']++;
            $overallTotal += $demand;
            $overallCount++;
        }
        
        if ($overallCount == 0) {
            return 1.0;
        }
        
        $overallAvg = $overallTotal / $overallCount;
        
        if (isset($monthlyTotals[$targetMonth]) && $monthlyTotals[$targetMonth]['count'] > 0) {
            $monthAvg = $monthlyTotals[$targetMonth]['sum'] / $monthlyTotals[$targetMonth]['count'];
            return $overallAvg > 0 ? $monthAvg / $overallAvg : 1.0;
        }
        
        return 1.0; // Default: no seasonal adjustment
    }

    /**
     * Get forecast data formatted for graph - creates separate graphs for each category
     * Based on historical sales data, shows top products for selected month
     * If category filter is provided, shows only that category. Otherwise shows all categories separately.
     */
    private function getForecastDataForGraph(int $year = 2026, int $month = 1, ?int $confidenceMin = null, ?int $categoryFilter = null)
    {
        $forecasts = DemandForecast::with('product.category')
            ->whereHas('product') // Only get forecasts where product still exists
            ->whereYear('forecast_date', $year)
            ->whereMonth('forecast_date', $month)
            ->when($confidenceMin !== null, function ($q) use ($confidenceMin) {
                $q->where('confidence_level', '>=', $confidenceMin);
            })
            ->when($categoryFilter !== null, function ($q) use ($categoryFilter) {
                $q->whereHas('product', function($query) use ($categoryFilter) {
                    $query->where('category_id', $categoryFilter);
                });
            })
            ->orderBy('forecast_date')
            ->get();
        
        // If category filter is set, return single graph for that category
        if ($categoryFilter !== null) {
            // Get category name
            $category = Category::find($categoryFilter);
            $categoryName = $category ? $category->name : 'Category';
            $categoryColors = $this->getCategoryColors();
            
            return $this->buildGraphDataForCategory($forecasts, $categoryFilter, $categoryName, $categoryColors[0]);
        }
        
        // Otherwise, get top 20 products overall and create separate graphs for each category
        // Group by product and calculate total forecast for each
        $products = $forecasts->groupBy('product_id');
        $productTotals = [];
        
        foreach ($products as $productId => $productForecasts) {
            $product = $productForecasts->first()->product;
            if (!$product) continue;
            
            // Calculate total forecast for this product
            $total = $productForecasts->sum('predicted_demand');
            $categoryId = $product->category_id ?? 0;
            $categoryName = $product->category->name ?? 'Uncategorized';
            
            $productTotals[$productId] = [
                'product' => $product,
                'forecasts' => $productForecasts,
                'total' => $total,
                'category_id' => $categoryId,
                'category_name' => $categoryName
            ];
        }
        
        // Sort by total forecast (descending) and take top 20
        uasort($productTotals, function($a, $b) {
            return $b['total'] <=> $a['total'];
        });
        $top20Products = array_slice($productTotals, 0, 20, true);
        
        // Group top 20 products by category
        $productsByCategory = [];
        foreach ($top20Products as $productId => $productData) {
            $categoryId = $productData['category_id'];
            if (!isset($productsByCategory[$categoryId])) {
                $productsByCategory[$categoryId] = [
                    'category_name' => $productData['category_name'],
                    'category_id' => $categoryId,
                    'products' => []
                ];
            }
            $productsByCategory[$categoryId]['products'][$productId] = $productData;
        }
        
        // Create separate graph data for each category
        $graphsByCategory = [];
        $categoryColors = $this->getCategoryColors();
        $categoryColorIndex = 0;
        
        foreach ($productsByCategory as $categoryId => $categoryData) {
            $categoryForecasts = collect();
            foreach ($categoryData['products'] as $productData) {
                $categoryForecasts = $categoryForecasts->merge($productData['forecasts']);
            }
            
            $graphData = $this->buildGraphDataForCategory($categoryForecasts, $categoryId, $categoryData['category_name'], $categoryColors[$categoryColorIndex % count($categoryColors)]);
            $graphsByCategory[$categoryId] = $graphData;
            $categoryColorIndex++;
        }
        
        // Check for categories with sales data but no forecasts
        $categoriesWithSales = $this->getCategoriesWithSales($year, $month);
        $allCategories = array_column($productsByCategory, 'category_name', 'category_id');
        
        foreach ($categoriesWithSales as $categoryId => $categoryInfo) {
            // Skip if we already have a graph for this category
            if (isset($graphsByCategory[$categoryId])) {
                continue;
            }
            
            // Generate graph data from sales for this category
            $salesGraphData = $this->buildGraphDataFromSales($categoryId, $categoryInfo['category_name'], $year, $month, $categoryColors[$categoryColorIndex % count($categoryColors)]);
            
            // Only add if there's actual data
            if (!empty($salesGraphData['datasets'])) {
                $graphsByCategory[$categoryId] = $salesGraphData;
                $allCategories[$categoryId] = $categoryInfo['category_name'];
                $categoryColorIndex++;
            }
        }
        
        return [
            'graphs_by_category' => $graphsByCategory,
            'categories' => $allCategories
        ];
    }
    
    /**
     * Build graph data for a specific category
     * @param \Illuminate\Support\Collection|array $forecasts Collection of forecast records
     * @param int $categoryId Category ID
     * @param string|null $categoryName Category name
     * @param array|null $categoryColor Color array for the category
     */
    private function buildGraphDataForCategory($forecasts, $categoryId, $categoryName = null, $categoryColor = null)
    {
        $data = [
            'labels' => [],
            'datasets' => [],
            'category_id' => $categoryId,
            'category_name' => $categoryName
        ];
        
        if ($forecasts->isEmpty()) {
            return $data;
        }
        
        // Group by product
        $products = $forecasts->groupBy('product_id');
        $productTotals = [];
        
        foreach ($products as $productId => $productForecasts) {
            $product = $productForecasts->first()->product;
            if (!$product) continue;
            
            $total = $productForecasts->sum('predicted_demand');
            $productTotals[$productId] = [
                'product' => $product,
                'forecasts' => $productForecasts,
                'total' => $total
            ];
        }
        
        // Sort by total and get all products in this category (from top 20)
        uasort($productTotals, function($a, $b) {
            return $b['total'] <=> $a['total'];
        });
        
        // Collect all unique days
        $allDays = [];
        foreach ($productTotals as $productData) {
            foreach ($productData['forecasts'] as $forecast) {
                $day = $forecast->forecast_date->format('M d');
                if (!in_array($day, $allDays)) {
                    $allDays[] = $day;
                }
            }
        }
        
        // Sort days chronologically
        usort($allDays, function($a, $b) {
            return strtotime($a) <=> strtotime($b);
        });
        $data['labels'] = $allDays;
        
        // Get category color or use default
        if ($categoryColor === null) {
            $categoryColors = $this->getCategoryColors();
            $categoryColor = $categoryColors[0];
        }
        
        // Generate graph data for products in this category
        $productIndex = 0;
        foreach ($productTotals as $productId => $productData) {
            $product = $productData['product'];
            $productForecasts = $productData['forecasts'];
            
            // Create color variation for each product in the category
            $colorVariation = $this->getColorVariation($categoryColor, $productIndex);
            $productIndex++;
            
            $dataset = [
                'label' => $product->name,
                'data' => [],
                'borderColor' => $colorVariation['border'],
                'backgroundColor' => $colorVariation['bg'],
                'tension' => 0.4
            ];
            
            // Create a map of day to forecast value for this product
            $forecastMap = [];
            foreach ($productForecasts as $forecast) {
                $day = $forecast->forecast_date->format('M d');
                $forecastMap[$day] = $forecast->predicted_demand;
            }
            
            // Build data array matching the sorted labels
            foreach ($data['labels'] as $day) {
                $dataset['data'][] = $forecastMap[$day] ?? 0;
            }
            
            $data['datasets'][] = $dataset;
        }
        
        return $data;
    }
    
    /**
     * Get color palette for categories
     */
    private function getCategoryColors()
    {
        return [
            ['border' => 'rgb(75, 192, 192)', 'bg' => 'rgba(75, 192, 192, 0.1)'],      // Teal
            ['border' => 'rgb(255, 99, 132)', 'bg' => 'rgba(255, 99, 132, 0.1)'],      // Pink
            ['border' => 'rgb(54, 162, 235)', 'bg' => 'rgba(54, 162, 235, 0.1)'],      // Blue
            ['border' => 'rgb(255, 206, 86)', 'bg' => 'rgba(255, 206, 86, 0.1)'],      // Yellow
            ['border' => 'rgb(153, 102, 255)', 'bg' => 'rgba(153, 102, 255, 0.1)'],    // Purple
            ['border' => 'rgb(255, 159, 64)', 'bg' => 'rgba(255, 159, 64, 0.1)'],      // Orange
            ['border' => 'rgb(199, 199, 199)', 'bg' => 'rgba(199, 199, 199, 0.1)'],    // Gray
            ['border' => 'rgb(83, 102, 255)', 'bg' => 'rgba(83, 102, 255, 0.1)'],      // Indigo
            ['border' => 'rgb(99, 255, 132)', 'bg' => 'rgba(99, 255, 132, 0.1)'],      // Green
            ['border' => 'rgb(255, 99, 255)', 'bg' => 'rgba(255, 99, 255, 0.1)'],      // Magenta
        ];
    }
    
    /**
     * Get color variation for products in the same category
     */
    private function getColorVariation($baseColor, $productIndex)
    {
        // Extract RGB values from border color
        preg_match('/rgb\((\d+),\s*(\d+),\s*(\d+)\)/', $baseColor['border'], $matches);
        if (count($matches) == 4) {
            $r = (int)$matches[1];
            $g = (int)$matches[2];
            $b = (int)$matches[3];
            
            // Create slight variation (darker/lighter) for different products in same category
            $variation = $productIndex * 20; // Adjust brightness
            $r = max(0, min(255, $r + $variation));
            $g = max(0, min(255, $g + $variation));
            $b = max(0, min(255, $b + $variation));
            
            // Extract alpha from bg color
            preg_match('/rgba\([^)]+,\s*([\d.]+)\)/', $baseColor['bg'], $alphaMatch);
            $alpha = isset($alphaMatch[1]) ? $alphaMatch[1] : '0.1';
            
            return [
                'border' => "rgb({$r}, {$g}, {$b})",
                'bg' => "rgba({$r}, {$g}, {$b}, {$alpha})"
            ];
        }
        
        return $baseColor;
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

    /**
     * Get categories that have sales data for the given year and month
     * but may not have forecasts
     */
    private function getCategoriesWithSales(int $year, int $month)
    {
        $categoriesWithSales = [];
        
        // Get sales data for the selected year and month
        $salesData = SaleItem::with('product.category')
            ->whereHas('product', function($query) {
                $query->whereNotNull('category_id');
            })
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get();
        
        // Group by category
        foreach ($salesData as $saleItem) {
            if (!$saleItem->product || !$saleItem->product->category) {
                continue;
            }
            
            $categoryId = $saleItem->product->category_id;
            if (!isset($categoriesWithSales[$categoryId])) {
                $categoriesWithSales[$categoryId] = [
                    'category_id' => $categoryId,
                    'category_name' => $saleItem->product->category->name,
                    'sales' => []
                ];
            }
            
            $categoriesWithSales[$categoryId]['sales'][] = $saleItem;
        }
        
        return $categoriesWithSales;
    }

    /**
     * Build graph data from sales data for categories without forecasts
     */
    private function buildGraphDataFromSales(int $categoryId, string $categoryName, int $year, int $month, array $categoryColor)
    {
        $data = [
            'labels' => [],
            'datasets' => [],
            'category_id' => $categoryId,
            'category_name' => $categoryName
        ];
        
        // Get sales data for this category in the selected month
        $salesData = SaleItem::with('product')
            ->whereHas('product', function($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get();
        
        if ($salesData->isEmpty()) {
            return $data;
        }
        
        // Group sales by product and day
        $productsByDay = [];
        foreach ($salesData as $saleItem) {
            if (!$saleItem->product) continue;
            
            $productId = $saleItem->product_id;
            $day = $saleItem->created_at->format('M d');
            
            if (!isset($productsByDay[$productId])) {
                $productsByDay[$productId] = [
                    'product' => $saleItem->product,
                    'days' => []
                ];
            }
            
            if (!isset($productsByDay[$productId]['days'][$day])) {
                $productsByDay[$productId]['days'][$day] = 0;
            }
            
            $productsByDay[$productId]['days'][$day] += $saleItem->quantity;
        }
        
        // Collect all unique days
        $allDays = [];
        foreach ($productsByDay as $productData) {
            foreach (array_keys($productData['days']) as $day) {
                if (!in_array($day, $allDays)) {
                    $allDays[] = $day;
                }
            }
        }
        
        // Sort days chronologically
        usort($allDays, function($a, $b) {
            return strtotime($a) <=> strtotime($b);
        });
        $data['labels'] = $allDays;
        
        // Get top products by total sales
        $productTotals = [];
        foreach ($productsByDay as $productId => $productData) {
            $total = array_sum($productData['days']);
            $productTotals[$productId] = [
                'product' => $productData['product'],
                'days' => $productData['days'],
                'total' => $total
            ];
        }
        
        // Sort by total and take top products (limit to 10 for readability)
        uasort($productTotals, function($a, $b) {
            return $b['total'] <=> $a['total'];
        });
        $topProducts = array_slice($productTotals, 0, 10, true);
        
        // Generate graph data for top products
        $productIndex = 0;
        foreach ($topProducts as $productId => $productData) {
            $product = $productData['product'];
            $dayData = $productData['days'];
            
            // Create color variation for each product
            $colorVariation = $this->getColorVariation($categoryColor, $productIndex);
            $productIndex++;
            
            $dataset = [
                'label' => $product->name,
                'data' => [],
                'borderColor' => $colorVariation['border'],
                'backgroundColor' => $colorVariation['bg'],
                'tension' => 0.4
            ];
            
            // Build data array matching the sorted labels
            foreach ($data['labels'] as $day) {
                $dataset['data'][] = $dayData[$day] ?? 0;
            }
            
            $data['datasets'][] = $dataset;
        }
        
        return $data;
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
