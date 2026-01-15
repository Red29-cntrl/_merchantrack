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

        // Add inventory gap and reorder calculations to each product
        foreach ($forecastProducts as $product) {
            // Get total forecasted demand for the selected month
            // Forecasts are stored with first day of month (YYYY-MM-01)
            $monthStart = sprintf('%04d-%02d-01', $year, $selectedMonth);
            $monthForecast = $product->demandForecasts
                ->filter(function($forecast) use ($monthStart) {
                    return $forecast->forecast_date->format('Y-m-d') === $monthStart;
                })
                ->sum('predicted_demand');
            
            // Calculate inventory gap: forecasted_demand - current_quantity
            $product->inventory_gap = $monthForecast - $product->quantity;
            
            // Reorder required if: forecasted_demand > current_quantity OR quantity <= reorder_level
            $product->reorder_required = ($monthForecast > $product->quantity) || ($product->quantity <= $product->reorder_level);
            
            // Store month forecast for display
            $product->month_forecast = $monthForecast;
        }

        // Get categories that have sales data (data-driven - only categories with actual sales appear)
        $categoriesWithSales = $this->getCategoriesWithSalesData();
        
        // Get dynamic category trend data (for the selected category or first available)
        $firstCategory = $categoriesWithSales->first();
        $selectedCategoryId = $request->get('category_trend', $firstCategory ? $firstCategory->id : null);
        $categoryTrendData = $selectedCategoryId ? $this->getCategoryTrendData($selectedCategoryId) : null;

        // Get actual vs forecasted data for graphs
        $actualVsForecastedData = $this->getActualVsForecastedData($year, $selectedMonth, null);
        
        // Get forecasted demand per product (bar chart data)
        $forecastPerProductData = $this->getForecastPerProductData($year, $selectedMonth, null);
        
        // Get forecasted demand vs inventory (bar chart data)
        $forecastVsInventoryData = $this->getForecastVsInventoryData($year, $selectedMonth, null);

        $allProducts = Product::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('forecasts.index', [
            'forecastProducts' => $forecastProducts,
            'actualVsForecastedData' => $actualVsForecastedData,
            'forecastPerProductData' => $forecastPerProductData,
            'forecastVsInventoryData' => $forecastVsInventoryData,
            'categoriesWithSales' => $categoriesWithSales,
            'categoryTrendData' => $categoryTrendData,
            'selectedCategoryId' => $selectedCategoryId,
            'year' => $year,
            'selectedMonth' => $selectedMonth,
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
     * Generate demand forecasts using Prophet-like time-series forecasting
     * Uses monthly aggregation from sales data only (sales.created_at)
     * Implements additive model: y(t) = trend(t) + seasonality(t) + noise
     */
    private function generateForecastUsingTrendProjection()
    {
        $products = Product::where('is_active', true)->get();
        $forecastYear = 2026; // Forecast for 2026
        $forecastHorizon = 12; // Forecast 12 months ahead
        
        $forecastCount = 0;
        
        foreach ($products as $product) {
            // Get historical monthly demand from sales data only
            // Use sales.created_at for grouping (not sale_items.created_at)
            $monthlyDemand = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sale_items.product_id', $product->id)
                ->select(
                    DB::raw('YEAR(sales.created_at) as year'),
                    DB::raw('MONTH(sales.created_at) as month'),
                    DB::raw('DATE_FORMAT(sales.created_at, "%Y-%m-01") as month_start'),
                    DB::raw('SUM(sale_items.quantity) as total_quantity')
                )
                ->groupBy('year', 'month', 'month_start')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();
            
            // Need at least 3 months of data for forecasting
            if ($monthlyDemand->count() < 3) {
                continue;
            }
            
            // Prepare time series data for Prophet-like forecasting
            $timeSeries = [];
            foreach ($monthlyDemand as $record) {
                $timeSeries[] = [
                    'ds' => $record->month_start, // Date string (Prophet format)
                    'y' => (float) $record->total_quantity // Demand value
                ];
            }
            
            // Train Prophet-like model and generate forecasts
            $forecasts = $this->forecastWithProphetLikeModel($timeSeries, $forecastHorizon, $forecastYear);
            
            if (empty($forecasts)) {
                continue;
            }
            
            // Store monthly forecasts (first day of each month)
            foreach ($forecasts as $forecast) {
                $forecastDate = $forecast['ds'];
                
                // Check if forecast already exists
                $existing = DemandForecast::where('product_id', $product->id)
                    ->where('forecast_date', $forecastDate)
                    ->first();
                
                if (!$existing) {
                    DemandForecast::create([
                        'product_id' => $product->id,
                        'forecast_date' => $forecastDate,
                        'predicted_demand' => (int) round($forecast['yhat']),
                        'confidence_level' => $forecast['confidence'],
                        'method' => 'Prophet-like Time-Series Forecasting',
                        'historical_data' => [
                            // Confidence is an ESTIMATE of reliability; it does not guarantee accuracy.
                            // It is computed from historical fit + amount of history + variability.
                            'historical_months' => count($timeSeries),
                            'trend' => $forecast['trend'],
                            'seasonality' => $forecast['seasonality'],
                            'yhat_lower' => isset($forecast['yhat_lower']) ? round($forecast['yhat_lower']) : null,
                            'yhat_upper' => isset($forecast['yhat_upper']) ? round($forecast['yhat_upper']) : null,
                            'std_dev' => isset($forecast['std_dev']) ? round($forecast['std_dev'], 4) : null,
                        ],
                    ]);
                    
                    $forecastCount++;
                }
            }
        }
        
        if ($forecastCount > 0) {
            \Log::info("Generated {$forecastCount} monthly demand forecasts using Prophet-like Time-Series Forecasting.");
        }
    }
    
    /**
     * Prophet-like forecasting model (additive: y = trend + seasonality)
     * @param array $timeSeries Historical data [['ds' => 'YYYY-MM-DD', 'y' => value], ...]
     * @param int $horizon Number of months to forecast
     * @param int $forecastYear Year to forecast
     * @return array Forecasts [['ds' => 'YYYY-MM-01', 'yhat' => value, 'confidence' => %, ...], ...]
     */
    private function forecastWithProphetLikeModel($timeSeries, $horizon, $forecastYear)
    {
        if (count($timeSeries) < 3) {
            return [];
        }
        
        // Convert dates to timestamps for calculations
        $data = [];
        foreach ($timeSeries as $point) {
            $timestamp = strtotime($point['ds']);
            $data[] = [
                't' => $timestamp,
                'y' => $point['y'],
                'month' => (int) date('n', $timestamp),
            ];
        }
        
        // Calculate trend using linear regression
        $trend = $this->calculateTrend($data);
        if ($trend === null) {
            return [];
        }
        
        // Calculate monthly seasonality (average deviation per month)
        $seasonality = $this->calculateMonthlySeasonality($data, $trend);
        
        // Estimate forecast reliability (confidence) based on historical fit + data quality.
        // IMPORTANT: This confidence score does NOT change the forecast values (yhat).
        // It is only an interpretable reliability indicator derived from:
        // - model fit quality (R²-like)
        // - amount of historical data available
        // - variability of historical demand (residual standard deviation)
        $stdDev = $this->calculateStandardDeviation($data, $trend, $seasonality);
        $confidence = $this->calculateConfidence($data, $trend, $seasonality, $stdDev);
        
        // Generate forecasts
        $forecasts = [];
        $lastTimestamp = max(array_column($data, 't'));
        
        for ($month = 1; $month <= $horizon; $month++) {
            // Calculate target month
            $targetMonth = $month;
            $targetYear = $forecastYear;
            
            // Handle year rollover
            if ($targetMonth > 12) {
                $targetYear += floor(($targetMonth - 1) / 12);
                $targetMonth = (($targetMonth - 1) % 12) + 1;
            }
            
            $forecastDate = sprintf('%04d-%02d-01', $targetYear, $targetMonth);
            $forecastTimestamp = strtotime($forecastDate);
            
            // Calculate month index from start of data
            $dataCount = $trend['data_count'];
            $monthIndex = $dataCount + ($month - 1); // Continue from last data point
            
            // Forecast: trend + seasonality
            // Trend is per month, so use month index directly
            $trendValue = $trend['intercept'] + ($trend['slope'] * $monthIndex);
            $seasonalValue = isset($seasonality[$targetMonth]) ? $seasonality[$targetMonth] : 0;
            $yhat = max(0, $trendValue + $seasonalValue);
            
            // Calculate uncertainty interval using residual standard deviation (approx. 95% interval).
            // This is an uncertainty range, not a guaranteed probability.
            $yhat_lower = max(0, $yhat - (1.96 * $stdDev)); // 95% confidence interval
            $yhat_upper = $yhat + (1.96 * $stdDev);
            
            $forecasts[] = [
                'ds' => $forecastDate,
                'yhat' => $yhat,
                'yhat_lower' => $yhat_lower,
                'yhat_upper' => $yhat_upper,
                'trend' => $trendValue,
                'seasonality' => $seasonalValue,
                'confidence' => $confidence,
                // Residual standard deviation used for the uncertainty range (same sigma for all horizon steps).
                'std_dev' => $stdDev,
            ];
        }
        
        return $forecasts;
    }
    
    /**
     * Calculate trend component using linear regression
     * Uses month index (0, 1, 2, ...) for numerical stability
     */
    private function calculateTrend($data)
    {
        if (count($data) < 2) {
            return null;
        }
        
        // Sort by timestamp
        usort($data, function($a, $b) {
            return $a['t'] <=> $b['t'];
        });
        
        // Use month index (0, 1, 2, ...) for regression
        $n = count($data);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;
        
        foreach ($data as $index => $point) {
            $x = $index; // Month index
            $y = $point['y'];
            
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }
        
        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if ($denominator == 0) {
            return null;
        }
        
        $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        $meanX = $sumX / $n;
        $meanY = $sumY / $n;
        $intercept = $meanY - ($slope * $meanX);
        
        return [
            'slope' => $slope, // Per month
            'intercept' => $intercept,
            'data_count' => $n,
        ];
    }
    
    /**
     * Calculate monthly seasonality (average deviation from trend per month)
     */
    private function calculateMonthlySeasonality($data, $trend)
    {
        // Sort data by timestamp to get correct indices
        usort($data, function($a, $b) {
            return $a['t'] <=> $b['t'];
        });
        
        $monthlyDeviations = [];
        $monthlyCounts = [];
        
        foreach ($data as $index => $point) {
            $month = $point['month'];
            // Use month index (0, 1, 2, ...) for trend calculation
            $expected = $trend['intercept'] + ($trend['slope'] * $index);
            $deviation = $point['y'] - $expected;
            
            if (!isset($monthlyDeviations[$month])) {
                $monthlyDeviations[$month] = 0;
                $monthlyCounts[$month] = 0;
            }
            
            $monthlyDeviations[$month] += $deviation;
            $monthlyCounts[$month]++;
        }
        
        // Average deviations per month
        $seasonality = [];
        for ($month = 1; $month <= 12; $month++) {
            if (isset($monthlyCounts[$month]) && $monthlyCounts[$month] > 0) {
                $seasonality[$month] = $monthlyDeviations[$month] / $monthlyCounts[$month];
            } else {
                $seasonality[$month] = 0;
            }
        }
        
        // Normalize seasonality (mean should be ~0)
        $meanSeasonality = array_sum($seasonality) / 12;
        foreach ($seasonality as $month => $value) {
            $seasonality[$month] -= $meanSeasonality;
        }
        
        return $seasonality;
    }
    
    /**
     * Calculate confidence level based on model fit
     *
     * Confidence is an ESTIMATED RELIABILITY indicator (0–100%), not a guarantee.
     * It depends on:
     * - Fit quality (R²-like metric)
     * - Amount of historical data available
     * - Variability (residual standard deviation relative to mean demand)
     */
    private function calculateConfidence($data, $trend, $seasonality, $stdDev)
    {
        // Sort data by timestamp to get correct indices
        usort($data, function($a, $b) {
            return $a['t'] <=> $b['t'];
        });
        
        // Calculate R-squared
        $meanY = array_sum(array_column($data, 'y')) / count($data);
        $ssTot = 0;
        $ssRes = 0;
        
        foreach ($data as $index => $point) {
            // Use month index (0, 1, 2, ...) for trend calculation
            $expected = $trend['intercept'] + ($trend['slope'] * $index);
            $seasonal = isset($seasonality[$point['month']]) ? $seasonality[$point['month']] : 0;
            $predicted = $expected + $seasonal;
            
            $ssTot += pow($point['y'] - $meanY, 2);
            $ssRes += pow($point['y'] - $predicted, 2);
        }
        
        $rSquared = $ssTot > 0 ? 1 - ($ssRes / $ssTot) : 0;
        $rSquared = max(0, min(1, $rSquared));
        
        // Amount of historical data (more points => more reliable, but with diminishing returns).
        $dataPoints = count($data);
        $dataScore = min(1.0, log(max(1, $dataPoints)) / log(24)); // ~1.0 by ~24 months

        // Variability / stability score:
        // Use coefficient of variation (CV = sigma / mean) so different scale products are comparable.
        // Lower CV => more stable => higher confidence.
        $epsilon = 1e-9;
        $cv = $stdDev / max($epsilon, abs($meanY));
        $stabilityScore = exp(-1.25 * $cv); // smooth decay: volatile history => lower score
        $stabilityScore = max(0.0, min(1.0, $stabilityScore));

        // Weighted combination (normalized to 0–100%).
        // Fit is strongest signal, then stability, then data amount.
        $fitScore = $rSquared; // already 0..1
        $combined = (0.50 * $fitScore) + (0.30 * $stabilityScore) + (0.20 * $dataScore);

        // Clamp and return as a percentage (estimate, not guarantee).
        return round(max(0, min(100, $combined * 100)), 1);
    }
    
    /**
     * Calculate standard deviation of residuals
     */
    private function calculateStandardDeviation($data, $trend, $seasonality)
    {
        // Sort data by timestamp to get correct indices
        usort($data, function($a, $b) {
            return $a['t'] <=> $b['t'];
        });
        
        $residuals = [];
        
        foreach ($data as $index => $point) {
            // Use month index (0, 1, 2, ...) for trend calculation
            $expected = $trend['intercept'] + ($trend['slope'] * $index);
            $seasonal = isset($seasonality[$point['month']]) ? $seasonality[$point['month']] : 0;
            $predicted = $expected + $seasonal;
            $residuals[] = $point['y'] - $predicted;
        }
        
        if (count($residuals) < 2) {
            return 0;
        }
        
        $mean = array_sum($residuals) / count($residuals);
        $variance = 0;
        foreach ($residuals as $residual) {
            $variance += pow($residual - $mean, 2);
        }
        $variance /= (count($residuals) - 1);
        
        return sqrt($variance);
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

    /**
     * Get actual vs forecasted monthly demand data for line chart
     */
    private function getActualVsForecastedData(int $year, int $month, ?int $categoryFilter = null)
    {
        $data = [
            'labels' => [],
            'datasets' => []
        ];
        
        // Get actual monthly sales data (from sales table, grouped by month)
        $actualSales = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->when($categoryFilter !== null, function ($q) use ($categoryFilter) {
                $q->where('products.category_id', $categoryFilter);
            })
            ->select(
                DB::raw('YEAR(sales.created_at) as year'),
                DB::raw('MONTH(sales.created_at) as month'),
                DB::raw('DATE_FORMAT(sales.created_at, "%Y-%m") as month_key'),
                DB::raw('SUM(sale_items.quantity) as total_quantity')
            )
            ->groupBy('year', 'month', 'month_key')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();
        
        // Get forecasted data (monthly forecasts)
        $forecasts = DemandForecast::with('product')
            ->when($categoryFilter !== null, function ($q) use ($categoryFilter) {
                $q->whereHas('product', function($query) use ($categoryFilter) {
                    $query->where('category_id', $categoryFilter);
                });
            })
            ->select(
                DB::raw('YEAR(forecast_date) as year'),
                DB::raw('MONTH(forecast_date) as month'),
                DB::raw('DATE_FORMAT(forecast_date, "%Y-%m") as month_key'),
                DB::raw('SUM(predicted_demand) as total_demand')
            )
            ->groupBy('year', 'month', 'month_key')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();
        
        // Combine and create labels (last 12 months + forecast months)
        $allMonths = [];
        foreach ($actualSales as $sale) {
            $monthKey = $sale->month_key;
            $allMonths[$monthKey] = date('M Y', strtotime($monthKey . '-01'));
        }
        foreach ($forecasts as $forecast) {
            $monthKey = $forecast->month_key;
            if (!isset($allMonths[$monthKey])) {
                $allMonths[$monthKey] = date('M Y', strtotime($monthKey . '-01'));
            }
        }
        ksort($allMonths);
        // Limit to last 12 months of actual + all forecasts
        $allMonthsArray = array_slice($allMonths, -12, null, true);
        foreach ($forecasts as $forecast) {
            $monthKey = $forecast->month_key;
            if (!isset($allMonthsArray[$monthKey])) {
                $allMonthsArray[$monthKey] = date('M Y', strtotime($monthKey . '-01'));
            }
        }
        ksort($allMonthsArray);
        $data['labels'] = array_values($allMonthsArray);
        
        // Build actual sales dataset
        $actualData = [];
        foreach ($allMonthsArray as $monthKey => $label) {
            $sale = $actualSales->first(function($item) use ($monthKey) {
                return $item->month_key === $monthKey;
            });
            $actualData[] = $sale ? (float) $sale->total_quantity : null;
        }
        
        // Build forecasted dataset
        $forecastData = [];
        foreach ($allMonthsArray as $monthKey => $label) {
            $forecast = $forecasts->first(function($item) use ($monthKey) {
                return $item->month_key === $monthKey;
            });
            $forecastData[] = $forecast ? (float) $forecast->total_demand : null;
        }
        
        $data['datasets'] = [
            [
                'label' => 'Actual Demand',
                'data' => $actualData,
                'borderColor' => 'rgb(54, 162, 235)',
                'backgroundColor' => 'rgba(54, 162, 235, 0.1)',
                'tension' => 0.4,
                'spanGaps' => true
            ],
            [
                'label' => 'Forecasted Demand',
                'data' => $forecastData,
                'borderColor' => 'rgb(255, 99, 132)',
                'backgroundColor' => 'rgba(255, 99, 132, 0.1)',
                'borderDash' => [5, 5],
                'tension' => 0.4,
                'spanGaps' => true
            ]
        ];
        
        return $data;
    }
    
    /**
     * Get forecasted demand trend per product data for line chart
     * Shows forecasted demand over time (months) for top products
     */
    private function getForecastPerProductData(int $year, int $month, ?int $categoryFilter = null)
    {
        $data = [
            'labels' => [],
            'datasets' => []
        ];
        
        // Get all forecast months for the selected year
        $forecastMonths = DemandForecast::whereYear('forecast_date', $year)
            ->select(DB::raw('DISTINCT MONTH(forecast_date) as month'))
            ->orderBy('month', 'asc')
            ->pluck('month')
            ->toArray();
        
        if (empty($forecastMonths)) {
            return $data;
        }
        
        // Create month labels
        $monthLabels = [];
        foreach ($forecastMonths as $m) {
            $monthLabels[] = date('M Y', mktime(0, 0, 0, $m, 1, $year));
        }
        $data['labels'] = $monthLabels;
        
        // Get top products by total forecasted demand for the selected year
        $topProducts = DemandForecast::with('product')
            ->whereYear('forecast_date', $year)
            ->when($categoryFilter !== null, function ($q) use ($categoryFilter) {
                $q->whereHas('product', function($query) use ($categoryFilter) {
                    $query->where('category_id', $categoryFilter);
                });
            })
            ->select('product_id', DB::raw('SUM(predicted_demand) as total_demand'))
            ->groupBy('product_id')
            ->orderBy('total_demand', 'desc')
            ->limit(10) // Limit to top 10 products to avoid clutter
            ->get();
        
        if ($topProducts->isEmpty()) {
            return $data;
        }
        
        // Generate color palette for products
        $colors = [
            ['border' => 'rgb(75, 192, 192)', 'bg' => 'rgba(75, 192, 192, 0.1)'],
            ['border' => 'rgb(255, 99, 132)', 'bg' => 'rgba(255, 99, 132, 0.1)'],
            ['border' => 'rgb(54, 162, 235)', 'bg' => 'rgba(54, 162, 235, 0.1)'],
            ['border' => 'rgb(255, 206, 86)', 'bg' => 'rgba(255, 206, 86, 0.1)'],
            ['border' => 'rgb(153, 102, 255)', 'bg' => 'rgba(153, 102, 255, 0.1)'],
            ['border' => 'rgb(255, 159, 64)', 'bg' => 'rgba(255, 159, 64, 0.1)'],
            ['border' => 'rgb(199, 199, 199)', 'bg' => 'rgba(199, 199, 199, 0.1)'],
            ['border' => 'rgb(83, 102, 255)', 'bg' => 'rgba(83, 102, 255, 0.1)'],
            ['border' => 'rgb(99, 255, 132)', 'bg' => 'rgba(99, 255, 132, 0.1)'],
            ['border' => 'rgb(255, 99, 255)', 'bg' => 'rgba(255, 99, 255, 0.1)'],
        ];
        
        $colorIndex = 0;
        
        // Build dataset for each product
        foreach ($topProducts as $productForecast) {
            if (!$productForecast->product) {
                continue;
            }
            
            $productData = [];
            
            // Get forecasted demand for each month
            foreach ($forecastMonths as $m) {
                $monthForecast = DemandForecast::where('product_id', $productForecast->product_id)
                    ->whereYear('forecast_date', $year)
                    ->whereMonth('forecast_date', $m)
                    ->sum('predicted_demand');
                
                $productData[] = (float) $monthForecast;
            }
            
            $color = $colors[$colorIndex % count($colors)];
            $colorIndex++;
            
            $data['datasets'][] = [
                'label' => $productForecast->product->name,
                'data' => $productData,
                'borderColor' => $color['border'],
                'backgroundColor' => $color['bg'],
                'tension' => 0.4,
                'fill' => false
            ];
        }
        
        return $data;
    }
    
    /**
     * Get categories that have sales data (data-driven - only categories with actual sales appear)
     * This ensures categories without sales data don't appear in the selector
     */
    private function getCategoriesWithSalesData()
    {
        // Get categories that have at least one sale_item record
        // This is data-driven: categories only appear if they have sales
        return Category::whereHas('products', function($query) {
                $query->whereHas('saleItems');
            })
            ->orderBy('name')
            ->get();
    }
    
    /**
     * Get category trend data (Actual vs Forecasted Monthly Demand for a specific category)
     * Data-driven: only works with categories that have sales data
     */
    private function getCategoryTrendData(int $categoryId)
    {
        $data = [
            'labels' => [],
            'datasets' => [],
            'category_id' => $categoryId
        ];
        
        // Get category name
        $category = Category::find($categoryId);
        if (!$category) {
            return $data;
        }
        $data['category_name'] = $category->name;
        
        // Get actual monthly sales data for this category (from sales table, grouped by month)
        $actualSales = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('products.category_id', $categoryId)
            ->select(
                DB::raw('YEAR(sales.created_at) as year'),
                DB::raw('MONTH(sales.created_at) as month'),
                DB::raw('DATE_FORMAT(sales.created_at, "%Y-%m") as month_key'),
                DB::raw('SUM(sale_items.quantity) as total_quantity')
            )
            ->groupBy('year', 'month', 'month_key')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();
        
        // Get forecasted data for this category (monthly forecasts)
        $forecasts = DemandForecast::with('product')
            ->whereHas('product', function($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->select(
                DB::raw('YEAR(forecast_date) as year'),
                DB::raw('MONTH(forecast_date) as month'),
                DB::raw('DATE_FORMAT(forecast_date, "%Y-%m") as month_key'),
                DB::raw('SUM(predicted_demand) as total_demand')
            )
            ->groupBy('year', 'month', 'month_key')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();
        
        // Combine and create labels (last 12 months of actual + all forecasts)
        $allMonths = [];
        foreach ($actualSales as $sale) {
            $monthKey = $sale->month_key;
            $allMonths[$monthKey] = date('M Y', strtotime($monthKey . '-01'));
        }
        foreach ($forecasts as $forecast) {
            $monthKey = $forecast->month_key;
            if (!isset($allMonths[$monthKey])) {
                $allMonths[$monthKey] = date('M Y', strtotime($monthKey . '-01'));
            }
        }
        ksort($allMonths);
        // Limit to last 12 months of actual + all forecasts
        $allMonthsArray = array_slice($allMonths, -12, null, true);
        foreach ($forecasts as $forecast) {
            $monthKey = $forecast->month_key;
            if (!isset($allMonthsArray[$monthKey])) {
                $allMonthsArray[$monthKey] = date('M Y', strtotime($monthKey . '-01'));
            }
        }
        ksort($allMonthsArray);
        $data['labels'] = array_values($allMonthsArray);
        
        // Build actual sales dataset
        $actualData = [];
        foreach ($allMonthsArray as $monthKey => $label) {
            $sale = $actualSales->first(function($item) use ($monthKey) {
                return $item->month_key === $monthKey;
            });
            $actualData[] = $sale ? (float) $sale->total_quantity : null;
        }
        
        // Build forecasted dataset
        $forecastData = [];
        foreach ($allMonthsArray as $monthKey => $label) {
            $forecast = $forecasts->first(function($item) use ($monthKey) {
                return $item->month_key === $monthKey;
            });
            $forecastData[] = $forecast ? (float) $forecast->total_demand : null;
        }
        
        $data['datasets'] = [
            [
                'label' => 'Actual Demand',
                'data' => $actualData,
                'borderColor' => 'rgb(54, 162, 235)',
                'backgroundColor' => 'rgba(54, 162, 235, 0.1)',
                'tension' => 0.4,
                'spanGaps' => true
            ],
            [
                'label' => 'Forecasted Demand',
                'data' => $forecastData,
                'borderColor' => 'rgb(255, 99, 132)',
                'backgroundColor' => 'rgba(255, 99, 132, 0.1)',
                'borderDash' => [5, 5],
                'tension' => 0.4,
                'spanGaps' => true
            ]
        ];
        
        return $data;
    }
    
    /**
     * Get forecasted demand vs inventory over time data for line chart
     * Shows forecasted demand trend and current inventory (constant reference line) over months
     */
    private function getForecastVsInventoryData(int $year, int $month, ?int $categoryFilter = null)
    {
        $data = [
            'labels' => [],
            'datasets' => []
        ];
        
        // Get all forecast months for the selected year
        $forecastMonths = DemandForecast::whereYear('forecast_date', $year)
            ->select(DB::raw('DISTINCT MONTH(forecast_date) as month'))
            ->orderBy('month', 'asc')
            ->pluck('month')
            ->toArray();
        
        if (empty($forecastMonths)) {
            return $data;
        }
        
        // Create month labels
        $monthLabels = [];
        foreach ($forecastMonths as $m) {
            $monthLabels[] = date('M Y', mktime(0, 0, 0, $m, 1, $year));
        }
        $data['labels'] = $monthLabels;
        
        // Get products with forecasts for the selected year
        $products = Product::with(['demandForecasts' => function ($q) use ($year) {
                $q->whereYear('forecast_date', $year);
            }])
            ->when($categoryFilter !== null, function ($q) use ($categoryFilter) {
                $q->where('category_id', $categoryFilter);
            })
            ->whereHas('demandForecasts', function ($q) use ($year) {
                $q->whereYear('forecast_date', $year);
            })
            ->orderBy('name')
            ->limit(1) // Show aggregate for all products (or single product if filtered)
            ->get();
        
        if ($products->isEmpty()) {
            return $data;
        }
        
        // Aggregate forecasted demand across all products for each month
        $forecastData = [];
        $inventoryValue = 0;
        
        foreach ($forecastMonths as $m) {
            $monthForecast = DemandForecast::when($categoryFilter !== null, function ($q) use ($categoryFilter) {
                    $q->whereHas('product', function($query) use ($categoryFilter) {
                        $query->where('category_id', $categoryFilter);
                    });
                })
                ->whereYear('forecast_date', $year)
                ->whereMonth('forecast_date', $m)
                ->sum('predicted_demand');
            
            $forecastData[] = (float) $monthForecast;
        }
        
        // Get average inventory (constant reference line)
        foreach ($products as $product) {
            $inventoryValue += (float) $product->quantity;
        }
        $inventoryValue = $inventoryValue / count($products);
        
        // Create constant inventory line (same value for all months)
        $inventoryData = array_fill(0, count($forecastMonths), $inventoryValue);
        
        $data['datasets'] = [
            [
                'label' => 'Forecasted Demand',
                'data' => $forecastData,
                'borderColor' => 'rgb(255, 99, 132)',
                'backgroundColor' => 'rgba(255, 99, 132, 0.1)',
                'tension' => 0.4,
                'fill' => false
            ],
            [
                'label' => 'Current Inventory (Reference)',
                'data' => $inventoryData,
                'borderColor' => 'rgb(54, 162, 235)',
                'backgroundColor' => 'rgba(54, 162, 235, 0.1)',
                'borderDash' => [5, 5],
                'tension' => 0,
                'fill' => false
            ]
        ];
        
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
