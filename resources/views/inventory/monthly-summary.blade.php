@extends('layouts.app')

@section('title', 'Monthly Summary')

@section('styles')
<style>
    .month-card {
        transition: all 0.3s ease;
        border-radius: 12px;
        overflow: hidden;
    }
    .month-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(133, 46, 78, 0.15) !important;
    }
    .stat-item {
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .stat-item:last-child {
        border-bottom: none;
    }
    .stat-label {
        font-size: 0.875rem;
        color: #6c757d;
        font-weight: 500;
    }
    .stat-value {
        font-size: 1rem;
        font-weight: 600;
    }
    .filter-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-radius: 12px;
        border: 1px solid #e9ecef;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="fas fa-chart-bar me-2"></i>Monthly Summary</h2>
            <p class="text-muted mb-0">View inventory movements summary by month</p>
        </div>
        <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i>Back to Inventory
        </a>
    </div>

    <div class="card mb-4 filter-card shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex align-items-center mb-3">
                <h6 class="mb-0 fw-bold">Filter by Year</h6>
            </div>
            <form method="GET" action="{{ route('inventory.monthly-summary') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Year</label>
                    <select name="year" class="form-select shadow-sm">
                        @foreach($availableYears as $year)
                        <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1 shadow-sm">
                        <i class="fas fa-filter me-1"></i>Apply Filter
                    </button>
                    <a href="{{ route('inventory.monthly-summary') }}" class="btn btn-outline-secondary shadow-sm" title="Clear Filters">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Line Graph --}}
    @if($monthlySummary->count() > 0)
    <div class="card mb-4 shadow-sm">
        <div class="card-body p-4">
            <h5 class="mb-3 fw-bold">Monthly Trends ({{ $selectedYear }})</h5>
            <canvas id="monthlyChart" height="80"></canvas>
        </div>
    </div>
    @endif

    <div class="card mb-4 shadow-sm">
        <div class="card-body p-4">
            <div class="mb-4">
                <div>
                    <h5 class="mb-1 fw-bold">Monthly Summary ({{ $selectedYear }})</h5>
                    <small class="text-muted">Overview of inventory movements for each month</small>
                </div>
            </div>
            <div class="row row-cols-1 row-cols-md-3 row-cols-xl-4 g-3">
                @forelse($monthlySummary as $summary)
                @php
                    $monthName = \Carbon\Carbon::create($summary->year, $summary->month, 1)->format('F');
                @endphp
                <div class="col">
                    <div class="card h-100 month-card" style="border: 1px solid #e9ecef;">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="text-muted small mb-1">{{ $summary->year }}</div>
                                    <h6 class="mb-0 fw-bold">{{ $monthName }}</h6>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="stat-item d-flex justify-content-between align-items-center">
                                    <span class="stat-label">In</span>
                                    <span class="stat-value text-success">{{ number_format($summary->total_in) }}</span>
                                </div>
                                <div class="stat-item d-flex justify-content-between align-items-center">
                                    <span class="stat-label">Out</span>
                                    <span class="stat-value text-danger">{{ number_format($summary->total_out) }}</span>
                                </div>
                                <div class="stat-item d-flex justify-content-between align-items-center">
                                    <span class="stat-label">Adjust</span>
                                    <span class="stat-value text-info">{{ number_format($summary->total_adjustment) }}</span>
                                </div>
                                <div class="stat-item d-flex justify-content-between align-items-center">
                                    <span class="stat-label">Net</span>
                                    <span class="stat-value {{ $summary->net_change >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $summary->net_change >= 0 ? '+' : '' }}{{ number_format($summary->net_change) }}
                                    </span>
                                </div>
                            </div>
                            <a href="{{ route('inventory.index', ['month' => $summary->month, 'year' => $summary->year]) }}" class="btn btn-sm w-100 mt-3 shadow-sm" style="background-color: #852E4E; border-color: #852E4E; color: #ffffff; border-radius: 8px; font-weight: 500;" onmouseover="this.style.backgroundColor='#4C1D3D'; this.style.borderColor='#4C1D3D';" onmouseout="this.style.backgroundColor='#852E4E'; this.style.borderColor='#852E4E';">
                                View {{ $monthName }} Details
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="alert alert-light border text-center py-4">
                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                        <p class="mb-0">No movements recorded for {{ $selectedYear }}.</p>
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@if($monthlySummary->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthlyData = @json($monthlySummary->sortBy('month')->values());
    
    const months = monthlyData.map(item => {
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return monthNames[item.month - 1];
    });
    
    const inData = monthlyData.map(item => item.total_in);
    const outData = monthlyData.map(item => item.total_out);
    const adjustmentData = monthlyData.map(item => item.total_adjustment);
    const netData = monthlyData.map(item => item.net_change);
    
    const ctx = document.getElementById('monthlyChart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Stock In',
                        data: inData,
                        borderColor: 'rgb(40, 167, 69)',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'Stock Out',
                        data: outData,
                        borderColor: 'rgb(220, 53, 69)',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'Adjustment',
                        data: adjustmentData,
                        borderColor: 'rgb(23, 162, 184)',
                        backgroundColor: 'rgba(23, 162, 184, 0.1)',
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'Net Change',
                        data: netData,
                        borderColor: 'rgb(133, 46, 78)',
                        backgroundColor: 'rgba(133, 46, 78, 0.1)',
                        tension: 0.4,
                        fill: false,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }
});
</script>
@endif
@endsection

