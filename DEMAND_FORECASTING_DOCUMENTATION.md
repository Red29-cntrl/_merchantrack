# Demand Forecasting Module - Complete Documentation

## Overview
The demand forecasting module uses a **Trend Projection Method with Linear Regression** to predict future product demand based on historical sales and inventory movement data.

---

## 📁 Files Involved

### Backend Files
1. **`app/Http/Controllers/DemandForecastController.php`**
   - Main controller containing all forecasting logic
   - Key methods: `generateForecastUsingTrendProjection()`, `calculateLinearRegression()`, `getMonthlyPattern()`

2. **`app/DemandForecast.php`**
   - Eloquent model for `demand_forecasts` table
   - Relationships: `belongsTo(Product::class)`

3. **`database/migrations/2025_11_27_135522_create_demand_forecasts_table.php`**
   - Database schema for storing forecasts

### Frontend Files
4. **`resources/views/forecasts/index.blade.php`**
   - Main forecast display page with charts and tables

5. **`resources/views/forecasts/show.blade.php`**
   - Individual forecast detail page

---

## 🔄 Data Flow

### Step 1: Data Collection
**Source Tables:**
- `sale_items` - Historical sales transactions
- `inventory_movements` (type='out') - Inventory outbound movements

**Query Logic:**
```php
// Get daily sales aggregated by date
$historicalSales = SaleItem::where('product_id', $product->id)
    ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(quantity) as total'))
    ->groupBy('date')
    ->get();

// Get daily inventory out movements
$historicalOutMovements = InventoryMovement::where('product_id', $product->id)
    ->where('type', 'out')
    ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(quantity) as total'))
    ->groupBy('date')
    ->get();
```

**Combined Daily Demand:**
```php
$dailyDemand[$date] = sales_total + inventory_out_total
```

### Step 2: Forecast Generation Trigger
**Location:** `DemandForecastController::index()`

**Condition:**
- Checks if forecasts exist for year 2026
- If `count == 0`, automatically calls `generateForecastUsingTrendProjection()`

### Step 3: Forecast Calculation
**Minimum Requirements:**
- At least **7 days** of historical data
- Product must be `is_active = true`

**Forecast Horizon:**
- Generates forecasts for **all 365 days** in year 2026
- One forecast record per day stored in `demand_forecasts` table

---

## 📐 Formulas Used

### 1. Linear Regression (Trend Calculation)

**Formula:** \( y = a + bx \)

Where:
- \( y \) = demand (dependent variable)
- \( x \) = days since first historical date (independent variable)
- \( a \) = intercept
- \( b \) = slope (daily trend)

**Slope Calculation:**
\[
b = \frac{n\sum xy - (\sum x)(\sum y)}{n\sum x^2 - (\sum x)^2}
\]

**Intercept Calculation:**
\[
a = \bar{y} - b\bar{x}
\]

Where:
- \( n \) = number of data points
- \( \bar{x} = \frac{\sum x}{n} \) (mean of x)
- \( \bar{y} = \frac{\sum y}{n} \) (mean of y)

**Implementation:**
```php
$slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
$intercept = $meanY - ($slope * $meanX);
```

### 2. R-Squared (Goodness of Fit)

**Formula:**
\[
R^2 = 1 - \frac{SS_{res}}{SS_{tot}}
\]

Where:
- \( SS_{res} = \sum (y_i - \hat{y}_i)^2 \) (Sum of Squares of Residuals)
- \( SS_{tot} = \sum (y_i - \bar{y})^2 \) (Total Sum of Squares)
- \( \hat{y}_i = a + bx_i \) (predicted value)

**Range:** 0 to 1 (higher = better fit)

### 3. Base Projection

**Formula:**
\[
\text{baseProjection} = a + b \cdot \text{daysFromStart}
\]

Where `daysFromStart` = number of days from first historical date to forecast date.

### 4. Seasonal Adjustment Factor

**Monthly Pattern Calculation:**
\[
\text{monthlyPattern} = \frac{\text{avgDemandForMonth}}{\text{overallAvgDemand}}
\]

**Seasonal Factor:**
\[
\text{seasonalFactor} = \begin{cases}
\text{monthlyPattern} & \text{if monthlyPattern > 0} \\
1.0 & \text{otherwise}
\end{cases}
\]

### 5. Weekend Adjustment

**Weekend Factor:**
\[
\text{weekendFactor} = \begin{cases}
0.85 & \text{if day is Saturday (6) or Sunday (0)} \\
1.0 & \text{otherwise}
\end{cases}
\]

### 6. Final Forecast Prediction

**Formula:**
\[
\text{predicted} = \max(1, \text{round}(\text{baseProjection} \times \text{seasonalFactor} \times \text{weekendFactor}))
\]

**Components:**
1. Base trend projection (linear regression)
2. Monthly seasonal adjustment
3. Weekend reduction (15% lower on weekends)
4. Minimum value of 1 unit

### 7. Confidence Level Calculation

**Formula:**
\[
\text{confidence} = \min(95, \text{round}(\text{baseConfidence} + \text{dataConfidence}, 1))
\]

Where:
- \( \text{baseConfidence} = \min(90, 50 + (R^2 \times 30)) \)
- \( \text{dataConfidence} = \min(10, \frac{\text{dataPoints}}{10}) \)

**Breakdown:**
- Base confidence: 50% minimum, up to +30% from R² (max 90%)
- Data confidence: up to +10% based on number of historical data points
- Final: capped at 95%

**Note:** Confidence represents **estimated reliability**, not guaranteed probability.

---

## 💾 Database Storage

### Table: `demand_forecasts`

**Schema:**
```sql
- id (primary key)
- product_id (foreign key → products.id)
- forecast_date (date)
- predicted_demand (integer)
- confidence_level (decimal 5,2)
- method (text)
- historical_data (json)
- created_at, updated_at (timestamps)
```

**Example Record:**
```json
{
  "product_id": 1,
  "forecast_date": "2026-01-15",
  "predicted_demand": 25,
  "confidence_level": 78.5,
  "method": "Trend Projection Method (Linear Regression)",
  "historical_data": {
    "slope": 0.1234,
    "intercept": 15.67,
    "r_squared": 0.8567,
    "data_points": 45,
    "trend_direction": "increasing"
  }
}
```

---

## 📊 Visualization (Line Graphs)

### Graph 1: Actual vs Forecasted Monthly Demand
- **Type:** Line chart
- **X-axis:** Months
- **Y-axis:** Demand (Units)
- **Lines:** Actual Demand (blue), Forecasted Demand (red dashed)

### Graph 2: Forecasted Demand Trend per Product
- **Type:** Line chart
- **X-axis:** Months (Jan-Dec 2026)
- **Y-axis:** Forecasted Demand (Units)
- **Lines:** Top 10 products (one line per product, different colors)

### Graph 3: Forecasted Demand vs Inventory Over Time
- **Type:** Line chart
- **X-axis:** Months
- **Y-axis:** Quantity (Units)
- **Lines:** 
  - Forecasted Demand (red, solid)
  - Current Inventory Reference (blue, dashed)

### Graph 4: Category Product Trend
- **Type:** Line chart
- **X-axis:** Months
- **Y-axis:** Quantity (Units)
- **Lines:** Actual vs Forecasted by selected category

---

## 🔧 Key Functions

### 1. `generateForecastUsingTrendProjection()`
**Purpose:** Main entry point for forecast generation
**Process:**
1. Loops through all active products
2. Collects historical sales + inventory movements
3. Requires minimum 7 days of data
4. Calculates linear regression trend
5. Generates daily forecasts for the requested year (supports future years like 2027, 2028, ...)
6. Applies seasonal and weekend adjustments
7. Stores forecasts in database

### 2. `calculateLinearRegression($timeSeries)`
**Purpose:** Compute trend line using OLS regression
**Returns:** `['slope' => b, 'intercept' => a, 'r_squared' => R²]`
**Formula:** Standard least-squares regression

### 3. `getMonthlyPattern($dailyDemand, $targetMonth)`
**Purpose:** Calculate seasonal adjustment factor for a specific month
**Returns:** Multiplier factor (e.g., 1.2 = 20% higher than average)
**Formula:** `monthAvg / overallAvg`

---

## 🎯 Why You See Feb-Dec Results Even with Only January Sales

**Explanation:**
1. The system generates forecasts for **all days** in the selected forecast year (entire year)
2. Even if you only have sales in January, the linear regression **projects forward** using the trend
3. The formula `predicted = intercept + (slope × daysFromStart)` calculates demand for **every future day**
4. These forecasts are **stored in the database** (`demand_forecasts` table)
5. The line graphs **read from stored forecasts**, not from raw sales data

**Example:**
- Historical sales: Only January 2025
- System generates: Forecasts for Jan 1 → Dec 31 of the selected forecast year (one record per day)
- Graph displays: All 12 months because forecasts exist in database

---

## 📚 Libraries & Frameworks

### Backend
- **Laravel Framework** (PHP)
- **Laravel Eloquent ORM** (database queries)
- **Native PHP functions** (math, date handling)
- **No external ML libraries** (all calculations are custom PHP)

### Frontend
- **Chart.js 3.9.1** (visualization library)
- **Bootstrap 5** (UI framework)
- **Blade Templates** (Laravel templating)

---

## ⚠️ Important Notes

1. **Forecast Values:** Generated using mathematical formulas, not guaranteed accuracy
2. **Confidence Level:** Represents estimated reliability based on historical data quality, not probability
3. **Minimum Data:** Requires at least 7 days of historical data to generate forecasts
4. **Daily Granularity:** Forecasts are generated per day, but can be aggregated monthly for display
5. **No External Dependencies:** All forecasting logic is custom PHP implementation

---

## 🔍 Data Source Summary

**Historical Data Sources:**
- `sale_items.created_at` + `sale_items.quantity` → Daily sales totals
- `inventory_movements.created_at` + `inventory_movements.quantity` (type='out') → Daily outbound movements

**Combined:** `dailyDemand[date] = sales + inventory_out`

**Forecast Output:**
- Stored in `demand_forecasts` table
- One record per day for year 2026
- Includes: `predicted_demand`, `confidence_level`, `historical_data` (JSON)

---

## 📝 Code Location Reference

**Main Forecasting Logic:**
- File: `app/Http/Controllers/DemandForecastController.php`
- Method: `generateForecastUsingTrendProjection()` (lines ~118-254)
- Method: `calculateLinearRegression()` (lines ~261-318)
- Method: `getMonthlyPattern()` (lines ~324-353)

**Data Display:**
- File: `resources/views/forecasts/index.blade.php`
- Chart.js configurations in `@section('scripts')`

---

*Last Updated: Based on current codebase implementation*

