@extends('layouts.app')

@section('title', 'Dashboard')

@section('styles')
<style>
    .stat-card,
    .stat-card.success,
    .stat-card.info,
    .stat-card.warning {
        background: #852E4E !important;
        color: white !important;
        border-color: #852E4E !important;
    }
    .badge.bg-info,
    .badge.bg-warning,
    .badge.bg-danger,
    .badge.bg-success {
        background-color: #852E4E !important;
        color: white !important;
        border-color: #852E4E !important;
    }
    .btn-warning,
    .btn-info {
        background-color: #852E4E !important;
        border-color: #852E4E !important;
        color: white !important;
    }
    .btn-warning:hover,
    .btn-info:hover {
        background-color: #4C1D3D !important;
        border-color: #4C1D3D !important;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <h2 class="mb-4"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Total Products</h5>
                    <h2 class="mb-0">{{ $stats['total_products'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card success">
                <div class="card-body">
                    <h5 class="card-title">Revenue Today</h5>
                    <h2 class="mb-0">₱{{ number_format($stats['revenue_today'], 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card info">
                <div class="card-body">
                    <h5 class="card-title">Sales Today</h5>
                    <h2 class="mb-0">{{ $stats['total_sales_today'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card warning">
                <div class="card-body">
                    <h5 class="card-title">Low Stock Items</h5>
                    <h2 class="mb-0">{{ $stats['low_stock_products'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Recent Sales</h5>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Sale #</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Cashier</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recent_sales as $sale)
                                <tr>
                                    <td>{{ $sale->sale_number }}</td>
                                    <td>{{ $sale->created_at->setTimezone('Asia/Manila')->format('M d, Y') }}</td>
                                    <td>{{ $sale->created_at->setTimezone('Asia/Manila')->format('h:i:s A') }}</td>
                                    <td>{{ $sale->user->name }}</td>
                                    <td>₱{{ number_format($sale->total, 2) }}</td>
                                    <td><span class="badge bg-info">{{ ucfirst($sale->payment_method) }}</span></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No sales yet</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Low Stock Products</h5>
                    @if($low_stock_products->count() > 0)
                    <a href="{{ route('products.index', ['low_stock' => 1]) }}" class="btn btn-sm btn-warning">
                        View All ({{ $low_stock_products->count() }})
                    </a>
                    @endif
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    @forelse($low_stock_products as $product)
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                        <div>
                            <strong>{{ $product->name }}</strong><br>
                            <small class="text-muted">
                                Stock: {{ number_format($product->quantity, 0) }} {{ $product->unit }} | 
                                Reorder: {{ number_format($product->reorder_level, 0) }} {{ $product->unit }}
                            </small>
                        </div>
                        <span class="badge bg-{{ $product->quantity == 0 ? 'danger' : 'warning' }}">
                            {{ $product->quantity == 0 ? 'Out' : 'Low' }}
                        </span>
                    </div>
                    @empty
                    <p class="text-muted text-center">No low stock items</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

