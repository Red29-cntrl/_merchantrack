@extends('layouts.app')

@section('title', 'Demand Forecasts')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h2 class="mb-0"><i class="fas fa-chart-line me-2"></i>Demand Forecasts</h2>
        <div class="d-flex gap-2 flex-wrap">
            <form method="GET" action="{{ route('forecasts.index') }}" class="row g-2 align-items-center">
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
                    <button class="btn btn-primary btn-sm" type="submit">
                        <i class="fas fa-filter me-1"></i>Apply
                    </button>
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

    @if(isset($forecastData) && !empty($forecastData['datasets']))
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Demand Forecast Graph - {{ $year }} Predictions</h5>
        </div>
        <div class="card-body">
            <canvas id="forecastChart" height="100"></canvas>
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

@if(isset($forecastData) && !empty($forecastData['datasets']))
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
const ctx = document.getElementById('forecastChart').getContext('2d');
const forecastData = @json($forecastData);

new Chart(ctx, {
    type: 'line',
    data: forecastData,
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            title: {
                display: true,
                text: 'Product Sales Forecast for {{ $year }}'
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
                    text: 'Predicted Demand (Units)'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Month'
                }
            }
        }
    }
});
</script>
@endsection
@endif
@endsection

