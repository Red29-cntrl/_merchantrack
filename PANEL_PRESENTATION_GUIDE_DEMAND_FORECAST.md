# Demand Forecast Module - Panel Presentation Guide

## 📋 Presentation Structure

1. [Introduction & Overview](#1-introduction--overview)
2. [Methodology & Academic Basis](#2-methodology--academic-basis)
3. [Core Formula & Mathematical Foundation](#3-core-formula--mathematical-foundation)
4. [System Components Explained](#4-system-components-explained)
5. [Filtering Features](#5-filtering-features)
6. [Visualization & Graphs](#6-visualization--graphs)
7. [Code Integration & Implementation](#7-code-integration--implementation)
8. [Conclusion & Summary](#8-conclusion--summary)

---

## 1. Introduction & Overview

### What is Demand Forecasting?

**Definition:**
Demand forecasting is the process of predicting future customer demand for products based on historical sales data and statistical analysis. It helps businesses optimize inventory levels, reduce stockouts, and make informed purchasing decisions.

### Purpose of This Module

The Demand Forecast Module provides:
- ✅ **Predictive Analytics**: Forecasts future product demand
- ✅ **Inventory Optimization**: Identifies optimal stock levels
- ✅ **Reorder Recommendations**: Suggests when to restock
- ✅ **Data-Driven Decisions**: Uses statistical methods for accuracy
- ✅ **Multi-Year Planning**: Forecasts for current year + 2 years ahead

### Key Features

1. **Automatic Forecast Generation** - Generates forecasts automatically when needed
2. **Daily Granularity** - Creates forecasts for each day
3. **Seasonal Adjustment** - Accounts for monthly patterns
4. **Confidence Indicators** - Shows forecast reliability
5. **Visual Analytics** - Multiple charts and graphs
6. **Advanced Filtering** - Filter by category, year, month, confidence
7. **Inventory Gap Analysis** - Compares forecast vs current stock

---

## 2. Methodology & Academic Basis

### Method Used: **Trend Projection with Linear Regression**

#### Method Classification:
- **Type**: Quantitative / Statistical Forecasting
- **Category**: Time Series Methods
- **Sub-type**: Trend Projection with Seasonal Adjustment
- **Formula**: Ordinary Least Squares (OLS) Regression

### Academic Foundation

#### Historical Origin:
- **Developed by**: Carl Friedrich Gauss (1795) and Adrien-Marie Legendre (1805)
- **Published**: Gauss's *"Theoria Motus Corporum Coelestium"* (1809)
- **Status**: One of the oldest and most fundamental statistical methods

#### Key Academic References:

1. **Gauss, C.F.** (1809)
   - *Theoria Motus Corporum Coelestium*
   - Origin of OLS regression method

2. **Box, G.E.P. & Jenkins, G.M.** (1976)
   - *Time Series Analysis: Forecasting and Control*
   - Time series forecasting theory

3. **Makridakis, S., Wheelwright, S.C., & Hyndman, R.J.** (1998)
   - *Forecasting: Methods and Applications* (3rd ed.)
   - Comprehensive forecasting methods

4. **Nahmias, S.** (2009)
   - *Production and Operations Analysis* (6th ed.)
   - Operations management forecasting

5. **Chopra, S. & Meindl, P.** (2016)
   - *Supply Chain Management: Strategy, Planning, and Operation* (6th ed.)
   - Supply chain forecasting applications

### Theoretical Framework

#### Classical Time Series Decomposition:
\[
Y_t = T_t + S_t + C_t + I_t
\]

Where:
- \( T_t \) = **Trend component** (captured by linear regression)
- \( S_t \) = **Seasonal component** (monthly patterns)
- \( C_t \) = **Cyclical component** (long-term cycles)
- \( I_t \) = **Irregular/Random component** (error term)

**Our Implementation:**
- Uses **Linear Regression** to capture trend (\( T_t \))
- Applies **Seasonal Adjustment** for monthly patterns (\( S_t \))
- Accounts for **Weekend Effects** (part of \( I_t \))

---

## 3. Core Formula & Mathematical Foundation

### 3.1 Linear Regression Formula

#### Basic Equation:
\[
y = a + bx + \epsilon
\]

Where:
- \( y \) = Dependent variable (demand)
- \( x \) = Independent variable (time in days)
- \( a \) = Intercept (base demand level)
- \( b \) = Slope (daily trend)
- \( \epsilon \) = Error term

### 3.2 Slope Calculation (OLS Method)

\[
b = \frac{n\sum xy - (\sum x)(\sum y)}{n\sum x^2 - (\sum x)^2}
\]

**Where:**
- \( n \) = Number of data points
- \( \sum xy \) = Sum of (x × y) products
- \( \sum x \) = Sum of x values
- \( \sum y \) = Sum of y values
- \( \sum x^2 \) = Sum of squared x values

### 3.3 Intercept Calculation

\[
a = \bar{y} - b\bar{x}
\]

**Where:**
- \( \bar{x} = \frac{\sum x}{n} \) (mean of x)
- \( \bar{y} = \frac{\sum y}{n} \) (mean of y)

### 3.4 Final Forecast Formula

\[
\text{Predicted Demand} = \max(1, \text{round}((\text{intercept} + \text{slope} \times \text{daysFromStart}) \times \text{seasonalFactor} \times \text{weekendFactor}))
\]

**Components:**

1. **Base Projection:**
   \[
   \text{baseProjection} = \text{intercept} + (\text{slope} \times \text{daysFromStart})
   \]

2. **Seasonal Factor:**
   \[
   \text{seasonalFactor} = \frac{\text{Average Demand for Month}}{\text{Overall Average Demand}}
   \]

3. **Weekend Factor:**
   \[
   \text{weekendFactor} = \begin{cases}
   0.85 & \text{if weekend (Saturday/Sunday)} \\
   1.0 & \text{if weekday}
   \end{cases}
   \]

### 3.5 R-Squared (Goodness of Fit)

\[
R^2 = 1 - \frac{SS_{res}}{SS_{tot}}
\]

**Where:**
- \( SS_{res} = \sum (y_i - \hat{y}_i)^2 \) (Sum of Squares of Residuals)
- \( SS_{tot} = \sum (y_i - \bar{y})^2 \) (Total Sum of Squares)
- \( \hat{y}_i = a + bx_i \) (predicted value)

**Interpretation:**
- \( R^2 = 1 \): Perfect fit
- \( R^2 = 0 \): No linear relationship
- Higher \( R^2 \) = Better model fit

### 3.6 Forecast Reliability (Confidence Level) Formula

\[
\text{Reliability} = \min(95, 50 + (R^2 \times 30) + \min(10, \frac{\text{dataPoints}}{10}))
\]

**⚠️ Important Note:**
This is a **custom reliability score**, NOT a standard statistical confidence interval. It should be interpreted as an **estimated reliability indicator** based on model quality and data adequacy.

**Components:**
- **Base**: 50% (minimum reliability score)
- **R² Contribution**: Up to 30% (based on model fit quality - well-established metric)
- **Data Points Contribution**: Up to 10% (based on sample size - recognized principle)

**Range:** 0% to 95%

**Academic Basis:**
- **R-Squared**: Well-established statistical measure (Draper & Smith, 1998; Montgomery et al., 2012)
- **Sample Size**: Recognized principle in statistics (Cochran, 1977; Kish, 1965)
- **Weighting System**: Heuristic approach (practical combination, not derived from statistical theory)

**Limitations:**
- ⚠️ Not a standard statistical confidence interval
- ⚠️ Does not represent probability of accuracy
- ⚠️ Arbitrary weights (30% and 10% are chosen, not derived)
- ⚠️ Does not account for forecast horizon or data quality

**For Panel Discussion:**
See `FORECAST_RELIABILITY_CRITICAL_ANALYSIS.md` for detailed analysis and recommendations.

---

## 4. System Components Explained

### 4.1 Month Forecast

#### Definition:
The **Month Forecast** represents the total predicted demand for a product in the selected month, calculated by summing all daily forecasts for that month.

#### Formula:
\[
\text{Month Forecast} = \sum_{day=1}^{\text{daysInMonth}} \text{Daily Forecast}_{day}
\]

#### Calculation Process:

**Step 1: Daily Forecast Calculation**
```php
// For each day in the month
$daysFromStart = (forecastTimestamp - firstHistoricalTimestamp) / 86400;
$baseProjection = $intercept + ($slope * $daysFromStart);
$seasonalFactor = getMonthlyPattern($dailyDemand, $month);
$weekendFactor = ($dayOfWeek == 0 || $dayOfWeek == 6) ? 0.85 : 1.0;
$dailyForecast = max(1, round($baseProjection * $seasonalFactor * $weekendFactor));
```

**Step 2: Monthly Aggregation**
```php
// Sum all daily forecasts for the month
$monthStart = sprintf('%04d-%02d-01', $year, $selectedMonth);
$monthForecast = $product->demandForecasts
    ->filter(function($forecast) use ($monthStart) {
        return $forecast->forecast_date->format('Y-m-d') === $monthStart;
    })
    ->sum('predicted_demand');
```

#### Code Location:
- **Controller**: `DemandForecastController.php` (Lines 112-118)
- **View**: `forecasts/index.blade.php` (Line 172)

#### Example:
- **Product**: "Laptop Computer"
- **Selected Month**: January 2026
- **Daily Forecasts**: 10 units/day × 31 days = **310 units**

---

### 4.2 Forecast Reliability (Confidence Level)

#### Definition:
**Forecast Reliability** (also called Confidence Level) is a **custom reliability score** that indicates estimated forecast quality based on:
1. **Model Fit Quality** (R-squared - well-established metric)
2. **Data Quantity** (number of historical data points - recognized principle)

**⚠️ Important:** This is NOT a standard statistical confidence interval. It represents an **estimated reliability indicator**, not a probability of accuracy.

#### Formula:
\[
\text{Reliability} = \min(95, 50 + (R^2 \times 30) + \min(10, \frac{\text{dataPoints}}{10}))
\]

#### Calculation Breakdown:

**Component 1: Base Confidence (50%)**
- Minimum confidence level
- Ensures all forecasts have at least 50% reliability

**Component 2: R-Squared Contribution (0-30%)**
- Based on how well the linear regression fits the data
- Higher R² = Better fit = Higher confidence
- Example: R² = 0.8 → Contribution = 0.8 × 30 = 24%

**Component 3: Data Points Contribution (0-10%)**
- Based on amount of historical data
- More data = Higher confidence
- Example: 50 data points → Contribution = min(10, 50/10) = 10%

#### Code Location:
- **Generation**: `DemandForecastController.php` (Lines 285-289)
- **Display**: `forecasts/index.blade.php` (Lines 173-180)

#### Color Coding:
- 🟢 **Green (≥80%)**: High confidence - Reliable forecast
- 🟡 **Yellow (60-79%)**: Medium confidence - Moderate reliability
- 🔴 **Red (<60%)**: Low confidence - Use with caution
- ⚪ **Gray (N/A)**: No data available

#### Example:
- **R²**: 0.75 (good fit)
- **Data Points**: 45 days
- **Calculation**: 50 + (0.75 × 30) + min(10, 45/10) = 50 + 22.5 + 9 = **81.5%**
- **Display**: Green badge showing "81.5%"
- **Interpretation**: Indicates good model fit and adequate data, but should be used as a quality indicator, not a guarantee of accuracy

#### Academic Basis & Limitations:
- **R-Squared Component**: Based on well-established statistical measure (coefficient of determination)
- **Sample Size Component**: Based on recognized statistical principle (more data = more reliable)
- **Weighting System**: Heuristic approach (practical combination, not derived from statistical theory)
- **Not Based On**: Standard confidence intervals, prediction intervals, or validated accuracy metrics
- **See**: `FORECAST_RELIABILITY_CRITICAL_ANALYSIS.md` for detailed analysis

---

### 4.3 Current Inventory

#### Definition:
**Current Inventory** represents the actual stock quantity available in the warehouse for a specific product at the current time.

#### Source:
- **Database Table**: `products`
- **Column**: `quantity`
- **Type**: Integer (number of units)

#### Formula:
\[
\text{Current Inventory} = \text{products.quantity}
\]

#### Code Location:
- **Model**: `Product.php` (Line 11: `quantity` field)
- **Display**: `forecasts/index.blade.php` (Line 182)

#### Characteristics:
- ✅ **Real-time**: Reflects current stock level
- ✅ **Same for all years**: Doesn't change based on forecast year
- ✅ **Updated by**: Inventory management system (POS, stock adjustments)

#### Example:
- **Product**: "Wireless Mouse"
- **Current Inventory**: **150 units**
- **Display**: Shows "150" in Current Inventory column

---

### 4.4 Inventory Gap

#### Definition:
**Inventory Gap** is the difference between forecasted demand and current inventory. It indicates whether there will be a shortage (positive) or surplus (negative) of stock.

#### Formula:
\[
\text{Inventory Gap} = \text{Month Forecast} - \text{Current Inventory}
\]

#### Interpretation:

**Positive Gap (+):**
- Forecasted demand > Current inventory
- **Meaning**: Potential stockout risk
- **Action**: Consider reordering
- **Color**: 🔴 Red badge

**Negative Gap (-):**
- Forecasted demand < Current inventory
- **Meaning**: Sufficient stock available
- **Action**: No immediate reorder needed
- **Color**: 🟢 Green badge

**Zero Gap (0):**
- Forecasted demand = Current inventory
- **Meaning**: Stock matches forecast exactly
- **Action**: Monitor closely

#### Code Location:
- **Calculation**: `DemandForecastController.php` (Line 122)
- **Display**: `forecasts/index.blade.php` (Lines 183-186)

#### Example Calculation:
- **Month Forecast**: 200 units
- **Current Inventory**: 150 units
- **Inventory Gap**: 200 - 150 = **+50 units** (🔴 Red - Shortage)

#### Business Impact:
- **Positive Gap**: Risk of lost sales, customer dissatisfaction
- **Negative Gap**: Excess inventory, storage costs, capital tied up

---

### 4.5 Reorder Required

#### Definition:
**Reorder Required** is a boolean indicator that determines whether a product needs to be reordered based on forecasted demand and current inventory levels.

#### Formula:
\[
\text{Reorder Required} = \begin{cases}
\text{True} & \text{if } (\text{Month Forecast} > \text{Current Inventory}) \text{ OR } (\text{Current Inventory} \leq \text{Reorder Level}) \\
\text{False} & \text{otherwise}
\end{cases}
\]

#### Logic Breakdown:

**Condition 1: Forecast Exceeds Inventory**
- If forecasted demand > current inventory
- **Reason**: Will run out of stock before meeting demand

**Condition 2: Below Reorder Level**
- If current inventory ≤ reorder level (safety stock)
- **Reason**: Stock is at or below minimum threshold

#### Code Location:
- **Calculation**: `DemandForecastController.php` (Line 126)
- **Display**: `forecasts/index.blade.php` (Lines 188-194)

#### Code Implementation:
```php
$product->reorder_required = ($monthForecast > $product->quantity) 
    || ($product->quantity <= $product->reorder_level);
```

#### Display:
- 🟡 **Yellow Badge "Yes"**: Reorder required
- 🟢 **Green Badge "No"**: No reorder needed

#### Example Scenarios:

**Scenario 1: Forecast Exceeds Inventory**
- Month Forecast: 300 units
- Current Inventory: 200 units
- Reorder Level: 50 units
- **Result**: ✅ **Yes** (300 > 200)

**Scenario 2: Below Reorder Level**
- Month Forecast: 100 units
- Current Inventory: 45 units
- Reorder Level: 50 units
- **Result**: ✅ **Yes** (45 ≤ 50)

**Scenario 3: Sufficient Stock**
- Month Forecast: 100 units
- Current Inventory: 150 units
- Reorder Level: 50 units
- **Result**: ❌ **No** (100 < 150 AND 150 > 50)

---

## 5. Filtering Features

### 5.1 Category Filter

#### Purpose:
Filter forecasts to show only products from a specific category.

#### Implementation:
```php
// Controller
if ($categoryId) {
    $productsQuery->where('category_id', $categoryId);
}

// View
<select name="category_id" class="form-select form-select-sm">
    <option value="">All</option>
    @foreach($categories as $category)
    <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>
        {{ $category->name }}
    </option>
    @endforeach
</select>
```

#### Code Location:
- **Controller**: `DemandForecastController.php` (Lines 75-77, 88-90)
- **View**: `forecasts/index.blade.php` (Lines 16-23)

#### Use Cases:
- Analyze demand for specific product categories
- Compare forecasts across categories
- Focus on high-priority categories

---

### 5.2 Year Filter

#### Purpose:
Select which forecast year to display (supports multiple future years).

#### Implementation:
```php
// Controller
$year = (int) $request->get('year', $defaultYear);
$productsQuery->with(['demandForecasts' => function ($q) use ($year) {
    $q->whereYear('forecast_date', $year);
}]);

// View
<select name="year" class="form-select form-select-sm">
    @foreach($availableYears as $yr)
    <option value="{{ $yr }}" {{ (int)$year === (int)$yr ? 'selected' : '' }}>
        {{ $yr }}
    </option>
    @endforeach
</select>
```

#### Code Location:
- **Controller**: `DemandForecastController.php` (Lines 24, 48-58, 71-72)
- **View**: `forecasts/index.blade.php` (Lines 27-31)

#### Available Years:
- Automatically detects years with forecasts
- Defaults to current year
- Supports: Current year + next 2 years (e.g., 2025, 2026, 2027)

---

### 5.3 Month Filter

#### Purpose:
Select specific month within the selected year to analyze.

#### Implementation:
```php
// Controller
$selectedMonth = (int) $request->get('month', 1);
$monthStart = sprintf('%04d-%02d-01', $year, $selectedMonth);
$monthForecast = $product->demandForecasts
    ->filter(function($forecast) use ($monthStart) {
        return $forecast->forecast_date->format('Y-m-d') === $monthStart;
    })
    ->sum('predicted_demand');

// View
<select name="month" class="form-select form-select-sm">
    @for($m = 1; $m <= 12; $m++)
    <option value="{{ $m }}" {{ (int)$selectedMonth === $m ? 'selected' : '' }}>
        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
    </option>
    @endfor
</select>
```

#### Code Location:
- **Controller**: `DemandForecastController.php` (Lines 25, 113-118)
- **View**: `forecasts/index.blade.php` (Lines 34-41)

#### Use Cases:
- Monthly planning and budgeting
- Seasonal analysis
- Month-specific inventory planning

---

### 5.4 Confidence Filter

#### Purpose:
Filter products by forecast reliability threshold.

#### Implementation:
```php
// Controller
$confidenceKey = $request->get('confidence');
$confidenceMin = null;
if (in_array($confidenceKey, ['90', '75', '50'], true)) {
    $confidenceMin = (int) $confidenceKey;
}

$productsQuery->with(['demandForecasts' => function ($q) use ($year, $confidenceMin) {
    $q->whereYear('forecast_date', $year);
    if ($confidenceMin !== null) {
        $q->where('confidence_level', '>=', $confidenceMin);
    }
}]);

// View
<select name="confidence" class="form-select form-select-sm">
    <option value="">All</option>
    <option value="90" {{ $confidenceKey === '90' ? 'selected' : '' }}>≥ 90%</option>
    <option value="75" {{ $confidenceKey === '75' ? 'selected' : '' }}>≥ 75%</option>
    <option value="50" {{ $confidenceKey === '50' ? 'selected' : '' }}>≥ 50%</option>
</select>
```

#### Code Location:
- **Controller**: `DemandForecastController.php` (Lines 51, 64-69, 71-75)
- **View**: `forecasts/index.blade.php` (Lines 44-50)

#### Filter Options:
- **All**: Show all products regardless of confidence
- **≥ 90%**: Only high-confidence forecasts
- **≥ 75%**: Medium to high confidence
- **≥ 50%**: All forecasts with at least minimum confidence

---

### 5.5 Sort Filter

#### Purpose:
Sort products by forecast quantity (highest or lowest first).

#### Implementation:
```php
// Controller
$sort = $request->get('sort', 'desc') === 'asc' ? 'asc' : 'desc';
$forecastProducts = $productsQuery
    ->orderBy('forecast_total', $sort)
    ->orderBy('name')
    ->paginate(15);

// View
<select name="sort" class="form-select form-select-sm">
    <option value="desc" {{ $sort === 'desc' ? 'selected' : '' }}>Highest forecast</option>
    <option value="asc" {{ $sort === 'asc' ? 'selected' : '' }}>Lowest forecast</option>
</select>
```

#### Code Location:
- **Controller**: `DemandForecastController.php` (Lines 60, 99-103)
- **View**: `forecasts/index.blade.php` (Lines 53-57)

#### Sort Options:
- **Highest forecast**: Products with highest predicted demand first
- **Lowest forecast**: Products with lowest predicted demand first

---

### 5.6 Search Filter

#### Purpose:
Search for products by name or SKU.

#### Implementation:
```php
// Controller
$search = $request->get('search');
if ($search) {
    $productsQuery->where(function ($q) use ($search) {
        $q->where('name', 'like', '%' . $search . '%')
          ->orWhere('sku', 'like', '%' . $search . '%');
    });
}

// View
<input type="text" name="search" class="form-control" 
       placeholder="Product or SKU" value="{{ $search }}">
```

#### Code Location:
- **Controller**: `DemandForecastController.php` (Lines 61, 92-97)
- **View**: `forecasts/index.blade.php` (Lines 60-66)

#### Search Capabilities:
- Search by product name (partial match)
- Search by SKU (partial match)
- Case-insensitive
- Real-time filtering

---

## 6. Visualization & Graphs

### 6.1 Graph 1: Actual vs Forecasted Monthly Demand

#### Type: **Line Chart**

#### Purpose:
Compare historical actual sales with forecasted demand over time to validate forecast accuracy.

#### Data Source:
- **Actual**: Historical sales from `sale_items` table (aggregated monthly)
- **Forecasted**: Forecasts from `demand_forecasts` table (aggregated monthly)

#### Chart Configuration:
```javascript
{
    type: 'line',
    data: {
        labels: ['Jan 2025', 'Feb 2025', ..., 'Jan 2026', 'Feb 2026'],
        datasets: [
            {
                label: 'Actual Demand',
                data: [1200, 1500, ..., null, null],
                borderColor: 'rgb(54, 162, 235)',  // Blue
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.4
            },
            {
                label: 'Forecasted Demand',
                data: [null, null, ..., 1800, 2000],
                borderColor: 'rgb(255, 99, 132)',  // Red
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                borderDash: [5, 5],  // Dashed line
                tension: 0.4
            }
        ]
    }
}
```

#### Code Location:
- **Controller**: `DemandForecastController.php` (Lines 852-957: `getActualVsForecastedData()`)
- **View**: `forecasts/index.blade.php` (Lines 210-224, 415-459)

#### Key Features:
- Shows last 12 months of actual data + all forecast months
- Blue solid line = Actual sales
- Red dashed line = Forecasted demand
- Helps identify forecast accuracy trends

---

### 6.2 Graph 2: Forecasted Demand Trend per Product

#### Type: **Multi-Line Chart**

#### Purpose:
Display forecasted demand trends for top 10 products over months in the selected year.

#### Data Source:
- Top 10 products by total forecasted demand
- Monthly aggregated forecasts

#### Chart Configuration:
```javascript
{
    type: 'line',
    data: {
        labels: ['Jan 2026', 'Feb 2026', 'Mar 2026', ...],
        datasets: [
            {
                label: 'Product A',
                data: [500, 550, 600, ...],
                borderColor: 'rgb(75, 192, 192)',  // Teal
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.4,
                fill: false
            },
            {
                label: 'Product B',
                data: [300, 320, 340, ...],
                borderColor: 'rgb(255, 99, 132)',  // Pink
                // ... more products
            }
        ]
    }
}
```

#### Code Location:
- **Controller**: `DemandForecastController.php` (Lines 963-1054: `getForecastPerProductData()`)
- **View**: `forecasts/index.blade.php` (Lines 228-242, 462-506)

#### Key Features:
- Shows top 10 products by forecast volume
- Each product has different color line
- Helps identify high-demand products
- Shows monthly trend patterns

---

### 6.3 Graph 3: Forecasted Demand vs Inventory Over Time

#### Type: **Dual-Line Chart**

#### Purpose:
Compare forecasted demand trend with current inventory level (constant reference line).

#### Data Source:
- **Forecasted**: Monthly aggregated forecasts
- **Inventory**: Current inventory level (constant line)

#### Chart Configuration:
```javascript
{
    type: 'line',
    data: {
        labels: ['Jan 2026', 'Feb 2026', 'Mar 2026', ...],
        datasets: [
            {
                label: 'Forecasted Demand',
                data: [5000, 5500, 6000, ...],
                borderColor: 'rgb(255, 99, 132)',  // Red
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.4,
                fill: false
            },
            {
                label: 'Current Inventory (Reference)',
                data: [3000, 3000, 3000, ...],  // Constant
                borderColor: 'rgb(54, 162, 235)',  // Blue
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                borderDash: [5, 5],  // Dashed line
                tension: 0,
                fill: false
            }
        ]
    }
}
```

#### Code Location:
- **Controller**: `DemandForecastController.php` (Lines 1060-1150: `getForecastVsInventoryData()`)
- **View**: `forecasts/index.blade.php` (Lines 246-260, 509-553)

#### Key Features:
- Red solid line = Forecasted demand trend
- Blue dashed line = Current inventory (constant)
- Identifies when forecast exceeds inventory
- Helps plan reorder timing

---

### 6.4 Graph 4: Category Product Trend

#### Type: **Line Chart**

#### Purpose:
Show actual vs forecasted demand for a specific category (selectable).

#### Data Source:
- Category-filtered actual sales
- Category-filtered forecasts

#### Chart Configuration:
```javascript
{
    type: 'line',
    data: {
        labels: ['Jan 2025', 'Feb 2025', ..., 'Jan 2026'],
        datasets: [
            {
                label: 'Actual Demand',
                data: [1200, 1500, ..., null],
                borderColor: 'rgb(54, 162, 235)',  // Blue
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.4,
                spanGaps: true
            },
            {
                label: 'Forecasted Demand',
                data: [null, null, ..., 1800],
                borderColor: 'rgb(255, 99, 132)',  // Red
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                borderDash: [5, 5],
                tension: 0.4,
                spanGaps: true
            }
        ]
    }
}
```

#### Code Location:
- **Controller**: `DemandForecastController.php` (Lines 1171-1280: `getCategoryTrendData()`)
- **View**: `forecasts/index.blade.php` (Lines 264-300, 556-591)

#### Key Features:
- Category selector dropdown (data-driven)
- Shows category-specific trends
- Compares actual vs forecasted for category
- Helps analyze category performance

---

## 7. Code Integration & Implementation

### 7.1 Data Collection Integration

#### Sales Data Integration:
```php
// Location: DemandForecastController.php (Lines 189-197)
$historicalSales = SaleItem::where('product_id', $product->id)
    ->select(
        DB::raw('DATE(created_at) as date'),
        DB::raw('SUM(quantity) as total')
    )
    ->groupBy('date')
    ->orderBy('date', 'asc')
    ->get();
```

**Integration Points:**
- **Table**: `sale_items`
- **Relationship**: `Product hasMany SaleItem`
- **Purpose**: Collect historical sales for trend calculation

#### Inventory Movement Integration:
```php
// Location: DemandForecastController.php (Lines 199-208)
$historicalOutMovements = InventoryMovement::where('product_id', $product->id)
    ->where('type', 'out')
    ->select(
        DB::raw('DATE(created_at) as date'),
        DB::raw('SUM(quantity) as total')
    )
    ->groupBy('date')
    ->orderBy('date', 'asc')
    ->get();
```

**Integration Points:**
- **Table**: `inventory_movements`
- **Relationship**: `Product hasMany InventoryMovement`
- **Purpose**: Include inventory outbound movements in demand calculation

---

### 7.2 Forecast Generation Integration

#### Linear Regression Calculation:
```php
// Location: DemandForecastController.php (Lines 323-380)
private function calculateLinearRegression($timeSeries)
{
    $n = count($timeSeries);
    $sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0;
    
    foreach ($timeSeries as $point) {
        $x = $point['x'];  // days since start
        $y = $point['y'];  // demand
        $sumX += $x;
        $sumY += $y;
        $sumXY += $x * $y;
        $sumX2 += $x * $x;
    }
    
    // Calculate slope
    $denominator = ($n * $sumX2) - ($sumX * $sumX);
    $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
    
    // Calculate intercept
    $meanX = $sumX / $n;
    $meanY = $sumY / $n;
    $intercept = $meanY - ($slope * $meanX);
    
    // Calculate R-squared
    // ... (R² calculation)
    
    return ['slope' => $slope, 'intercept' => $intercept, 'r_squared' => $rSquared];
}
```

**Integration Points:**
- **Input**: Time series data (days vs demand)
- **Output**: Slope, intercept, R-squared
- **Purpose**: Calculate trend line parameters

---

### 7.3 Database Storage Integration

#### Forecast Storage:
```php
// Location: DemandForecastController.php (Lines 291-304)
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
```

**Integration Points:**
- **Model**: `DemandForecast` (Eloquent ORM)
- **Table**: `demand_forecasts`
- **Relationship**: `belongsTo Product`
- **Purpose**: Store daily forecasts for retrieval

---

### 7.4 View Integration

#### Table Display:
```php
// Location: forecasts/index.blade.php (Lines 158-195)
@forelse($forecastProducts as $product)
    @php
        $monthForecast = $product->month_forecast ?? 0;
        $inventoryGap = $product->inventory_gap ?? 0;
        $reorderRequired = $product->reorder_required ?? false;
        $monthReliability = optional($product->demandForecasts->first())->confidence_level;
    @endphp
    <tr>
        <td>{{ $product->name }}<br><small>{{ $product->sku }}</small></td>
        <td>{{ $product->category->name ?? 'N/A' }}</td>
        <td class="text-end">{{ number_format($monthForecast) }}</td>
        <td class="text-end">
            <span class="badge bg-{{ $reliabilityClass }}">
                {{ $monthReliability }}%
            </span>
        </td>
        <td class="text-end">{{ number_format($product->quantity) }}</td>
        <td class="text-end">
            <span class="badge bg-{{ $inventoryGap > 0 ? 'danger' : 'success' }}">
                {{ $inventoryGap > 0 ? '+' : '' }}{{ number_format($inventoryGap) }}
            </span>
        </td>
        <td class="text-end">
            @if($reorderRequired)
                <span class="badge bg-warning">Yes</span>
            @else
                <span class="badge bg-success">No</span>
            @endif
        </td>
    </tr>
@endforelse
```

**Integration Points:**
- **Controller Data**: `$forecastProducts` collection
- **Blade Template**: Laravel Blade syntax
- **Bootstrap Styling**: Badges, colors, formatting

---

### 7.5 Chart Integration

#### Chart.js Integration:
```javascript
// Location: forecasts/index.blade.php (Lines 415-459)
const actualVsForecastedCtx = document.getElementById('actualVsForecastedChart');
if (actualVsForecastedCtx) {
    new Chart(actualVsForecastedCtx.getContext('2d'), {
        type: 'line',
        data: @json($actualVsForecastedData),  // PHP to JSON
        options: {
            responsive: true,
            plugins: {
                title: { display: true, text: 'Actual vs Forecasted Monthly Demand' },
                legend: { display: true, position: 'top' }
            },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Demand (Units)' } },
                x: { title: { display: true, text: 'Month' } }
            }
        }
    });
}
```

**Integration Points:**
- **Library**: Chart.js 3.9.1
- **Data Format**: JSON (converted from PHP arrays)
- **Rendering**: HTML5 Canvas
- **Controller**: Prepares data in Chart.js format

---

## 8. Conclusion & Summary

### 8.1 Key Achievements

✅ **Statistical Foundation**: Based on established OLS regression method (Gauss, 1809)

✅ **Academic Rigor**: Follows principles from:
- Time Series Analysis (Box & Jenkins)
- Operations Management (Nahmias)
- Supply Chain Forecasting (Chopra & Meindl)

✅ **Practical Implementation**: 
- Automatic forecast generation
- Multi-year forecasting capability
- Daily granularity with seasonal adjustment

✅ **User-Friendly Interface**:
- Comprehensive filtering system
- Visual analytics (4 chart types)
- Color-coded indicators
- Real-time calculations

✅ **Business Value**:
- Inventory optimization
- Reorder recommendations
- Data-driven decision making
- Risk identification (inventory gaps)

### 8.2 Technical Highlights

**Method**: Trend Projection with Linear Regression (OLS)

**Formula**: 
\[
\text{Predicted} = \max(1, \text{round}((\text{intercept} + \text{slope} \times \text{days}) \times \text{seasonal} \times \text{weekend}))
\]

**Components**:
1. Month Forecast - Sum of daily forecasts
2. Forecast Reliability - Based on R² and data points
3. Current Inventory - Real-time stock level
4. Inventory Gap - Forecast - Inventory
5. Reorder Required - Boolean based on gap and reorder level

**Integration**:
- Sales Module (historical data)
- Inventory Module (movements)
- Product Module (current stock)
- Category Module (filtering)

### 8.3 Future Enhancements

- Advanced forecasting methods (ARIMA, ML)
- External factor integration (promotions, events)
- Automated reordering
- Enhanced analytics and reporting
- Multi-store support

---

## 📊 Presentation Tips

### For Panel Presentation:

1. **Start with Overview** (2 minutes)
   - What is demand forecasting?
   - Why is it important?
   - What does this module do?

2. **Explain Methodology** (3 minutes)
   - Linear Regression (OLS)
   - Academic basis (Gauss, Box & Jenkins)
   - Why this method was chosen

3. **Demonstrate Formula** (5 minutes)
   - Show step-by-step calculation
   - Use example with real numbers
   - Explain each component

4. **Explain Each Element** (10 minutes)
   - Month Forecast (how calculated)
   - Forecast Reliability (confidence formula)
   - Current Inventory (source)
   - Inventory Gap (calculation)
   - Reorder Required (logic)

5. **Show Filtering Features** (3 minutes)
   - Live demonstration
   - Explain each filter
   - Show use cases

6. **Present Graphs** (5 minutes)
   - Explain each chart type
   - Show what insights they provide
   - Demonstrate interactivity

7. **Code Integration** (5 minutes)
   - Show key code snippets
   - Explain database relationships
   - Demonstrate data flow

8. **Q&A Preparation** (2 minutes)
   - Limitations and assumptions
   - Future improvements
   - Performance considerations

---

## 📝 Quick Reference Card

### Formulas:
- **Forecast**: `max(1, round((intercept + slope × days) × seasonal × weekend))`
- **Slope**: `b = (nΣxy - ΣxΣy) / (nΣx² - (Σx)²)`
- **Intercept**: `a = ȳ - b x̄`
- **R²**: `R² = 1 - (SS_res / SS_tot)`
- **Confidence**: `min(95, 50 + (R² × 30) + min(10, dataPoints/10))`
- **Inventory Gap**: `Month Forecast - Current Inventory`
- **Reorder**: `(Forecast > Inventory) OR (Inventory ≤ Reorder Level)`

### Key Studies:
- Gauss (1809) - OLS Method
- Box & Jenkins (1976) - Time Series
- Makridakis et al. (1998) - Forecasting Methods
- Nahmias (2009) - Operations Management
- Chopra & Meindl (2016) - Supply Chain

### Components:
1. Month Forecast - Sum of daily forecasts
2. Forecast Reliability - Confidence percentage
3. Current Inventory - Stock level
4. Inventory Gap - Difference calculation
5. Reorder Required - Boolean indicator

---

*This guide provides comprehensive information for presenting the Demand Forecast Module to panelists. Use it as a reference during your presentation and Q&A session.*

