@extends('layouts.app')

@section('title', 'Dashboard')

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
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Recent Sales</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Sale #</th>
                                    <th>Date</th>
                                    <th>Cashier</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recent_sales as $sale)
                                <tr>
                                    <td>{{ $sale->sale_number }}</td>
                                    <td>{{ $sale->created_at->format('M d, Y H:i') }}</td>
                                    <td>{{ $sale->user->name }}</td>
                                    <td>₱{{ number_format($sale->total, 2) }}</td>
                                    <td><span class="badge bg-info">{{ ucfirst($sale->payment_method) }}</span></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">No sales yet</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Low Stock Products</h5>
                </div>
                <div class="card-body">
                    @forelse($low_stock_products as $product)
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                        <div>
                            <strong>{{ $product->name }}</strong><br>
                            <small class="text-muted">Stock: {{ $product->quantity }}</small>
                        </div>
                        <span class="badge bg-warning">Low</span>
                    </div>
                    @empty
                    <p class="text-muted">No low stock items</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

