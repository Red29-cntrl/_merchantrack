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

{{-- Low Stock Alert Modal (Auto-show for Admin) --}}
@if(auth()->user()->isAdmin() && $low_stock_products->count() > 0)
<div class="modal fade" id="lowStockAlertModal" tabindex="-1" aria-labelledby="lowStockAlertModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #852E4E; color: white;">
                <h5 class="modal-title" id="lowStockAlertModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alert
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">
                    <strong>You have {{ $low_stock_products->count() }} product(s) with low stock or out of stock.</strong>
                </p>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($low_stock_products->take(20) as $product)
                            <tr>
                                <td>
                                    <strong>{{ $product->name }}</strong>
                                    @if($product->sku)
                                    <br><small class="text-muted">SKU: {{ $product->sku }}</small>
                                    @endif
                                </td>
                                <td>{{ $product->category->name ?? 'N/A' }}</td>
                                <td>
                                    <span class="fw-bold text-dark">
                                        {{ number_format($product->quantity, 0) }} {{ $product->unit }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $product->quantity == 0 ? 'danger' : 'warning' }}">
                                        {{ $product->quantity == 0 ? 'Out of Stock' : 'Low Stock' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($low_stock_products->count() > 20)
                <div class="mt-3 text-center">
                    <p class="text-muted mb-2">Showing first 20 items. Total: {{ $low_stock_products->count() }} items.</p>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <a href="{{ route('products.index', ['low_stock' => 1]) }}" class="btn btn-warning">
                    <i class="fas fa-boxes me-2"></i>View All Low Stock Products
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show the low stock alert modal automatically for admin
    const lowStockModal = new bootstrap.Modal(document.getElementById('lowStockAlertModal'));
    lowStockModal.show();
});
</script>
@endif
@endsection

