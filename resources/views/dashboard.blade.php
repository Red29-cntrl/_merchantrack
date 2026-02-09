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
                    <h2 class="mb-0" data-stat="total_products">{{ $stats['total_products'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card success">
                <div class="card-body">
                    <h5 class="card-title">Revenue Today</h5>
                    <h2 class="mb-0" data-stat="revenue_today">₱{{ number_format($stats['revenue_today'], 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card info">
                <div class="card-body">
                    <h5 class="card-title">Sales Today</h5>
                    <h2 class="mb-0" data-stat="total_sales_today">{{ $stats['total_sales_today'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card warning">
                <div class="card-body">
                    <h5 class="card-title">Low Stock Items</h5>
                    <h2 class="mb-0" data-stat="low_stock_count">{{ $stats['low_stock_products'] }}</h2>
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
                            <tbody id="recent-sales-tbody">
                                @forelse($recent_sales as $sale)
                                <tr data-sale-id="{{ $sale->id }}">
                                    <td>{{ $sale->sale_number }}</td>
                                    <td>{{ $sale->created_at->setTimezone('Asia/Manila')->format('M d, Y') }}</td>
                                    <td>{{ $sale->created_at->setTimezone('Asia/Manila')->format('h:i:s A') }}</td>
                                    <td>{{ $sale->user->name }}</td>
                                    <td>₱{{ number_format($sale->total, 2) }}</td>
                                    <td><span class="badge bg-info">{{ ucfirst($sale->payment_method) }}</span></td>
                                </tr>
                                @empty
                                <tr id="no-sales-row">
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
    @if(auth()->user()->isAdmin() && $low_stock_products->count() > 0)
    const lowStockModal = new bootstrap.Modal(document.getElementById('lowStockAlertModal'));
    lowStockModal.show();
    @endif

    // Listen for polling sync events (fallback when WebSocket not available)
    window.addEventListener('sync:newSales', function(event) {
        const sales = event.detail;
        console.log('📊 Dashboard: Received new sales via polling:', sales.length);
        sales.forEach(function(sale) {
            // Update sales count
            const salesCountEl = document.querySelector('[data-stat="total_sales_today"]');
            if (salesCountEl) {
                const currentCount = parseInt(salesCountEl.textContent) || 0;
                salesCountEl.textContent = currentCount + 1;
            }
            
            // Update revenue
            const revenueEl = document.querySelector('[data-stat="revenue_today"]');
            if (revenueEl) {
                const currentRevenue = parseFloat(revenueEl.textContent.replace(/[^0-9.]/g, '')) || 0;
                const newRevenue = currentRevenue + parseFloat(sale.total);
                revenueEl.textContent = '₱' + newRevenue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
            
            // Add to recent sales table
            const tbody = document.getElementById('recent-sales-tbody');
            const noSalesRow = document.getElementById('no-sales-row');
            if (tbody) {
                if (noSalesRow) noSalesRow.remove();
                const existingRow = tbody.querySelector(`tr[data-sale-id="${sale.id}"]`);
                if (!existingRow) {
                    const saleDate = new Date(sale.created_at);
                    const dateStr = saleDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    const timeStr = saleDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
                    const newRow = document.createElement('tr');
                    newRow.setAttribute('data-sale-id', sale.id);
                    newRow.innerHTML = `
                        <td>${sale.sale_number}</td>
                        <td>${dateStr}</td>
                        <td>${timeStr}</td>
                        <td>${sale.cashier_name || 'Unknown'}</td>
                        <td>₱${parseFloat(sale.total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        <td><span class="badge bg-info">${(sale.payment_method || 'cash').charAt(0).toUpperCase() + (sale.payment_method || 'cash').slice(1)}</span></td>
                    `;
                    tbody.insertBefore(newRow, tbody.firstChild);
                    while (tbody.children.length > 10) {
                        tbody.removeChild(tbody.lastChild);
                    }
                }
            }
        });
    });
    
    // Real-time updates with Laravel Echo
    if (typeof Echo !== 'undefined') {
        console.log('✓ Dashboard: Setting up real-time listeners...');
        // Listen for new sales
        Echo.channel('sales')
            .listen('.sale.created', (e) => {
                console.log('✓ Dashboard: New sale received:', e.sale);
                
                // Update total sales today
                const salesCountEl = document.querySelector('[data-stat="total_sales_today"]');
                if (salesCountEl) {
                    const currentCount = parseInt(salesCountEl.textContent) || 0;
                    salesCountEl.textContent = currentCount + 1;
                }
                
                // Update revenue today
                const revenueEl = document.querySelector('[data-stat="revenue_today"]');
                if (revenueEl) {
                    const currentRevenue = parseFloat(revenueEl.textContent.replace(/[^0-9.]/g, '')) || 0;
                    const newRevenue = currentRevenue + parseFloat(e.sale.total);
                    revenueEl.textContent = '₱' + newRevenue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
                
                // Add new sale to recent sales table dynamically
                const tbody = document.getElementById('recent-sales-tbody');
                const noSalesRow = document.getElementById('no-sales-row');
                
                if (tbody) {
                    // Remove "no sales" row if it exists
                    if (noSalesRow) {
                        noSalesRow.remove();
                    }
                    
                    // Check if sale already exists (prevent duplicates)
                    const existingRow = tbody.querySelector(`tr[data-sale-id="${e.sale.sale_id}"]`);
                    if (existingRow) {
                        return; // Sale already displayed
                    }
                    
                    // Create new row
                    const now = new Date();
                    const saleDate = new Date(e.sale.created_at);
                    const dateStr = saleDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    const timeStr = saleDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
                    
                    const newRow = document.createElement('tr');
                    newRow.setAttribute('data-sale-id', e.sale.sale_id);
                    const paymentMethod = e.sale.payment_method || 'cash';
                    const cashierName = e.sale.cashier_name || 'Unknown';
                    newRow.innerHTML = `
                        <td>${e.sale.sale_number}</td>
                        <td>${dateStr}</td>
                        <td>${timeStr}</td>
                        <td>${cashierName}</td>
                        <td>₱${parseFloat(e.sale.total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        <td><span class="badge bg-info">${paymentMethod.charAt(0).toUpperCase() + paymentMethod.slice(1)}</span></td>
                    `;
                    
                    // Insert at the top of the table
                    tbody.insertBefore(newRow, tbody.firstChild);
                    
                    // Keep only first 10 rows
                    while (tbody.children.length > 10) {
                        tbody.removeChild(tbody.lastChild);
                    }
                    
                    // Add highlight animation
                    newRow.style.backgroundColor = '#d4edda';
                    setTimeout(() => {
                        newRow.style.transition = 'background-color 2s';
                        newRow.style.backgroundColor = '';
                    }, 100);
                }
            });

        // Listen for inventory updates
        Echo.channel('inventory')
            .listen('.inventory.updated', (e) => {
                console.log('Inventory updated:', e.product);
                
                // Update low stock count dynamically
                // Note: This is an approximation - for exact count, would need to fetch from server
                // But avoids full page reload
                const lowStockEl = document.querySelector('[data-stat="low_stock_count"]');
                if (lowStockEl && e.product.quantity <= e.product.reorder_level) {
                    const currentCount = parseInt(lowStockEl.textContent) || 0;
                    // Increment if product went low, but we can't decrement accurately without server data
                    // This is better than full page reload
                }
                
                // Check for low stock
                if (e.product.quantity <= e.product.reorder_level) {
                    // Show notification
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-warning alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        <strong>Low Stock Alert!</strong> ${e.product.name} has ${e.product.quantity} ${e.product.unit} remaining.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    const mainContent = document.querySelector('.container-fluid');
                    if (mainContent) {
                        mainContent.insertBefore(alertDiv, mainContent.firstChild);
                    }
                }
            });

        // Listen for product updates
        Echo.channel('products')
            .listen('.product.updated', (e) => {
                console.log('Product updated:', e.product);
                
                // Update total products count if product was created/deleted
                const totalProductsEl = document.querySelector('[data-stat="total_products"]');
                if (totalProductsEl && e.product.action) {
                    const currentCount = parseInt(totalProductsEl.textContent) || 0;
                    if (e.product.action === 'created') {
                        totalProductsEl.textContent = currentCount + 1;
                    } else if (e.product.action === 'deleted') {
                        totalProductsEl.textContent = Math.max(0, currentCount - 1);
                    }
                }
                
                // Update low stock count if product status changed
                const lowStockEl = document.querySelector('[data-stat="low_stock_count"]');
                if (lowStockEl) {
                    // Approximate update - would need server data for exact count
                    // But avoids full page reload
                }
            });

        // Listen for category updates
        Echo.channel('categories')
            .listen('.category.updated', (e) => {
                console.log('Category updated:', e.category);
                // Categories don't directly affect dashboard stats
                // No action needed, or show a subtle notification
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-info alert-dismissible fade show';
                alertDiv.innerHTML = `
                    <strong>Category Updated:</strong> ${e.category.name} was ${e.category.action}.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                const mainContent = document.querySelector('.container-fluid');
                if (mainContent) {
                    mainContent.insertBefore(alertDiv, mainContent.firstChild);
                    setTimeout(() => {
                        alertDiv.remove();
                    }, 3000);
                }
            });
    } else {
        console.warn('Laravel Echo is not available. Real-time updates disabled.');
    }
});
</script>
@endif
@endsection

