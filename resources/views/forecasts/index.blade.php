@extends('layouts.app')

@section('title', 'Demand Forecasts')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h2 class="mb-0"><i class="fas fa-chart-line me-2"></i>Demand Forecasts</h2>
        <div class="d-flex gap-2 flex-wrap">
            <form method="GET" action="{{ route('forecasts.index') }}" class="row g-2 align-items-center" id="table-filter-form">
                <input type="hidden" name="graph_category" value="{{ request('graph_category') }}">
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
                            <th>Product</th>
                            <th>Category</th>
                            <th class="text-end">Total Forecast ({{ $year }})</th>
                            <th class="text-end">Avg / Month</th>
                            <th class="text-end">Peak Month</th>
                            <th class="text-end">Avg Confidence</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($forecastProducts as $product)
                        @php
                            $forecasts = $product->demandForecasts;
                            $total = (int) ($product->forecast_total ?? 0);
                            $monthsCount = max($forecasts->count(), 1);
                            $avg = $monthsCount ? round($total / $monthsCount, 2) : 0;
                            $peak = $forecasts->sortByDesc('predicted_demand')->first();
                            $avgConfidence = $forecasts->avg('confidence_level') ?? 0;
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $product->name }}</div>
                                <div class="text-muted small">{{ $product->sku }}</div>
                            </td>
                            <td>{{ $product->category->name ?? 'N/A' }}</td>
                            <td class="text-end"><span class="badge bg-primary">{{ $total }}</span></td>
                            <td class="text-end">{{ $avg }}</td>
                            <td class="text-end">
                                @if($peak)
                                    {{ $peak->forecast_date->format('M') }} ({{ $peak->predicted_demand }})
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($avgConfidence, 1) }}%</td>
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

    {{-- Graph Filter Section --}}
    <div class="card mt-4">
        <div class="card-body">
            <h5 class="mb-3"><i class="fas fa-chart-line me-2"></i>Graph Settings</h5>
            <form method="GET" action="{{ route('forecasts.index') }}" class="row g-2 align-items-center" id="graph-filter-form">
                <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                <input type="hidden" name="year" value="{{ request('year', $year) }}">
                <input type="hidden" name="month" value="{{ request('month', $selectedMonth) }}">
                <input type="hidden" name="confidence" value="{{ request('confidence') }}">
                <input type="hidden" name="sort" value="{{ request('sort', $sort) }}">
                <input type="hidden" name="search" value="{{ request('search') }}">
                <div class="col-auto">
                    <label class="form-label mb-0"><strong>Graph Category</strong></label>
                    <select name="graph_category" class="form-select" onchange="this.form.submit()">
                        <option value="">All Categories (Separate Graphs)</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('graph_category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }} Only
                        </option>
                        @endforeach
                    </select>
                </div>
                @if(request('graph_category'))
                <div class="col-auto align-self-end">
                    <a href="{{ route('forecasts.index', array_merge(request()->except('graph_category'), ['graph_category' => ''])) }}" class="btn btn-outline-secondary btn-sm" title="Clear Graph Filter">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
                @endif
            </form>
        </div>
    </div>

    {{-- Display separate graphs for each category OR single graph if category filter is selected --}}
    @if(isset($forecastData))
        @if(isset($forecastData['graphs_by_category']) && !empty($forecastData['graphs_by_category']))
            {{-- Multiple graphs - one for each category --}}
            @foreach($forecastData['graphs_by_category'] as $catId => $graphData)
                @if(!empty($graphData['datasets']))
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            {{ $graphData['category_name'] ?? 'Category' }} - Best-Selling Products
                        </h5>
                        <small class="text-white-50">
                            Top products from this category for {{ date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $year)) }}
                        </small>
                    </div>
                    <div class="card-body">
                        <canvas id="forecastChart{{ $catId }}" height="80"></canvas>
                    </div>
                </div>
                @endif
            @endforeach
        @elseif(!empty($forecastData['datasets']))
            {{-- Single graph - when category filter is selected --}}
            <div class="card mt-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        {{ $forecastData['category_name'] ?? 'Best-Selling Products' }} - Demand Forecast
                    </h5>
                    <small class="text-white-50">
                        Top products for {{ date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $year)) }}
                    </small>
                </div>
                <div class="card-body">
                    <canvas id="forecastChart" height="80"></canvas>
                </div>
            </div>
        @endif
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

@if(isset($forecastData))
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Preserve graph_category when submitting table filter form
const tableFilterForm = document.getElementById('table-filter-form');
if (tableFilterForm) {
    tableFilterForm.addEventListener('submit', function(e) {
        const graphCategory = new URLSearchParams(window.location.search).get('graph_category');
        if (graphCategory) {
            const hiddenInput = this.querySelector('input[name="graph_category"]');
            if (hiddenInput) {
                hiddenInput.value = graphCategory;
            }
        }
    });
}

// Preserve table filters when submitting graph filter form
const graphFilterForm = document.getElementById('graph-filter-form');
if (graphFilterForm) {
    graphFilterForm.addEventListener('submit', function(e) {
        const urlParams = new URLSearchParams(window.location.search);
        const tableFilters = ['category_id', 'year', 'month', 'confidence', 'sort', 'search'];
        
        tableFilters.forEach(function(filter) {
            const value = urlParams.get(filter);
            if (value) {
                const hiddenInput = graphFilterForm.querySelector('input[name="' + filter + '"]');
                if (hiddenInput) {
                    hiddenInput.value = value;
                }
            }
        });
    });
}

const forecastData = @json($forecastData);

// Function to create a chart
function createChart(canvasId, data, categoryName) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    
    new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                title: {
                    display: true,
                    text: categoryName + ' - Daily Demand Forecast for {{ date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $year)) }}',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                legend: {
                    display: true,
                    position: 'right',
                    labels: {
                        font: {
                            size: 11
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 10,
                    callbacks: {
                        title: function(context) {
                            return '📅 ' + context[0].label;
                        },
                        label: function(context) {
                            const label = context.dataset.label || '';
                            const value = context.parsed.y || 0;
                            return '  ' + label + ': ' + value.toLocaleString() + ' units';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Forecasted Demand (Units)',
                        font: {
                            weight: 'bold'
                        }
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
                        text: 'Date',
                        font: {
                            weight: 'bold'
                        }
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

// Create charts based on data structure
@if(isset($forecastData['graphs_by_category']) && !empty($forecastData['graphs_by_category']))
    // Multiple graphs - one for each category
    @foreach($forecastData['graphs_by_category'] as $catId => $graphData)
        @if(!empty($graphData['datasets']))
        createChart('forecastChart{{ $catId }}', @json($graphData), '{{ $graphData['category_name'] ?? 'Category' }}');
        @endif
    @endforeach
@elseif(!empty($forecastData['datasets']))
    // Single graph - when category filter is selected
    createChart('forecastChart', @json($forecastData), '{{ $forecastData['category_name'] ?? 'Best-Selling Products' }}');
@endif
</script>
@endsection
@endif
@endsection


