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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Recent Sales</h5>
                    <a href="{{ route('sales.index') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-list me-1"></i>View All Sales
                    </a>
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
                                <tr data-sale-id="{{ $sale->id }}" data-sale-number="{{ $sale->sale_number }}" class="sale-row-clickable" style="cursor: pointer;" onclick="sessionStorage.setItem('saleFromDashboard', 'true'); console.log('Clicked sale ID: {{ $sale->id }}, Sale Number: {{ $sale->sale_number }}'); window.location.href='{{ url('/sales/' . $sale->id) }}'">
                                    <td><strong>{{ $sale->sale_number }}</strong></td>
                                    <td>{{ $sale->created_at->setTimezone('Asia/Manila')->format('M d, Y') }}</td>
                                    <td>{{ $sale->created_at->setTimezone('Asia/Manila')->format('h:i:s A') }}</td>
                                    <td>{{ $sale->user->name }}</td>
                                    <td>₱{{ number_format($sale->total, 2) }}</td>
                                    <td><span class="badge bg-info" onclick="event.stopPropagation();">{{ ucfirst($sale->payment_method) }}</span></td>
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
                <div class="card-body" style="max-height: 500px; overflow-y: auto;" id="low-stock-container">
                    @forelse($low_stock_products as $product)
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded" data-product-id="{{ $product->id }}">
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
                    <p class="text-muted text-center" id="no-low-stock-message">No low stock items</p>
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
    // Base URL for sales routes
    const salesBaseUrl = '{{ url("/sales") }}';
    
    // Ensure sale row click handlers are set up correctly
    function setupSaleRowHandlers() {
        const rows = document.querySelectorAll('#recent-sales-tbody tr[data-sale-id]');
        console.log('📊 Dashboard: Setting up handlers for', rows.length, 'sale rows');
        rows.forEach(function(row, index) {
            const saleId = row.getAttribute('data-sale-id');
            const saleNumber = row.getAttribute('data-sale-number') || row.querySelector('td:first-child strong')?.textContent || 'Unknown';
            
            if (saleId) {
                const saleUrl = salesBaseUrl + '/' + saleId;
                // Always override onclick to ensure correct URL and set sessionStorage
                row.setAttribute('onclick', `sessionStorage.setItem('saleFromDashboard', 'true'); console.log('Row ${index}: Clicked sale ID: ${saleId}, Sale Number: ${saleNumber}'); window.location.href='${saleUrl}'`);
                row.style.cursor = 'pointer';
                
                console.log(`📊 Dashboard: Row ${index} - Sale ID: ${saleId}, Sale Number: ${saleNumber}, URL: ${saleUrl}`);
                
                // Ensure badge doesn't trigger navigation
                const badge = row.querySelector('.badge');
                if (badge) {
                    badge.setAttribute('onclick', 'event.stopPropagation();');
                }
            } else {
                console.error('📊 Dashboard: Row', index, 'has no sale ID!');
            }
        });
    }
    
    // Set up handlers immediately
    setupSaleRowHandlers();
    
    // Show the low stock alert modal automatically for admin
    @if(auth()->user()->isAdmin() && $low_stock_products->count() > 0)
    const lowStockModal = new bootstrap.Modal(document.getElementById('lowStockAlertModal'));
    lowStockModal.show();
    
    // Re-setup handlers after modal is shown (in case modal initialization interferes)
    setTimeout(function() {
        setupSaleRowHandlers();
    }, 100);
    @endif

    console.log('📊 Dashboard: Page loaded, setting up real-time sync...');
    console.log('📊 Dashboard: Current route:', '{{ \Illuminate\Support\Facades\Route::currentRouteName() }}');
    
    // Verify event listeners are registered
    console.log('📊 Dashboard: Checking event listeners...');
    console.log('📊 Dashboard: window.addEventListener exists?', typeof window.addEventListener === 'function');
    
    // Test if we can manually trigger events
    console.log('📊 Dashboard: Testing event system...');
    const testHandler = function(e) {
        console.log('📊 Dashboard: TEST EVENT RECEIVED!', e);
    };
    window.addEventListener('sync:newSales', testHandler);
    window.addEventListener('sync:productUpdates', testHandler);
    
    // Dispatch a test event immediately
    setTimeout(function() {
        console.log('📊 Dashboard: Dispatching test events...');
        window.dispatchEvent(new CustomEvent('sync:newSales', { 
            detail: [{ 
                id: 999999, 
                sale_number: 'TEST-DASHBOARD', 
                total: 0, 
                created_at: new Date().toISOString(),
                cashier_name: 'Test',
                payment_method: 'cash'
            }] 
        }));
        window.dispatchEvent(new CustomEvent('sync:productUpdates', { 
            detail: [{ 
                id: 999999, 
                name: 'TEST-DASHBOARD', 
                quantity: 0,
                reorder_level: 0,
                unit: 'pcs'
            }] 
        }));
        console.log('📊 Dashboard: Test events dispatched - check if handlers fired above');
    }, 1000);
    
    console.log('📊 Dashboard: Event listeners registered for sync:productUpdates and sync:newSales');
    
    // Helper function to show notification
    function showNotification(message, type = 'success') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
    
    // Helper function to refresh dashboard stats from server
    async function refreshDashboardStats() {
        try {
            console.log('📊 Dashboard: Refreshing stats from server...');
            const response = await fetch('{{ route("dashboard") }}', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                // If we get JSON, parse it; otherwise, the page will reload via global sync
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    // Update stats if provided
                    if (data.stats) {
                        if (data.stats.total_products !== undefined) {
                            const el = document.querySelector('[data-stat="total_products"]');
                            if (el) el.textContent = data.stats.total_products;
                        }
                        if (data.stats.low_stock_products !== undefined) {
                            const el = document.querySelector('[data-stat="low_stock_count"]');
                            if (el) el.textContent = data.stats.low_stock_products;
                        }
                    }
                }
            }
        } catch (error) {
            console.error('📊 Dashboard: Error refreshing stats:', error);
        }
    }
    
    // Helper function to update product in dashboard (used by both polling and WebSocket)
    function updateProductInDashboard(product) {
        // Skip test products
        if (product.id === 999999 || product.name === 'TEST-PRODUCT') {
            console.log('📊 Dashboard: Skipping test product');
            return;
        }
        
        console.log('📊 Dashboard: Updating product:', product.id, product.name, product);
        
        // Update total products count if product was created/deleted
        const totalProductsEl = document.querySelector('[data-stat="total_products"]');
        if (totalProductsEl && product.action) {
            const currentCount = parseInt(totalProductsEl.textContent) || 0;
            if (product.action === 'created') {
                totalProductsEl.textContent = currentCount + 1;
                console.log('📊 Dashboard: Product created, updated count to:', currentCount + 1);
            } else if (product.action === 'deleted') {
                totalProductsEl.textContent = Math.max(0, currentCount - 1);
                console.log('📊 Dashboard: Product deleted, updated count to:', Math.max(0, currentCount - 1));
            }
        }
        
        // Update low stock list
        const lowStockContainer = document.getElementById('low-stock-container');
        if (lowStockContainer && product.quantity !== undefined) {
            const isLowStock = product.quantity <= product.reorder_level;
            const existingItem = lowStockContainer.querySelector(`[data-product-id="${product.id}"]`);
            
            if (isLowStock) {
                // Add or update low stock item
                if (!existingItem) {
                    // Create new low stock item
                    const newItem = document.createElement('div');
                    newItem.className = 'd-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded';
                    newItem.setAttribute('data-product-id', product.id);
                    newItem.innerHTML = `
                        <div>
                            <strong>${product.name}</strong><br>
                            <small class="text-muted">
                                Stock: ${parseInt(product.quantity).toLocaleString()} ${product.unit || 'pcs'} | 
                                Reorder: ${parseInt(product.reorder_level || 0).toLocaleString()} ${product.unit || 'pcs'}
                            </small>
                        </div>
                        <span class="badge bg-${product.quantity == 0 ? 'danger' : 'warning'}">
                            ${product.quantity == 0 ? 'Out' : 'Low'}
                        </span>
                    `;
                    lowStockContainer.insertBefore(newItem, lowStockContainer.firstChild);
                    
                    // Remove "no low stock" message if it exists
                    const noLowStockMsg = document.getElementById('no-low-stock-message');
                    if (noLowStockMsg) noLowStockMsg.remove();
                    
                    // Update low stock count
                    const lowStockEl = document.querySelector('[data-stat="low_stock_count"]');
                    if (lowStockEl) {
                        const currentCount = parseInt(lowStockEl.textContent) || 0;
                        lowStockEl.textContent = currentCount + 1;
                    }
                    
                    // Show notification
                    showNotification(`Low Stock Alert! ${product.name} has ${product.quantity} ${product.unit || 'pcs'} remaining.`, 'warning');
                } else {
                    // Update existing item
                    const stockEl = existingItem.querySelector('small');
                    const badgeEl = existingItem.querySelector('.badge');
                    if (stockEl) {
                        stockEl.innerHTML = `
                            Stock: ${parseInt(product.quantity).toLocaleString()} ${product.unit || 'pcs'} | 
                            Reorder: ${parseInt(product.reorder_level || 0).toLocaleString()} ${product.unit || 'pcs'}
                        `;
                    }
                    if (badgeEl) {
                        badgeEl.className = `badge bg-${product.quantity == 0 ? 'danger' : 'warning'}`;
                        badgeEl.textContent = product.quantity == 0 ? 'Out' : 'Low';
                    }
                }
            } else {
                // Remove from low stock list if no longer low
                if (existingItem) {
                    existingItem.remove();
                    
                    // Update low stock count
                    const lowStockEl = document.querySelector('[data-stat="low_stock_count"]');
                    if (lowStockEl) {
                        const currentCount = parseInt(lowStockEl.textContent) || 0;
                        lowStockEl.textContent = Math.max(0, currentCount - 1);
                    }
                    
                    // Show "no low stock" message if container is empty
                    if (lowStockContainer.children.length === 0) {
                        const noLowStockMsg = document.createElement('p');
                        noLowStockMsg.className = 'text-muted text-center';
                        noLowStockMsg.id = 'no-low-stock-message';
                        noLowStockMsg.textContent = 'No low stock items';
                        lowStockContainer.appendChild(noLowStockMsg);
                    }
                }
            }
        }
    }
    
    // Function to add/update product in dashboard (same pattern as sales history)
    function addProductToDashboard(product) {
        // Skip test products
        if (product.id === 999999 || product.name === 'TEST-PRODUCT') {
            console.log('📊 Dashboard: Skipping test product');
            return;
        }
        
        console.log('📊 Dashboard: Processing product update:', product.id, product.name, product);
        updateProductInDashboard(product);
    }
    
    // Listen for polling sync events (same pattern as sales history)
    // Use named function for easier debugging
    function handleProductUpdates(event) {
        console.log('📦 Dashboard: sync:productUpdates event received!', event);
        console.log('📦 Dashboard: Event type:', event.type);
        console.log('📦 Dashboard: Event detail:', event.detail);
        
        const products = event.detail || [];
        if (!products.length) {
            console.log('📦 Dashboard: No products in event detail');
            return;
        }
        
        console.log('📦 Dashboard: Received product updates via polling:', products.length, products);
        
        products.forEach(function(product) {
            console.log('📦 Dashboard: Processing product in handler:', product.id, product.name);
            addProductToDashboard(product);
        });
    }
    
    window.addEventListener('sync:productUpdates', handleProductUpdates);
    console.log('📦 Dashboard: Registered sync:productUpdates listener');
    
    // Test the event listener after a delay
    setTimeout(function() {
        console.log('📦 Dashboard: Testing event listener...');
        const testEvent = new CustomEvent('sync:productUpdates', { 
            detail: [{ 
                id: 999999, 
                name: 'TEST-PRODUCT', 
                quantity: 100,
                reorder_level: 50,
                unit: 'pcs'
            }] 
        });
        window.dispatchEvent(testEvent);
        console.log('📦 Dashboard: Test event dispatched');
    }, 2000);
    
    // Function to add new sale to dashboard (same pattern as sales history)
    function addSaleToDashboard(sale) {
        // Skip test sales
        if (sale.id === 999999 || sale.sale_number === 'TEST-SALE') {
            console.log('📊 Dashboard: Skipping test sale');
            return;
        }
        
        console.log('📊 Dashboard: Processing sale:', sale.sale_number, sale);
        
        // Check if sale already exists (prevent duplicates)
        const tbody = document.getElementById('recent-sales-tbody');
        if (!tbody) return;
        
        const existingRow = tbody.querySelector(`tr[data-sale-id="${sale.id}"]`);
        if (existingRow) {
            console.log('📊 Dashboard: Sale already exists in table:', sale.sale_number);
            return;
        }
        
        // Remove "no sales" row if it exists
        const noSalesRow = document.getElementById('no-sales-row');
        if (noSalesRow) {
            noSalesRow.remove();
        }
        
        // Update sales count
        const salesCountEl = document.querySelector('[data-stat="total_sales_today"]');
        if (salesCountEl) {
            const currentCount = parseInt(salesCountEl.textContent) || 0;
            salesCountEl.textContent = currentCount + 1;
            console.log('📊 Dashboard: Updated sales count to:', currentCount + 1);
        }
        
        // Update revenue
        const revenueEl = document.querySelector('[data-stat="revenue_today"]');
        if (revenueEl) {
            const currentRevenue = parseFloat(revenueEl.textContent.replace(/[^0-9.]/g, '')) || 0;
            const newRevenue = currentRevenue + parseFloat(sale.total);
            revenueEl.textContent = '₱' + newRevenue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            console.log('📊 Dashboard: Updated revenue to:', newRevenue);
        }
        
        // Add to recent sales table
        const saleDate = new Date(sale.created_at);
        const dateStr = saleDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const timeStr = saleDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        const newRow = document.createElement('tr');
        newRow.setAttribute('data-sale-id', sale.id);
        
        // Add highlight animation
        newRow.style.backgroundColor = '#d4edda';
        setTimeout(() => {
            newRow.style.transition = 'background-color 2s';
            newRow.style.backgroundColor = '';
        }, 100);
        
        newRow.innerHTML = `
            <td><strong>${sale.sale_number}</strong></td>
            <td>${dateStr}</td>
            <td>${timeStr}</td>
            <td>${sale.cashier_name || sale.user_name || 'Unknown'}</td>
            <td>₱${parseFloat(sale.total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td><span class="badge bg-info">${(sale.payment_method || 'cash').charAt(0).toUpperCase() + (sale.payment_method || 'cash').slice(1)}</span></td>
        `;
        
        // Make row clickable to go to sale detail page
        newRow.className = 'sale-row-clickable';
        newRow.style.cursor = 'pointer';
        newRow.setAttribute('data-sale-id', sale.id);
        const saleDetailUrl = salesBaseUrl + '/' + sale.id;
        
        // Use both onclick attribute and event listener for reliability
        newRow.setAttribute('onclick', `sessionStorage.setItem('saleFromDashboard', 'true'); window.location.href='${saleDetailUrl}'`);
        newRow.addEventListener('click', function(e) {
            if (e.target.tagName !== 'SPAN') {
                sessionStorage.setItem('saleFromDashboard', 'true');
                window.location.href = saleDetailUrl;
            }
        });
        
        // Prevent badge clicks from navigating
        const badge = newRow.querySelector('.badge');
        if (badge) {
            badge.setAttribute('onclick', 'event.stopPropagation();');
            badge.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
        
        tbody.insertBefore(newRow, tbody.firstChild);
        while (tbody.children.length > 10) {
            tbody.removeChild(tbody.lastChild);
        }
        
        console.log('📊 Dashboard: Added sale to recent sales table:', sale.sale_number);
    }
    
    // Listen for polling sync events (same pattern as sales history)
    // Use named function for easier debugging
    function handleNewSales(event) {
        console.log('📊 Dashboard: sync:newSales event received!', event);
        console.log('📊 Dashboard: Event type:', event.type);
        console.log('📊 Dashboard: Event detail:', event.detail);
        
        const sales = event.detail || [];
        if (!sales.length) {
            console.log('📊 Dashboard: No sales in event detail');
            return;
        }
        
        console.log('📊 Dashboard: Received new sales via polling:', sales.length, sales);
        
        sales.forEach(function(sale) {
            console.log('📊 Dashboard: Processing sale in handler:', sale.sale_number);
            addSaleToDashboard(sale);
        });
    }
    
    window.addEventListener('sync:newSales', handleNewSales);
    console.log('📊 Dashboard: Registered sync:newSales listener');
    
    // Real-time updates with Laravel Echo
    if (typeof Echo !== 'undefined') {
        console.log('✓ Dashboard: Setting up real-time listeners...');
        // Listen for new sales
        Echo.channel('sales')
            .listen('.sale.created', function(e) {
                console.log('✓ Dashboard: New sale via WebSocket:', e.sale);
                
                // Convert WebSocket event data to match polling format
                const sale = {
                    id: e.sale.sale_id,
                    sale_number: e.sale.sale_number,
                    total: e.sale.total,
                    created_at: e.sale.created_at,
                    cashier_name: e.sale.cashier_name,
                    user_name: e.sale.user_name || e.sale.cashier_name,
                    payment_method: e.sale.payment_method
                };
                
                // Use the same function as polling
                addSaleToDashboard(sale);
            });

        // Listen for inventory updates
        Echo.channel('inventory')
            .listen('.inventory.updated', function(e) {
                console.log('✓ Dashboard: Inventory updated via WebSocket:', e.product);
                
                // Convert WebSocket event data to match polling format
                const product = {
                    id: e.product.product_id || e.product.id,
                    name: e.product.product_name || e.product.name,
                    quantity: e.product.quantity,
                    reorder_level: e.product.reorder_level || 0,
                    unit: e.product.unit || 'pcs'
                };
                
                // Use the same function as polling
                addProductToDashboard(product);
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

