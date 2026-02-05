# Demand Forecasting Methods - Complete Explanation

This document explains each method used in the demand forecasting system, including formulas and connected code.

---

## Table of Contents

1. [generateForecastUsingTrendProjection()](#1-generateforecastusingtrendprojection)
2. [calculateLinearRegression()](#2-calculatelinearregression)
3. [getMonthlyPattern()](#3-getmonthlypattern)
4. [getActualVsForecastedData()](#4-getactualvsforecasteddata)
5. [getForecastPerProductData()](#5-getforecastperproductdata)
6. [getForecastVsInventoryData()](#6-getforecastvsinventorydata)
7. [getCategoryTrendData()](#7-getcategorytrenddata)
8. [getCategoriesWithSalesData()](#8-getcategorieswithsalesdata)
9. [getForecastDataForGraph()](#9-getforecastdataforgraph)
10. [buildGraphDataForCategory()](#10-buildgraphdataforcategory)

---

## 1. generateForecastUsingTrendProjection()

### Purpose
Main method that generates demand forecasts for products using linear regression trend projection. Creates daily forecasts for multiple years.

### Formula
The final forecast uses this formula:
```
predicted_demand = max(1, round((intercept + slope × daysFromStart) × seasonalFactor × weekendFactor))
```

Where:
- `intercept` = Base demand level (from linear regression)
- `slope` = Daily trend (units per day)
- `daysFromStart` = Days from first historical date to forecast date
- `seasonalFactor` = Monthly pattern adjustment (from `getMonthlyPattern()`)
- `weekendFactor` = 0.85 for weekends, 1.0 for weekdays

### Connected Code
**Location:** Lines 180-316

**Key Steps:**
1. **Collect Historical Data** (Lines 189-220)
   ```php
   // Get sales data aggregated by date
   $historicalSales = SaleItem::where('product_id', $product->id)
       ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(quantity) as total'))
       ->groupBy('date')
       ->get();
   
   // Get inventory "out" movements
   $historicalOutMovements = InventoryMovement::where('product_id', $product->id)
       ->where('type', 'out')
       ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(quantity) as total'))
       ->groupBy('date')
       ->get();
   
   // Combine into daily demand
   $dailyDemand[$date] = sales_total + inventory_out_total
   ```

2. **Prepare Time Series** (Lines 227-238)
   ```php
   $firstDate = min(array_keys($dailyDemand));
   $firstTimestamp = strtotime($firstDate);
   
   foreach ($dailyDemand as $date => $demand) {
       $daysSinceStart = (strtotime($date) - $firstTimestamp) / 86400;
       $timeSeries[] = ['x' => $daysSinceStart, 'y' => $demand];
   }
   ```

3. **Calculate Trend** (Line 242)
   ```php
   $trend = $this->calculateLinearRegression($timeSeries);
   $slope = $trend['slope'];
   $intercept = $trend['intercept'];
   $rSquared = $trend['r_squared'];
   ```

4. **Generate Forecasts** (Lines 252-310)
   ```php
   for ($yearOffset = 0; $yearOffset < $yearCount; $yearOffset++) {
       $forecastYear = $startYear + $yearOffset;
       
       for ($month = 1; $month <= 12; $month++) {
           for ($day = 1; $day <= $daysInMonth; $day++) {
               // Calculate days from start
               $daysFromStart = ($forecastTimestamp - $firstTimestamp) / 86400;
               
               // Base projection
               $baseProjection = $intercept + ($slope * $daysFromStart);
               
               // Apply adjustments
               $seasonalFactor = $this->getMonthlyPattern($dailyDemand, $month);
               $weekendFactor = ($dayOfWeek == 0 || $dayOfWeek == 6) ? 0.85 : 1.0;
               
               // Final prediction
               $predicted = max(1, round($baseProjection * $seasonalFactor * $weekendFactor));
               
               // Calculate confidence
               $confidence = min(95, round(50 + ($rSquared * 30) + min(10, $dataPoints / 10), 1));
               
               // Save to database
               DemandForecast::create([...]);
           }
       }
   }
   ```

### Example Calculation
- First historical date: Jan 1, 2025
- Forecast date: Jan 1, 2026
- `daysFromStart` = 365 days
- `slope` = 0.5 units/day
- `intercept` = 100 units
- `seasonalFactor` = 1.2 (January is 20% above average)
- `weekendFactor` = 1.0 (Jan 1, 2026 is a weekday)

**Calculation:**
```
baseProjection = 100 + (0.5 × 365) = 282.5
predicted = max(1, round(282.5 × 1.2 × 1.0)) = 339 units
```

---

## 2. calculateLinearRegression()

### Purpose
Calculates the linear regression trend line (slope and intercept) from time series data using Ordinary Least Squares (OLS) method.

### Formula

**Slope (b):**
\[
b = \frac{n\sum xy - (\sum x)(\sum y)}{n\sum x^2 - (\sum x)^2}
\]

**Intercept (a):**
\[
a = \bar{y} - b\bar{x}
\]

Where:
- \( n \) = number of data points
- \( \bar{x} = \frac{\sum x}{n} \) (mean of x)
- \( \bar{y} = \frac{\sum y}{n} \) (mean of y)

**R-Squared (Goodness of Fit):**
\[
R^2 = 1 - \frac{SS_{res}}{SS_{tot}}
\]

Where:
- \( SS_{res} = \sum (y_i - \hat{y}_i)^2 \) (Sum of Squares of Residuals)
- \( SS_{tot} = \sum (y_i - \bar{y})^2 \) (Total Sum of Squares)
- \( \hat{y}_i = a + bx_i \) (predicted value)

### Connected Code
**Location:** Lines 323-380

**Step-by-Step:**

1. **Calculate Sums** (Lines 329-345)
   ```php
   $n = count($timeSeries);
   $sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0; $sumY2 = 0;
   
   foreach ($timeSeries as $point) {
       $x = $point['x'];  // days since start
       $y = $point['y'];  // demand
       
       $sumX += $x;
       $sumY += $y;
       $sumXY += $x * $y;
       $sumX2 += $x * $x;
       $sumY2 += $y * $y;
   }
   ```

2. **Calculate Slope** (Lines 347-353)
   ```php
   $denominator = ($n * $sumX2) - ($sumX * $sumX);
   if ($denominator == 0) return null; // Cannot calculate
   
   $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
   ```

3. **Calculate Intercept** (Lines 355-358)
   ```php
   $meanX = $sumX / $n;
   $meanY = $sumY / $n;
   $intercept = $meanY - ($slope * $meanX);
   ```

4. **Calculate R-Squared** (Lines 360-373)
   ```php
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
   ```

5. **Return Results** (Lines 375-379)
   ```php
   return [
       'slope' => $slope,
       'intercept' => $intercept,
       'r_squared' => max(0, min(1, $rSquared)) // Clamp between 0 and 1
   ];
   ```

### Example Calculation
Given data points:
- Day 0: 100 units
- Day 7: 105 units
- Day 14: 110 units
- Day 21: 115 units

**Calculations:**
- n = 4
- Σx = 0 + 7 + 14 + 21 = 42
- Σy = 100 + 105 + 110 + 115 = 430
- Σxy = 0×100 + 7×105 + 14×110 + 21×115 = 0 + 735 + 1540 + 2415 = 4690
- Σx² = 0² + 7² + 14² + 21² = 0 + 49 + 196 + 441 = 686

**Slope:**
```
b = (4 × 4690 - 42 × 430) / (4 × 686 - 42²)
  = (18760 - 18060) / (2744 - 1764)
  = 700 / 980
  = 0.714 units/day
```

**Intercept:**
```
meanX = 42 / 4 = 10.5
meanY = 430 / 4 = 107.5
a = 107.5 - (0.714 × 10.5) = 107.5 - 7.5 = 100 units
```

**Result:** y = 100 + 0.714x

---

## 3. getMonthlyPattern()

### Purpose
Calculates seasonal adjustment factor for a specific month based on historical monthly patterns.

### Formula
\[
\text{monthlyPattern} = \frac{\text{avgDemandForMonth}}{\text{overallAvgDemand}}
\]

Where:
- `avgDemandForMonth` = Average daily demand for the target month
- `overallAvgDemand` = Average daily demand across all months

**Seasonal Factor:**
\[
\text{seasonalFactor} = \begin{cases}
\text{monthlyPattern} & \text{if monthlyPattern > 0} \\
1.0 & \text{otherwise}
\end{cases}
\]

### Connected Code
**Location:** Lines 386-415

**Step-by-Step:**

1. **Group by Month** (Lines 388-401)
   ```php
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
   ```

2. **Calculate Averages** (Lines 403-411)
   ```php
   $overallAvg = $overallTotal / $overallCount;
   
   if (isset($monthlyTotals[$targetMonth]) && $monthlyTotals[$targetMonth]['count'] > 0) {
       $monthAvg = $monthlyTotals[$targetMonth]['sum'] / $monthlyTotals[$targetMonth]['count'];
       return $overallAvg > 0 ? $monthAvg / $overallAvg : 1.0;
   }
   ```

3. **Return Default** (Line 414)
   ```php
   return 1.0; // Default: no seasonal adjustment
   ```

### Example Calculation
Historical data:
- January: 10 days, total 1200 units → avg = 120 units/day
- February: 8 days, total 800 units → avg = 100 units/day
- March: 10 days, total 1500 units → avg = 150 units/day
- Overall: 28 days, total 3500 units → avg = 125 units/day

**For January forecast:**
```
monthlyPattern = 120 / 125 = 0.96 (4% below average)
```

**For March forecast:**
```
monthlyPattern = 150 / 125 = 1.2 (20% above average)
```

---

## 4. getActualVsForecastedData()

### Purpose
Prepares data for line chart comparing actual sales vs forecasted demand over time (monthly aggregation).

### Formula
**Monthly Aggregation:**
\[
\text{monthlyTotal} = \sum_{day=1}^{daysInMonth} \text{dailyValue}
\]

### Connected Code
**Location:** Lines 852-957

**Step-by-Step:**

1. **Get Actual Sales** (Lines 860-874)
   ```php
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
   ```

2. **Get Forecasted Data** (Lines 877-892)
   ```php
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
   ```

3. **Combine and Create Labels** (Lines 894-916)
   ```php
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
   ```

4. **Build Datasets** (Lines 918-954)
   ```php
   // Actual sales dataset
   $actualData = [];
   foreach ($allMonthsArray as $monthKey => $label) {
       $sale = $actualSales->first(function($item) use ($monthKey) {
           return $item->month_key === $monthKey;
       });
       $actualData[] = $sale ? (float) $sale->total_quantity : null;
   }
   
   // Forecasted dataset
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
   ```

### Output Format
```json
{
  "labels": ["Jan 2025", "Feb 2025", ..., "Jan 2026", "Feb 2026"],
  "datasets": [
    {
      "label": "Actual Demand",
      "data": [1200, 1500, ..., null, null],
      "borderColor": "rgb(54, 162, 235)"
    },
    {
      "label": "Forecasted Demand",
      "data": [null, null, ..., 1800, 2000],
      "borderColor": "rgb(255, 99, 132)",
      "borderDash": [5, 5]
    }
  ]
}
```

---

## 5. getForecastPerProductData()

### Purpose
Prepares data for line chart showing forecasted demand trends for top 10 products over months.

### Formula
**Monthly Total per Product:**
\[
\text{monthlyTotal} = \sum_{day=1}^{daysInMonth} \text{predicted_demand}
\]

### Connected Code
**Location:** Lines 963-1054

**Step-by-Step:**

1. **Get Forecast Months** (Lines 970-985)
   ```php
   $forecastMonths = DemandForecast::whereYear('forecast_date', $year)
       ->select(DB::raw('DISTINCT MONTH(forecast_date) as month'))
       ->orderBy('month', 'asc')
       ->pluck('month')
       ->toArray();
   
   // Create month labels
   $monthLabels = [];
   foreach ($forecastMonths as $m) {
       $monthLabels[] = date('M Y', mktime(0, 0, 0, $m, 1, $year));
   }
   $data['labels'] = $monthLabels;
   ```

2. **Get Top Products** (Lines 988-1004)
   ```php
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
       ->limit(10) // Top 10 products
       ->get();
   ```

3. **Build Dataset for Each Product** (Lines 1022-1051)
   ```php
   foreach ($topProducts as $productForecast) {
       if (!$productForecast->product) continue;
       
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
   ```

### Output Format
```json
{
  "labels": ["Jan 2026", "Feb 2026", "Mar 2026", ...],
  "datasets": [
    {
      "label": "Product A",
      "data": [500, 550, 600, ...],
      "borderColor": "rgb(75, 192, 192)"
    },
    {
      "label": "Product B",
      "data": [300, 320, 340, ...],
      "borderColor": "rgb(255, 99, 132)"
    },
    ...
  ]
}
```

---

## 6. getForecastVsInventoryData()

### Purpose
Prepares data for line chart comparing forecasted demand vs current inventory (constant reference line) over months.

### Formula
**Monthly Forecast Total:**
\[
\text{monthlyForecast} = \sum_{\text{all products}} \sum_{day=1}^{daysInMonth} \text{predicted_demand}
\]

**Average Inventory:**
\[
\text{avgInventory} = \frac{\sum_{\text{all products}} \text{current_quantity}}{\text{number of products}}
\]

### Connected Code
**Location:** Lines 1060-1150

**Step-by-Step:**

1. **Get Forecast Months** (Lines 1067-1083)
   ```php
   $forecastMonths = DemandForecast::whereYear('forecast_date', $year)
       ->select(DB::raw('DISTINCT MONTH(forecast_date) as month'))
       ->orderBy('month', 'asc')
       ->pluck('month')
       ->toArray();
   
   // Create month labels
   $monthLabels = [];
   foreach ($forecastMonths as $m) {
       $monthLabels[] = date('M Y', mktime(0, 0, 0, $m, 1, $year));
   }
   $data['labels'] = $monthLabels;
   ```

2. **Get Products** (Lines 1085-1101)
   ```php
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
       ->limit(1) // Aggregate for all products
       ->get();
   ```

3. **Calculate Monthly Forecasts** (Lines 1103-1118)
   ```php
   $forecastData = [];
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
   ```

4. **Calculate Average Inventory** (Lines 1120-1127)
   ```php
   $inventoryValue = 0;
   foreach ($products as $product) {
       $inventoryValue += (float) $product->quantity;
   }
   $inventoryValue = $inventoryValue / count($products);
   
   // Create constant inventory line (same value for all months)
   $inventoryData = array_fill(0, count($forecastMonths), $inventoryValue);
   ```

5. **Build Datasets** (Lines 1129-1147)
   ```php
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
   ```

### Output Format
```json
{
  "labels": ["Jan 2026", "Feb 2026", "Mar 2026", ...],
  "datasets": [
    {
      "label": "Forecasted Demand",
      "data": [5000, 5500, 6000, ...],
      "borderColor": "rgb(255, 99, 132)"
    },
    {
      "label": "Current Inventory (Reference)",
      "data": [3000, 3000, 3000, ...],
      "borderColor": "rgb(54, 162, 235)",
      "borderDash": [5, 5]
    }
  ]
}
```

---

## 7. getCategoryTrendData()

### Purpose
Prepares data for line chart showing actual vs forecasted demand for a specific category over time.

### Formula
Same as `getActualVsForecastedData()` but filtered by category.

### Connected Code
**Location:** Lines 1171-1280

**Key Difference from `getActualVsForecastedData()`:**
- Filters by `category_id` in both actual sales and forecasts queries
- Returns category-specific data only

**Actual Sales Query** (Lines 1187-1199):
```php
$actualSales = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
    ->join('products', 'sale_items.product_id', '=', 'products.id')
    ->where('products.category_id', $categoryId)  // Category filter
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
```

**Forecast Query** (Lines 1202-1215):
```php
$forecasts = DemandForecast::with('product')
    ->whereHas('product', function($query) use ($categoryId) {
        $query->where('category_id', $categoryId);  // Category filter
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
```

---

## 8. getCategoriesWithSalesData()

### Purpose
Returns list of categories that have at least one sale_item record (data-driven approach).

### Connected Code
**Location:** Lines 1156-1165

```php
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
```

**Logic:**
- Uses Laravel's `whereHas()` to filter categories
- Only includes categories where:
  1. Category has products (`whereHas('products')`)
  2. Those products have sale items (`whereHas('saleItems')`)

---

## 9. getForecastDataForGraph()

### Purpose
Prepares forecast data formatted for graphs, creating separate graphs for each category. Handles both forecast data and sales data fallback.

### Connected Code
**Location:** Lines 422-537

**Key Features:**
1. **Category Filter Support** (Lines 444-450)
   ```php
   if ($categoryFilter !== null) {
       $category = Category::find($categoryFilter);
       $categoryName = $category ? $category->name : 'Category';
       $categoryColors = $this->getCategoryColors();
       
       return $this->buildGraphDataForCategory($forecasts, $categoryFilter, $categoryName, $categoryColors[0]);
   }
   ```

2. **Top 20 Products** (Lines 453-494)
   ```php
   // Group by product and calculate total forecast
   $products = $forecasts->groupBy('product_id');
   $productTotals = [];
   
   foreach ($products as $productId => $productForecasts) {
       $product = $productForecasts->first()->product;
       $total = $productForecasts->sum('predicted_demand');
       $productTotals[$productId] = [
           'product' => $product,
           'forecasts' => $productForecasts,
           'total' => $total,
           'category_id' => $product->category_id,
           'category_name' => $product->category->name
       ];
   }
   
   // Sort by total and take top 20
   uasort($productTotals, function($a, $b) {
       return $b['total'] <=> $a['total'];
   });
   $top20Products = array_slice($productTotals, 0, 20, true);
   ```

3. **Group by Category** (Lines 482-510)
   ```php
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
   ```

4. **Sales Data Fallback** (Lines 512-531)
   ```php
   // Check for categories with sales data but no forecasts
   $categoriesWithSales = $this->getCategoriesWithSales($year, $month);
   
   foreach ($categoriesWithSales as $categoryId => $categoryInfo) {
       if (isset($graphsByCategory[$categoryId])) {
           continue; // Already have graph
       }
       
       // Generate graph data from sales for this category
       $salesGraphData = $this->buildGraphDataFromSales($categoryId, $categoryInfo['category_name'], $year, $month, $categoryColors[$categoryColorIndex % count($categoryColors)]);
       
       if (!empty($salesGraphData['datasets'])) {
           $graphsByCategory[$categoryId] = $salesGraphData;
       }
   }
   ```

---

## 10. buildGraphDataForCategory()

### Purpose
Builds graph data structure for a specific category with multiple products as separate lines.

### Connected Code
**Location:** Lines 546-637

**Step-by-Step:**

1. **Group by Product** (Lines 559-573)
   ```php
   $products = $forecasts->groupBy('product_id');
   $productTotals = [];
   
   foreach ($products as $productId => $productForecasts) {
       $product = $productForecasts->first()->product;
       $total = $productForecasts->sum('predicted_demand');
       $productTotals[$productId] = [
           'product' => $product,
           'forecasts' => $productForecasts,
           'total' => $total
       ];
   }
   ```

2. **Collect All Days** (Lines 580-595)
   ```php
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
   ```

3. **Build Dataset for Each Product** (Lines 604-634)
   ```php
   $productIndex = 0;
   foreach ($productTotals as $productId => $productData) {
       $product = $productData['product'];
       $productForecasts = $productData['forecasts'];
       
       // Create color variation for each product
       $colorVariation = $this->getColorVariation($categoryColor, $productIndex);
       $productIndex++;
       
       // Create forecast map
       $forecastMap = [];
       foreach ($productForecasts as $forecast) {
           $day = $forecast->forecast_date->format('M d');
           $forecastMap[$day] = $forecast->predicted_demand;
       }
       
       // Build data array matching sorted labels
       $dataset = [
           'label' => $product->name,
           'data' => [],
           'borderColor' => $colorVariation['border'],
           'backgroundColor' => $colorVariation['bg'],
           'tension' => 0.4
       ];
       
       foreach ($data['labels'] as $day) {
           $dataset['data'][] = $forecastMap[$day] ?? 0;
       }
       
       $data['datasets'][] = $dataset;
   }
   ```

---

## Summary of Formulas

| Method | Formula |
|--------|---------|
| **Final Forecast** | `predicted = max(1, round((intercept + slope × daysFromStart) × seasonalFactor × weekendFactor))` |
| **Slope** | `b = (nΣxy - ΣxΣy) / (nΣx² - (Σx)²)` |
| **Intercept** | `a = ȳ - b x̄` |
| **R-Squared** | `R² = 1 - (SS_res / SS_tot)` |
| **Seasonal Factor** | `seasonalFactor = avgMonthDemand / avgOverallDemand` |
| **Weekend Factor** | `weekendFactor = 0.85 (weekend) or 1.0 (weekday)` |
| **Confidence** | `confidence = min(95, 50 + (R² × 30) + min(10, dataPoints / 10))` |

---

*Last Updated: Based on current implementation in `DemandForecastController.php`*

