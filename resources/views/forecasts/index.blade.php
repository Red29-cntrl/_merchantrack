@extends('layouts.app')

@section('title', 'Demand Forecasts')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h2 class="mb-0"><i class="fas fa-chart-line me-2"></i>Demand Forecasts</h2>
        <div class="d-flex gap-2 flex-wrap">
            <form method="GET" action="{{ route('forecasts.index') }}" class="row g-2 align-items-center" id="table-filter-form">
                <input type="hidden" name="category_trend" value="{{ request('category_trend', $selectedCategoryId ?? '') }}">
                <div class="col-auto">
                    <label class="form-label mb-0 small text-muted">Category</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label mb-0 small text-muted">Year</label>
                    <select name="year" class="form-select form-select-sm">
                        @foreach($availableYears as $yr)
                        <option value="{{ $yr }}" {{ (int)$year === (int)$yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label mb-0 small text-muted">Month</label>
                    <select name="month" class="form-select form-select-sm">
                        @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ (int)$selectedMonth === $m ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                        </option>
                        @endfor
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label mb-0 small text-muted">Confidence</label>
                    <select name="confidence" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="90" {{ $confidenceKey === '90' ? 'selected' : '' }}>≥ 90%</option>
                        <option value="75" {{ $confidenceKey === '75' ? 'selected' : '' }}>≥ 75%</option>
                        <option value="50" {{ $confidenceKey === '50' ? 'selected' : '' }}>≥ 50%</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label mb-0 small text-muted">Sort</label>
                    <select name="sort" class="form-select form-select-sm">
                        <option value="desc" {{ $sort === 'desc' ? 'selected' : '' }}>Highest forecast</option>
                        <option value="asc" {{ $sort === 'asc' ? 'selected' : '' }}>Lowest forecast</option>
                    </select>
                </div>
                <div class="col-auto flex-grow-1">
                    <label class="form-label mb-0 small text-muted">Search</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" placeholder="Product or SKU" value="{{ $search }}">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-auto align-self-end">
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary btn-sm" type="submit">
                            <i class="fas fa-filter me-1"></i>Apply
                        </button>
                        @php
                            $hasActiveFilters = request('category_id') || 
                                                (request('year') && (int)request('year') !== (int)date('Y')) ||
                                                (request('month') && (int)request('month') !== 1) ||
                                                request('confidence') ||
                                                (request('sort') && request('sort') !== 'desc') ||
                                                request('search');
                        @endphp
                        @if($hasActiveFilters)
                        <a href="{{ route('forecasts.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                            <i class="fas fa-times"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>
                                Product
                                <i class="fas fa-info-circle text-muted ms-1" 
                                   data-bs-toggle="tooltip" 
                                   data-bs-placement="top" 
                                   data-bs-title="The product name and SKU being evaluated for demand forecasting."
                                   style="font-size: 0.85em; cursor: help;"></i>
                            </th>
                            <th>
                                Category
                                <i class="fas fa-info-circle text-muted ms-1" 
                                   data-bs-toggle="tooltip" 
                                   data-bs-placement="top" 
                                   data-bs-title="The product category used for filtering and organization."
                                   style="font-size: 0.85em; cursor: help;"></i>
                            </th>
                            <th class="text-end">
                                Month Forecast
                                <i class="fas fa-info-circle text-muted ms-1" 
                                   data-bs-toggle="tooltip" 
                                   data-bs-placement="top" 
                                   data-bs-title="The predicted quantity of this product expected to be sold for the selected month, based on historical sales data."
                                   style="font-size: 0.85em; cursor: help;"></i>
                            </th>
                            <th class="text-end">
                                Current Inventory
                                <i class="fas fa-info-circle text-muted ms-1" 
                                   data-bs-toggle="tooltip" 
                                   data-bs-placement="top" 
                                   data-bs-title="The current available stock level of the product in inventory."
                                   style="font-size: 0.85em; cursor: help;"></i>
                            </th>
                            <th class="text-end">
                                Inventory Gap
                                <i class="fas fa-info-circle text-muted ms-1" 
                                   data-bs-toggle="tooltip" 
                                   data-bs-placement="top" 
                                   data-bs-title="The difference between forecasted demand and current inventory. A positive value indicates potential shortage, while a negative value indicates surplus."
                                   style="font-size: 0.85em; cursor: help;"></i>
                            </th>
                            <th class="text-end">
                                Reorder Required
                                <i class="fas fa-info-circle text-muted ms-1" 
                                   data-bs-toggle="tooltip" 
                                   data-bs-placement="top" 
                                   data-bs-title="Indicates whether additional stock should be ordered based on forecasted demand and current inventory."
                                   style="font-size: 0.85em; cursor: help;"></i>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($forecastProducts as $product)
                        @php
                            $monthForecast = $product->month_forecast ?? 0;
                            $inventoryGap = $product->inventory_gap ?? 0;
                            $reorderRequired = $product->reorder_required ?? false;
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $product->name }}</div>
                                <div class="text-muted small">{{ $product->sku }}</div>
                            </td>
                            <td>{{ $product->category->name ?? 'N/A' }}</td>
                            <td class="text-end"><span class="badge bg-info">{{ number_format($monthForecast) }}</span></td>
                            <td class="text-end">{{ number_format($product->quantity) }}</td>
                            <td class="text-end">
                                <span class="badge bg-{{ $inventoryGap > 0 ? 'danger' : 'success' }}">
                                    {{ $inventoryGap > 0 ? '+' : '' }}{{ number_format($inventoryGap) }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if($reorderRequired)
                                    <span class="badge bg-warning text-dark">Yes</span>
                                @else
                                    <span class="badge bg-success">No</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No forecast data available</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $forecastProducts->links() }}
        </div>
    </div>


    {{-- Actual vs Forecasted Monthly Demand Line Chart --}}
    @if(isset($actualVsForecastedData) && !empty($actualVsForecastedData['datasets']))
    <div class="card mt-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-chart-line me-2"></i>
                Actual vs Forecasted Monthly Demand
            </h5>
            <small class="text-white-50">
                Historical actual sales vs forecasted demand over time
            </small>
        </div>
        <div class="card-body">
            <canvas id="actualVsForecastedChart" height="80"></canvas>
        </div>
    </div>
    @endif

    {{-- Forecasted Demand per Product Bar Chart --}}
    @if(isset($forecastPerProductData) && !empty($forecastPerProductData['datasets']))
    <div class="card mt-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="fas fa-chart-bar me-2"></i>
                Forecasted Demand per Product
            </h5>
            <small class="text-white-50">
                Top products by forecasted demand for {{ date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $year)) }}
            </small>
        </div>
        <div class="card-body">
            <canvas id="forecastPerProductChart" height="80"></canvas>
        </div>
    </div>
    @endif

    {{-- Forecasted Demand vs Inventory Bar Chart --}}
    @if(isset($forecastVsInventoryData) && !empty($forecastVsInventoryData['datasets']))
    <div class="card mt-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="fas fa-chart-bar me-2"></i>
                Forecasted Demand vs Current Inventory
            </h5>
            <small class="text-dark">
                Comparison of forecasted demand and current inventory levels for {{ date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $year)) }}
            </small>
        </div>
        <div class="card-body">
            <canvas id="forecastVsInventoryChart" height="80"></canvas>
        </div>
    </div>
    @endif

    {{-- Dynamic Category Product Trend Graph --}}
    @if(isset($categoriesWithSales) && $categoriesWithSales->isNotEmpty() && isset($categoryTrendData))
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Category Product Trend
                    </h5>
                    <small class="text-white-50">
                        Actual vs Forecasted Monthly Demand by Category
                    </small>
                </div>
                <div class="ms-3">
                    {{-- Data-driven category selector: only shows categories with sales data --}}
                    <form method="GET" action="{{ route('forecasts.index') }}" class="d-inline" id="category-trend-form">
                        <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                        <input type="hidden" name="year" value="{{ request('year', $year) }}">
                        <input type="hidden" name="month" value="{{ request('month', $selectedMonth) }}">
                        <input type="hidden" name="confidence" value="{{ request('confidence') }}">
                        <input type="hidden" name="sort" value="{{ request('sort', $sort) }}">
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <select name="category_trend" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 250px;">
                            @foreach($categoriesWithSales as $cat)
                            <option value="{{ $cat->id }}" {{ $selectedCategoryId == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body">
            <canvas id="categoryTrendChart" height="80"></canvas>
            <p class="text-muted small mt-2 mb-0">
                <i class="fas fa-info-circle"></i> 
                <strong>Note:</strong> Categories appear in the selector only if they have historical sales data. 
                When a category receives sales in the future, it will automatically appear without code changes.
            </p>
        </div>
    </div>
    @endif

    @if(isset($forecastSummary) && !empty($forecastSummary))
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-table me-2"></i>Forecast Summary Table</h5>
            <small class="text-muted">Monthly demand forecast using Trend Projection Method</small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>SKU</th>
                            <th>Product Name</th>
                            <th class="text-end">Last Month Sales</th>
                            <th class="text-end">Forecasted Demand (Next Month)</th>
                            <th class="text-end">Lower Bound</th>
                            <th class="text-end">Upper Bound</th>
                            <th class="text-end">Percentage Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($forecastSummary as $summary)
                        <tr>
                            <td><strong>{{ $summary['sku'] }}</strong></td>
                            <td>{{ $summary['product_name'] }}</td>
                            <td class="text-end">{{ number_format($summary['last_month_sales']) }}</td>
                            <td class="text-end">
                                <span class="badge bg-primary">{{ number_format($summary['forecasted_demand']) }}</span>
                            </td>
                            <td class="text-end text-muted">{{ number_format($summary['lower_bound']) }}</td>
                            <td class="text-end text-muted">{{ number_format($summary['upper_bound']) }}</td>
                            <td class="text-end">
                                <span class="badge bg-{{ $summary['percentage_change'] >= 0 ? 'success' : 'danger' }}">
                                    {{ $summary['percentage_change'] >= 0 ? '+' : '' }}{{ number_format($summary['percentage_change'], 2) }}%
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Generate Forecast Modal -->
<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('forecasts.generate') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Generate Demand Forecast</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Product</label>
                        <select class="form-select" id="product_id" name="product_id" required>
                            <option value="">Select Product</option>
                            @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="forecast_date" class="form-label">Forecast Date</label>
                        <input type="date" class="form-control" id="forecast_date" name="forecast_date" 
                               min="{{ date('Y-m-d', strtotime('+1 day')) }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Generate Forecast</button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Initialize Bootstrap tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
// Preserve category_trend when submitting table filter form
const tableFilterForm = document.getElementById('table-filter-form');
if (tableFilterForm) {
    tableFilterForm.addEventListener('submit', function(e) {
        const categoryTrend = new URLSearchParams(window.location.search).get('category_trend');
        if (categoryTrend) {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'category_trend';
            hiddenInput.value = categoryTrend;
            this.appendChild(hiddenInput);
        }
    });
}

// Create Actual vs Forecasted chart
@if(isset($actualVsForecastedData) && !empty($actualVsForecastedData['datasets']))
    const actualVsForecastedCtx = document.getElementById('actualVsForecastedChart');
    if (actualVsForecastedCtx) {
        new Chart(actualVsForecastedCtx.getContext('2d'), {
            type: 'line',
            data: @json($actualVsForecastedData),
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Actual vs Forecasted Monthly Demand',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Demand (Units)',
                            font: { weight: 'bold' }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month',
                            font: { weight: 'bold' }
                        }
                    }
                }
            }
        });
    }
@endif

// Create Forecast per Product bar chart
@if(isset($forecastPerProductData) && !empty($forecastPerProductData['datasets']))
    const forecastPerProductCtx = document.getElementById('forecastPerProductChart');
    if (forecastPerProductCtx) {
        new Chart(forecastPerProductCtx.getContext('2d'), {
            type: 'bar',
            data: @json($forecastPerProductData),
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Forecasted Demand per Product',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Forecasted Demand (Units)',
                            font: { weight: 'bold' }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Product',
                            font: { weight: 'bold' }
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });
    }
@endif

// Create Forecast vs Inventory bar chart
@if(isset($forecastVsInventoryData) && !empty($forecastVsInventoryData['datasets']))
    const forecastVsInventoryCtx = document.getElementById('forecastVsInventoryChart');
    if (forecastVsInventoryCtx) {
        new Chart(forecastVsInventoryCtx.getContext('2d'), {
            type: 'bar',
            data: @json($forecastVsInventoryData),
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Forecasted Demand vs Current Inventory',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Quantity (Units)',
                            font: { weight: 'bold' }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Product',
                            font: { weight: 'bold' }
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });
    }
@endif

// Create Dynamic Category Product Trend chart
@if(isset($categoryTrendData) && !empty($categoryTrendData['datasets']))
    const categoryTrendCtx = document.getElementById('categoryTrendChart');
    if (categoryTrendCtx) {
        new Chart(categoryTrendCtx.getContext('2d'), {
            type: 'line',
            data: @json($categoryTrendData),
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    title: {
                        display: true,
                        text: '{{ $categoryTrendData['category_name'] ?? 'Category' }} - Actual vs Forecasted Monthly Demand',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            title: function(context) {
                                return '📅 ' + context[0].label;
                            },
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = context.parsed.y !== null ? context.parsed.y.toLocaleString() : 'N/A';
                                return '  ' + label + ': ' + value + ' units';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Quantity (Units)',
                            font: { weight: 'bold' }
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month',
                            font: { weight: 'bold' }
                        }
                    }
                }
            }
        });
    }
@endif
</script>
@endsection

{{-- Initialize Bootstrap tooltips for column headers --}}
<script>
// Initialize Bootstrap tooltips for information icons
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection


