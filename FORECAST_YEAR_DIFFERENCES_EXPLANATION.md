# Why Forecast Values Differ Across Years (2026, 2027, 2028)

## Question
**"Why are there different forecast values for 2026, 2027, and 2028 when the historical sales data is the same?"**

## Answer

Even though the **historical sales data is identical**, the forecasted values differ because the system uses **Linear Regression Trend Projection** that projects demand **further into the future** based on the calculated trend.

---

## The Core Formula

The forecast uses this formula:
```
predicted_demand = intercept + (slope × daysFromStart) × seasonalFactor × weekendFactor
```

Where:
- **`intercept`** = Base demand level (calculated from historical data)
- **`slope`** = Daily trend (positive = increasing, negative = decreasing, zero = stable)
- **`daysFromStart`** = Number of days from the **first historical sales date** to the **forecast date**
- **`seasonalFactor`** = Monthly pattern adjustment (same for same month across years)
- **`weekendFactor`** = Weekend adjustment (0.85 for weekends, 1.0 for weekdays)

---

## Why Values Differ Across Years

### Example Scenario:
- **First historical sale date:** January 1, 2025
- **Historical data:** Same for all calculations
- **Calculated slope:** +0.5 units per day (increasing trend)
- **Calculated intercept:** 100 units

### Forecast Calculations:

**For January 1, 2026:**
- `daysFromStart` = 365 days (from Jan 1, 2025 to Jan 1, 2026)
- `baseProjection` = 100 + (0.5 × 365) = **282.5 units**

**For January 1, 2027:**
- `daysFromStart` = 730 days (from Jan 1, 2025 to Jan 1, 2027)
- `baseProjection` = 100 + (0.5 × 730) = **465 units**

**For January 1, 2028:**
- `daysFromStart` = 1095 days (from Jan 1, 2025 to Jan 1, 2028)
- `baseProjection` = 100 + (0.5 × 1095) = **647.5 units**

**Result:** Even with identical historical data, forecasts increase over time because the trend is projected further into the future.

---

## Impact on Each Column

### 1. **Month Forecast** (Different)
- **Why:** Calculated using `daysFromStart` which increases for later years
- **Formula:** `predicted = intercept + (slope × daysFromStart) × seasonal × weekend`
- **Example:** 2026 = 282 units, 2027 = 465 units, 2028 = 647 units

### 2. **Forecast Reliability** (Usually Similar, May Slight Differ)
- **Why:** Based on R-squared and data points from historical data
- **Note:** Should be similar across years since it uses the same historical data
- **Minor differences:** May occur due to rounding or if confidence calculation considers forecast distance

### 3. **Current Inventory** (Same)
- **Why:** This is the **current stock level** in the database
- **Note:** This value is the same regardless of which year you're viewing
- **Example:** If you have 500 units in stock, it shows 500 for all years

### 4. **Inventory Gap** (Different)
- **Why:** Calculated as `Month Forecast - Current Inventory`
- **Formula:** `inventory_gap = monthForecast - product->quantity`
- **Example:**
  - 2026: 282 - 500 = **-218** (surplus)
  - 2027: 465 - 500 = **-35** (small surplus)
  - 2028: 647 - 500 = **+147** (shortage)

### 5. **Reorder Required** (Different)
- **Why:** Based on `monthForecast > currentInventory OR quantity <= reorder_level`
- **Logic:** If forecasted demand exceeds inventory OR inventory is at/below reorder level
- **Example:**
  - 2026: 282 < 500 → **No reorder** (unless below reorder level)
  - 2027: 465 < 500 → **No reorder** (unless below reorder level)
  - 2028: 647 > 500 → **Reorder required**

---

## Visual Example

```
Historical Data (Same for all):
┌─────────────────────────────────────┐
│ Sales from Jan 1, 2025 to Dec 31, 2025 │
│ (Used to calculate slope & intercept)  │
└─────────────────────────────────────┘
         │
         │ Trend Line: y = 100 + 0.5x
         │
         ├─→ Jan 2026: 282 units (365 days ahead)
         │
         ├─→ Jan 2027: 465 units (730 days ahead)
         │
         └─→ Jan 2028: 647 units (1095 days ahead)
```

---

## Key Takeaway

**The system assumes the trend continues into the future.** If historical sales show an **increasing trend**, forecasts for later years will be **higher**. If the trend is **decreasing**, forecasts for later years will be **lower**. This is the expected behavior of trend projection forecasting.

---

## When Historical Data Changes

When you add new sales through POS:
1. The system recalculates the **slope** and **intercept** based on the updated historical data
2. Forecasts for all years (2026, 2027, 2028) will be **regenerated** with the new trend
3. The differences between years will reflect the **updated trend**

---

*Last Updated: Based on current implementation in `DemandForecastController.php`*

