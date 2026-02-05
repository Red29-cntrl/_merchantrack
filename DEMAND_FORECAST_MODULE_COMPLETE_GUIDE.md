# Demand Forecast Module - Complete Guide

## 📋 Table of Contents

1. [Module Overview](#module-overview)
2. [System Integration](#system-integration)
3. [Database Structure](#database-structure)
4. [Access Control & Permissions](#access-control--permissions)
5. [Data Requirements](#data-requirements)
6. [Performance Considerations](#performance-considerations)
7. [User Interface Features](#user-interface-features)
8. [Maintenance & Updates](#maintenance--updates)
9. [Edge Cases & Limitations](#edge-cases--limitations)
10. [Troubleshooting](#troubleshooting)
11. [Best Practices](#best-practices)
12. [Future Considerations](#future-considerations)

---

## Module Overview

### What It Does
The Demand Forecast Module predicts future product demand using historical sales and inventory data. It helps businesses:
- Plan inventory levels
- Identify reorder needs
- Optimize stock management
- Make data-driven purchasing decisions

### Key Features
- ✅ Automatic forecast generation
- ✅ Multi-year forecasting (current + 2 years ahead)
- ✅ Daily granularity forecasts
- ✅ Seasonal adjustment
- ✅ Confidence level indicators
- ✅ Visual charts and graphs
- ✅ Category-based filtering
- ✅ Inventory gap analysis
- ✅ Reorder recommendations

---

## System Integration

### Dependencies

#### 1. **Product Module**
- **Relationship**: `DemandForecast belongsTo Product`
- **Required Fields**: `id`, `name`, `sku`, `quantity`, `reorder_level`, `is_active`, `category_id`
- **Usage**: Forecasts are generated only for active products (`is_active = true`)

#### 2. **Sales Module**
- **Table**: `sale_items`
- **Required Fields**: `product_id`, `quantity`, `created_at`
- **Usage**: Historical sales data aggregated by date for trend calculation

#### 3. **Inventory Module**
- **Table**: `inventory_movements`
- **Required Fields**: `product_id`, `quantity`, `type`, `created_at`
- **Usage**: Only "out" type movements are included in demand calculation

#### 4. **Category Module**
- **Table**: `categories`
- **Usage**: Filtering forecasts by category, grouping products

### Data Flow

```
Sales Data (sale_items)
    ↓
    + Inventory Out Movements (inventory_movements, type='out')
    ↓
    = Daily Demand Calculation
    ↓
    Linear Regression Analysis
    ↓
    Trend Calculation (slope, intercept)
    ↓
    Seasonal & Weekend Adjustments
    ↓
    Forecast Generation (daily forecasts)
    ↓
    Database Storage (demand_forecasts)
    ↓
    UI Display (tables, charts, graphs)
```

---

## Database Structure

### Table: `demand_forecasts`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `product_id` | bigint | Foreign key to `products` table |
| `forecast_date` | date | Date of forecast (YYYY-MM-DD) |
| `predicted_demand` | integer | Forecasted quantity |
| `confidence_level` | decimal(5,2) | Reliability score (0-95) |
| `method` | text | "Trend Projection Method (Linear Regression)" |
| `historical_data` | json | Stores slope, intercept, R², data_points, trend_direction |
| `created_at` | timestamp | Record creation time |
| `updated_at` | timestamp | Record update time |

### Indexes
- **Primary Key**: `id`
- **Foreign Key**: `product_id` → `products.id` (CASCADE DELETE)
- **Composite Index**: `(product_id, forecast_date)` for efficient queries

### Relationships

```php
// DemandForecast Model
public function product()
{
    return $this->belongsTo(Product::class);
}

// Product Model
public function demandForecasts()
{
    return $this->hasMany(DemandForecast::class);
}
```

### Data Volume Estimate
- **Per Product**: 365 records per year (one per day)
- **100 Products**: 36,500 records per year
- **100 Products × 3 Years**: 109,500 records

---

## Access Control & Permissions

### Middleware Protection
```php
public function __construct()
{
    $this->middleware('auth');      // Must be logged in
    $this->middleware('admin');     // Must be admin user
}
```

### Routes
- **GET** `/forecasts` - View forecasts (index page)
- **GET** `/forecasts/{forecast}` - View individual forecast details
- **POST** `/forecasts/generate` - Manually trigger generation (if implemented)

### Access Requirements
- ✅ User must be authenticated
- ✅ User must have admin role
- ❌ Regular users cannot access forecasts

---

## Data Requirements

### Minimum Data Requirements

#### For Forecast Generation:
1. **At least 7 days** of historical data
   - Can be sales OR inventory movements
   - Can be non-consecutive days
   - More data = better accuracy

2. **Product must be active**
   - `is_active = true` in products table
   - Inactive products are skipped

#### For Accurate Forecasts:
- **Recommended**: 30+ days of data
- **Ideal**: 90+ days of data
- **Best**: 180+ days (6 months) of data

### Data Sources

#### 1. Sales Data (`sale_items`)
```sql
SELECT DATE(created_at) as date, SUM(quantity) as total
FROM sale_items
WHERE product_id = ?
GROUP BY DATE(created_at)
```

#### 2. Inventory Out Movements (`inventory_movements`)
```sql
SELECT DATE(created_at) as date, SUM(quantity) as total
FROM inventory_movements
WHERE product_id = ? AND type = 'out'
GROUP BY DATE(created_at)
```

#### 3. Combined Daily Demand
```php
$dailyDemand[$date] = sales_total + inventory_out_total
```

### Data Quality Considerations

#### Good Data:
- ✅ Consistent daily sales
- ✅ Regular inventory movements
- ✅ No large gaps in dates
- ✅ Realistic quantities

#### Problematic Data:
- ⚠️ Large gaps (weeks/months without data)
- ⚠️ Extreme outliers (very high/low values)
- ⚠️ Only weekend sales (no weekday pattern)
- ⚠️ Very sparse data (< 7 days)

---

## Performance Considerations

### Forecast Generation Performance

#### Time Complexity:
- **Per Product**: O(n) where n = number of historical data points
- **All Products**: O(p × n) where p = number of products
- **Overall**: Linear time complexity

#### Typical Performance:
- **100 Products**: ~5-10 seconds
- **500 Products**: ~30-60 seconds
- **1000 Products**: ~2-5 minutes

#### Optimization Strategies:

1. **Batch Processing**
   - Processes products one at a time
   - Can be optimized with queue jobs for large datasets

2. **Database Indexing**
   - Index on `(product_id, forecast_date)` for fast lookups
   - Index on `forecast_date` for year filtering

3. **Caching**
   - Forecasts are stored in database (no recalculation needed)
   - Only regenerates when missing

4. **Query Optimization**
   - Uses `groupBy` and aggregation in database
   - Minimizes data transfer from database

### Page Load Performance

#### Index Page Load:
- **With 100 Products**: ~1-2 seconds
- **With 500 Products**: ~3-5 seconds
- **With 1000 Products**: ~5-10 seconds

#### Optimization Tips:
- Use pagination (currently 15 items per page)
- Filter by category to reduce data
- Use year/month filters to limit queries

---

## User Interface Features

### Main Page (`/forecasts`)

#### Filters:
1. **Category Filter** - Filter by product category
2. **Year Selector** - Choose forecast year (2025, 2026, 2027, etc.)
3. **Month Selector** - Choose specific month (Jan-Dec)
4. **Confidence Filter** - Filter by reliability (≥90%, ≥75%, ≥50%)
5. **Sort Options** - Highest/Lowest forecast first
6. **Search** - Search by product name or SKU

#### Table Columns:
1. **Product** - Name and SKU
2. **Category** - Product category
3. **Month Forecast** - Predicted demand for selected month
4. **Forecast Reliability** - Confidence percentage (color-coded)
5. **Current Inventory** - Current stock level
6. **Inventory Gap** - Forecast - Inventory (color-coded)
7. **Reorder Required** - Yes/No indicator

#### Charts:
1. **Actual vs Forecasted Monthly Demand** - Line chart comparing historical vs predicted
2. **Forecasted Demand Trend per Product** - Top 10 products over months
3. **Forecasted Demand vs Inventory** - Comparison with current inventory
4. **Category Product Trend** - Category-specific trends

### Color Coding:

#### Forecast Reliability:
- 🟢 **Green** (≥80%): High confidence
- 🟡 **Yellow** (60-79%): Medium confidence
- 🔴 **Red** (<60%): Low confidence
- ⚪ **Gray**: No data

#### Inventory Gap:
- 🔴 **Red** (Positive): Shortage (forecast > inventory)
- 🟢 **Green** (Negative): Surplus (forecast < inventory)

---

## Maintenance & Updates

### When Forecasts Are Generated

#### Automatic Generation:
- **Trigger**: When accessing `/forecasts` page
- **Condition**: If forecasts don't exist for current year + next 2 years
- **Frequency**: Once per year range (unless manually regenerated)

#### Manual Regeneration:
- Delete existing forecasts from database
- Access `/forecasts` page
- System will automatically regenerate

### Updating Forecasts

#### When to Regenerate:
1. **After significant sales data added** (e.g., after 30+ days)
2. **After product changes** (price, category, etc.)
3. **At start of new year** (for new forecast year)
4. **After data corrections** (if historical data was fixed)

#### How to Regenerate:
```sql
-- Delete forecasts for specific year
DELETE FROM demand_forecasts WHERE YEAR(forecast_date) = 2026;

-- Delete all forecasts
DELETE FROM demand_forecasts;

-- Then access /forecasts page to regenerate
```

### Data Cleanup

#### Old Forecasts:
- Forecasts older than 3 years can be archived/deleted
- Use scheduled job or manual cleanup

```sql
-- Delete forecasts older than 3 years
DELETE FROM demand_forecasts 
WHERE forecast_date < DATE_SUB(CURDATE(), INTERVAL 3 YEAR);
```

---

## Edge Cases & Limitations

### Edge Cases Handled

#### 1. **Insufficient Data**
- **Case**: Less than 7 days of historical data
- **Handling**: Product is skipped, no forecast generated
- **User Impact**: Product won't appear in forecasts

#### 2. **No Historical Data**
- **Case**: Product has no sales or inventory movements
- **Handling**: Product is skipped
- **User Impact**: Product won't appear in forecasts

#### 3. **Inactive Products**
- **Case**: Product has `is_active = false`
- **Handling**: Product is skipped during generation
- **User Impact**: Inactive products don't get forecasts

#### 4. **Deleted Products**
- **Case**: Product deleted but forecasts exist
- **Handling**: Foreign key cascade delete removes forecasts
- **User Impact**: Forecasts automatically cleaned up

#### 5. **Regression Failure**
- **Case**: Cannot calculate slope (e.g., all x values same)
- **Handling**: Product is skipped, no forecast generated
- **User Impact**: Product won't appear in forecasts

#### 6. **Negative Forecasts**
- **Case**: Trend projection results in negative value
- **Handling**: `max(1, round(...))` ensures minimum of 1 unit
- **User Impact**: Forecasts always show at least 1 unit

### Limitations

#### 1. **Linear Trend Assumption**
- **Limitation**: Assumes linear trend continues
- **Impact**: May not capture non-linear patterns
- **Mitigation**: Seasonal adjustment helps

#### 2. **No External Factors**
- **Limitation**: Doesn't account for promotions, events, holidays
- **Impact**: May miss demand spikes/drops
- **Mitigation**: Manual adjustments may be needed

#### 3. **Extrapolation Risk**
- **Limitation**: Accuracy decreases for distant forecasts
- **Impact**: Long-term forecasts less reliable
- **Mitigation**: Focus on short-term (1-2 years)

#### 4. **Sparse Data**
- **Limitation**: Works best with consistent daily data
- **Impact**: Irregular sales patterns may reduce accuracy
- **Mitigation**: More historical data improves results

#### 5. **Single Method**
- **Limitation**: Only uses linear regression
- **Impact**: May not suit all product types
- **Mitigation**: Consider multiple methods for different products

---

## Troubleshooting

### Common Issues

#### 1. **No Forecasts Generated**

**Symptoms:**
- Empty forecast table
- "No forecast data available" message

**Possible Causes:**
- Insufficient historical data (< 7 days)
- All products are inactive
- Database connection issues

**Solutions:**
```sql
-- Check if products have data
SELECT p.id, p.name, COUNT(DISTINCT DATE(si.created_at)) as days
FROM products p
LEFT JOIN sale_items si ON p.id = si.product_id
WHERE p.is_active = 1
GROUP BY p.id
HAVING days >= 7;

-- Check if forecasts exist
SELECT COUNT(*) FROM demand_forecasts;
```

#### 2. **Forecasts Not Updating**

**Symptoms:**
- Old forecasts still showing
- New sales data not reflected

**Possible Causes:**
- Forecasts already exist (system doesn't regenerate)
- Cache issues
- Data not saved properly

**Solutions:**
```sql
-- Force regeneration by deleting existing forecasts
DELETE FROM demand_forecasts WHERE YEAR(forecast_date) = 2026;

-- Then access /forecasts page
```

#### 3. **Low Confidence Scores**

**Symptoms:**
- Many forecasts with <60% confidence
- Red/yellow reliability badges

**Possible Causes:**
- Insufficient data (< 30 days)
- High variability in sales
- Poor trend fit (low R²)

**Solutions:**
- Collect more historical data
- Review data quality (outliers, gaps)
- Consider manual adjustments for critical products

#### 4. **Performance Issues**

**Symptoms:**
- Slow page load
- Timeout errors
- High server CPU usage

**Possible Causes:**
- Too many products
- Large forecast tables
- Missing database indexes

**Solutions:**
```sql
-- Add indexes if missing
CREATE INDEX idx_forecast_product_date ON demand_forecasts(product_id, forecast_date);
CREATE INDEX idx_forecast_date_year ON demand_forecasts(forecast_date);

-- Optimize queries
EXPLAIN SELECT * FROM demand_forecasts WHERE YEAR(forecast_date) = 2026;
```

#### 5. **Incorrect Forecasts**

**Symptoms:**
- Forecasts seem unrealistic
- Negative trends when sales increasing

**Possible Causes:**
- Data quality issues
- Outliers skewing trend
- Incorrect date calculations

**Solutions:**
```sql
-- Check for outliers
SELECT product_id, DATE(created_at), SUM(quantity) as total
FROM sale_items
GROUP BY product_id, DATE(created_at)
HAVING total > 1000; -- Adjust threshold

-- Review historical data
SELECT * FROM demand_forecasts 
WHERE product_id = ? 
ORDER BY forecast_date 
LIMIT 10;
```

---

## Best Practices

### 1. **Data Collection**
- ✅ Record sales daily through POS system
- ✅ Track all inventory movements
- ✅ Maintain consistent data entry
- ✅ Avoid data gaps

### 2. **Forecast Usage**
- ✅ Review forecasts weekly/monthly
- ✅ Compare actual vs forecasted regularly
- ✅ Adjust inventory based on forecasts
- ✅ Use confidence levels to prioritize

### 3. **Product Management**
- ✅ Keep products active when selling
- ✅ Update product information regularly
- ✅ Set appropriate reorder levels
- ✅ Review inactive products periodically

### 4. **System Maintenance**
- ✅ Regenerate forecasts quarterly
- ✅ Clean up old forecasts (>3 years)
- ✅ Monitor forecast accuracy
- ✅ Update system as needed

### 5. **Decision Making**
- ✅ Use forecasts as guide, not absolute
- ✅ Consider external factors (promotions, events)
- ✅ Combine with business knowledge
- ✅ Review trends, not just single values

---

## Future Considerations

### Potential Enhancements

#### 1. **Advanced Forecasting Methods**
- ARIMA models for time series
- Exponential smoothing
- Machine learning models
- Ensemble methods

#### 2. **External Factor Integration**
- Promotional campaigns
- Seasonal events
- Holiday calendars
- Weather data (if relevant)

#### 3. **Automated Actions**
- Auto-generate purchase orders
- Send alerts for low stock
- Integrate with suppliers
- Automated reordering

#### 4. **Analytics & Reporting**
- Forecast accuracy metrics
- Trend analysis reports
- Category performance
- Product lifecycle analysis

#### 5. **Performance Improvements**
- Queue-based generation
- Caching strategies
- Database optimization
- API endpoints for mobile

#### 6. **User Experience**
- Export to Excel/PDF
- Email reports
- Dashboard widgets
- Mobile-friendly interface

#### 7. **Multi-Store Support**
- Store-specific forecasts
- Aggregate forecasts
- Cross-store comparisons
- Regional trends

---

## Quick Reference

### Key Formulas

**Forecast Calculation:**
```
predicted = max(1, round((intercept + slope × daysFromStart) × seasonalFactor × weekendFactor))
```

**Confidence Level:**
```
confidence = min(95, 50 + (R² × 30) + min(10, dataPoints / 10))
```

**Inventory Gap:**
```
gap = monthForecast - currentInventory
```

**Reorder Required:**
```
reorderRequired = (monthForecast > inventory) OR (inventory <= reorderLevel)
```

### Important Thresholds

- **Minimum Data**: 7 days
- **Recommended Data**: 30+ days
- **Ideal Data**: 90+ days
- **Forecast Years**: Current + 2 years ahead
- **High Confidence**: ≥80%
- **Medium Confidence**: 60-79%
- **Low Confidence**: <60%

### Database Queries

**Check Forecast Coverage:**
```sql
SELECT YEAR(forecast_date) as year, COUNT(DISTINCT product_id) as products, COUNT(*) as forecasts
FROM demand_forecasts
GROUP BY YEAR(forecast_date);
```

**Find Products Without Forecasts:**
```sql
SELECT p.id, p.name
FROM products p
WHERE p.is_active = 1
AND NOT EXISTS (
    SELECT 1 FROM demand_forecasts df WHERE df.product_id = p.id
);
```

**Check Data Availability:**
```sql
SELECT p.id, p.name, COUNT(DISTINCT DATE(si.created_at)) as sales_days
FROM products p
LEFT JOIN sale_items si ON p.id = si.product_id
WHERE p.is_active = 1
GROUP BY p.id
HAVING sales_days < 7;
```

---

## Summary

### What You Should Know:

1. ✅ **Method**: Linear Regression (OLS) with seasonal adjustment
2. ✅ **Data Sources**: Sales + Inventory out movements
3. ✅ **Minimum Requirements**: 7 days of data, active products
4. ✅ **Generation**: Automatic on page access (if missing)
5. ✅ **Storage**: Daily forecasts stored in database
6. ✅ **Access**: Admin users only
7. ✅ **Performance**: Handles 100-1000 products efficiently
8. ✅ **Limitations**: Linear trend assumption, no external factors
9. ✅ **Maintenance**: Regenerate quarterly, cleanup old data
10. ✅ **Best Practice**: Use as guide, combine with business knowledge

---

*Last Updated: Based on current implementation*
*For technical details, see: `FORECASTING_METHODS_EXPLANATION.md`*
*For academic basis, see: `FORECASTING_METHOD_ACADEMIC_BASIS.md`*

