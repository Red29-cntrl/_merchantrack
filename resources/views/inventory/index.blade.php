@extends('layouts.app')

@section('title', 'Inventory Movements')

@section('styles')
<style>
    .filter-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-radius: 12px;
        border: 1px solid #e9ecef;
    }
    .table-card {
        border-radius: 12px;
        overflow: hidden;
    }
    .table thead th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 1rem;
    }
    .table tbody td {
        padding: 1rem;
        vertical-align: middle;
    }
    .table tbody tr:hover {
        background-color: #f8f9fa;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="fas fa-warehouse me-2"></i>Inventory</h2>
        </div>
        @if(auth()->user()->isAdmin())
        <div class="d-flex gap-2">
            <a href="{{ route('inventory.monthly-summary') }}" class="btn btn-primary shadow-sm">
                <i class="fas fa-chart-bar me-2"></i>View Monthly Summary
            </a>
            <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#adjustModal">
                <i class="fas fa-plus me-2"></i>Adjust Stock
            </button>
        </div>
        @endif
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(auth()->user()->isStaff())
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Stock Levels</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Stock</th>
                            <th>Unit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="staff-products-tbody">
                        @forelse($products as $product)
                        <tr data-product-id="{{ $product->id }}">
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->category->name }}</td>
                            <td>
                                <span class="badge bg-{{ $product->isLowStock() ? 'warning' : 'success' }}" data-product-quantity="{{ $product->quantity }}">
                                    {{ number_format($product->quantity, 0) }}
                                </span>
                            </td>
                            <td>
                                <span class="text-muted">{{ $product->unit ? ucfirst($product->unit) : 'Pcs' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $product->isLowStock() ? 'warning' : 'success' }}" data-product-status>
                                    {{ $product->isLowStock() ? 'Low Stock' : 'In Stock' }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No products found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="fas fa-history me-2"></i>Inventory Movements</h3>
    </div>

    <div class="card mb-4 filter-card shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex align-items-center mb-3">
                <h6 class="mb-0 fw-bold">Filter Options</h6>
            </div>
            <form id="inventory-filter-form" method="GET" action="{{ route('inventory.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Product</label>
                    <select name="product_id" class="form-select shadow-sm">
                        <option value="">All Products</option>
                        @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id || request('product_id') == (string)$product->id ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Type</label>
                    <select name="type" class="form-select shadow-sm">
                        <option value="">All</option>
                        <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>In</option>
                        <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Out</option>
                        <option value="adjustment" {{ request('type') == 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                        @if(auth()->user()->isAdmin())
                        <option value="balance" {{ request('type') == 'balance' ? 'selected' : '' }}>Balance</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Month</label>
                    <select name="month" class="form-select shadow-sm">
                        <option value="all" {{ (string)$selectedMonth === 'all' ? 'selected' : '' }}>All Months (full year)</option>
                        <option value="1" {{ (string)$selectedMonth === '1' ? 'selected' : '' }}>January</option>
                        <option value="2" {{ (string)$selectedMonth === '2' ? 'selected' : '' }}>February</option>
                        <option value="3" {{ (string)$selectedMonth === '3' ? 'selected' : '' }}>March</option>
                        <option value="4" {{ (string)$selectedMonth === '4' ? 'selected' : '' }}>April</option>
                        <option value="5" {{ (string)$selectedMonth === '5' ? 'selected' : '' }}>May</option>
                        <option value="6" {{ (string)$selectedMonth === '6' ? 'selected' : '' }}>June</option>
                        <option value="7" {{ (string)$selectedMonth === '7' ? 'selected' : '' }}>July</option>
                        <option value="8" {{ (string)$selectedMonth === '8' ? 'selected' : '' }}>August</option>
                        <option value="9" {{ (string)$selectedMonth === '9' ? 'selected' : '' }}>September</option>
                        <option value="10" {{ (string)$selectedMonth === '10' ? 'selected' : '' }}>October</option>
                        <option value="11" {{ (string)$selectedMonth === '11' ? 'selected' : '' }}>November</option>
                        <option value="12" {{ (string)$selectedMonth === '12' ? 'selected' : '' }}>December</option>
                    </select>
                </div>
                <div class="col-md-2">
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
                    @php
                        $hasActiveFilters = request('product_id') || 
                                            request('type') || 
                                            (request()->has('month') && request('month') !== 'all' && request('month') != date('n')) ||
                                            (request()->has('year') && request('year') != date('Y'));
                    @endphp
                    @if($hasActiveFilters)
                    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary shadow-sm" title="Clear Filters">
                        <i class="fas fa-times"></i>
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>


    <div class="card table-card shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h6 class="mb-0 fw-bold"><i class="fas fa-table me-2"></i>Inventory Movements Table</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                @if(isset($showBalance) && $showBalance)
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Balance</th>
                            <th>Unit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="balance-products-tbody">
                        @forelse($balanceProducts as $product)
                        <tr data-product-id="{{ $product->id }}">
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->category->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-info" data-product-current-stock="{{ $product->current_stock ?? $product->quantity }}">
                                    {{ number_format($product->current_stock ?? $product->quantity, 0) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $product->isLowStock() ? 'warning' : 'success' }}" data-product-balance="{{ $product->balance ?? $product->quantity }}">
                                    {{ number_format($product->balance ?? $product->quantity, 0) }}
                                </span>
                            </td>
                            <td>
                                <span class="text-muted">{{ $product->unit ? ucfirst($product->unit) : 'Pcs' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $product->isLowStock() ? 'warning' : 'success' }}" data-product-status>
                                    {{ $product->isLowStock() ? 'Low Stock' : 'In Stock' }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No products found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $balanceProducts->links() }}
                @else
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><strong>Date</strong></th>
                            <th><strong>Product</strong></th>
                            <th><strong>Current Stock</strong></th>
                            <th><strong>In</strong></th>
                            <th><strong>Out</strong></th>
                            <th><strong>Balance</strong></th>
                            <th><strong>Reason</strong></th>
                            <th><strong>Staff</strong></th>
                        </tr>
                    </thead>
                    <tbody id="movements-tbody">
                        @forelse($movements as $movement)
                        @if($movement->product)
                        <tr data-movement-id="{{ $movement->id }}" data-product-id="{{ $movement->product->id }}">
                            <td>{{ $movement->created_at->setTimezone('Asia/Manila')->format('M d, Y h:i:s A') }}</td>
                            <td>{{ $movement->product->name }}</td>
                            <td>{{ number_format($movement->opening_balance ?? 0, 0) }}</td>
                            <td>
                                @if($movement->type === 'in' || $movement->type === 'adjustment')
                                    {{ number_format($movement->quantity, 0) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($movement->type === 'out')
                                    {{ number_format($movement->quantity, 0) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ number_format($movement->running_balance ?? 0, 0) }}</td>
                            <td>{{ $movement->reason ?? 'N/A' }}</td>
                            <td>{{ $movement->user->name ?? 'N/A' }}</td>
                        </tr>
                        @endif
                        @empty
                        <tr id="no-movements-row">
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted mb-0">No inventory movements found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->isAdmin())
<!-- Adjust Stock Modal -->
<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-bottom" style="background-color: #852E4E; color: white; border-radius: 12px 12px 0 0;">
                <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Adjust Stock</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="adjustForm" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="product_id" class="form-label fw-semibold">Product</label>
                        <select class="form-select shadow-sm" id="product_id" name="product_id" required>
                            <option value="">Select Product</option>
                            @foreach($products as $product)
                            <option value="{{ $product->id }}" data-stock="{{ $product->quantity }}">
                                {{ $product->name }} (Current: {{ number_format($product->quantity, 0) }} {{ $product->unit }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label fw-semibold">Type</label>
                        <select class="form-select shadow-sm" id="type" name="type" required>
                            <option value="in">Stock In</option>
                            <option value="out">Stock Out</option>
                            <option value="adjustment">Adjustment</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label fw-semibold">Quantity</label>
                        <input type="number" class="form-control shadow-sm" id="quantity" name="quantity" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label fw-semibold">Reason</label>
                        <textarea class="form-control shadow-sm" id="reason" name="reason" rows="2" placeholder="Enter reason for adjustment..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light" style="border-radius: 0 0 12px 12px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary shadow-sm">
                        <i class="fas fa-check me-1"></i>Adjust Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('adjustForm').addEventListener('submit', function(e) {
    const productId = document.getElementById('product_id').value;
    if (!productId) {
        e.preventDefault();
        alert('Please select a product');
        return;
    }
    const selectedOption = document.querySelector('#product_id option:checked');
    const currentStock = selectedOption ? parseInt(selectedOption.getAttribute('data-stock'), 10) : 0;
    const type = document.getElementById('type').value;
    const qty = parseInt(document.getElementById('quantity').value, 10);
    if (type === 'out' && qty > currentStock) {
        e.preventDefault();
        alert('Cannot stock out more than available quantity.');
        return;
    }
    const form = this;
    form.action = '/inventory/adjust/' + productId;
});
</script>

@if(auth()->user()->isStaff())
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('📦 Inventory (Staff): Page loaded, setting up auto-refresh...');
    
    // Function to collect current product IDs from staff products table
    function collectStaffProductIds() {
        const rows = document.querySelectorAll('#staff-products-tbody tr[data-product-id]');
        const ids = [];
        rows.forEach(row => {
            const productId = row.getAttribute('data-product-id');
            if (productId) {
                ids.push(parseInt(productId));
            }
        });
        return ids;
    }
    
    // Function to collect current movement IDs
    function collectMovementIds() {
        const rows = document.querySelectorAll('#movements-tbody tr[data-movement-id]');
        const ids = [];
        rows.forEach(row => {
            const movementId = row.getAttribute('data-movement-id');
            if (movementId) {
                ids.push(parseInt(movementId));
            }
        });
        return ids;
    }
    
    // Function to update known IDs in localStorage
    function updateKnownIds() {
        const productIds = collectStaffProductIds();
        const movementIds = collectMovementIds();
        const known = JSON.parse(localStorage.getItem('sync_known_ids') || '{"products":[],"categories":[],"movements":[]}');
        known.products = productIds;
        known.movements = movementIds;
        localStorage.setItem('sync_known_ids', JSON.stringify(known));
    }
    
    // Initial collection of IDs
    updateKnownIds();
    
    // Function to reload products table (staff view)
    function reloadStaffProductsTable() {
        console.log('📦 Inventory (Staff): Reloading products table...');
        const urlParams = new URLSearchParams(window.location.search);
        const page = urlParams.get('page') || '1';
        
        let reloadUrl = '{{ route("inventory.index") }}?page=' + page;
        
        fetch(reloadUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTbody = doc.querySelector('#staff-products-tbody');
            
            if (newTbody) {
                const currentTbody = document.getElementById('staff-products-tbody');
                if (currentTbody && currentTbody.parentNode) {
                    currentTbody.innerHTML = newTbody.innerHTML;
                    console.log('📦 Inventory (Staff): Products table reloaded');
                    
                    // Highlight first row briefly
                    const rows = currentTbody.querySelectorAll('tr');
                    if (rows.length > 0) {
                        rows[0].style.backgroundColor = '#d4edda';
                        setTimeout(() => {
                            rows[0].style.transition = 'background-color 2s';
                            rows[0].style.backgroundColor = '';
                        }, 2000);
                    }
                    
                    // Update known IDs after reload
                    updateKnownIds();
                }
            }
        })
        .catch(err => {
            console.error('📦 Inventory (Staff): Error reloading products table:', err);
        });
    }
    
    // Function to handle product deletions
    function handleProductDeletions(deletedIds) {
        console.log('🗑️ Inventory (Staff): Handling product deletions:', deletedIds);
        const tbody = document.getElementById('staff-products-tbody');
        if (!tbody) return;
        
        deletedIds.forEach(id => {
            const row = tbody.querySelector(`tr[data-product-id="${id}"]`);
            if (row) {
                row.remove();
                console.log('🗑️ Inventory (Staff): Removed product row:', id);
            }
        });
        
        // Update known IDs after deletion
        updateKnownIds();
        
        // Reload table if any deletions occurred
        if (deletedIds.length > 0) {
            setTimeout(() => reloadStaffProductsTable(), 500);
        }
    }
    
    // Function to handle movement deletions
    function handleMovementDeletions(deletedIds) {
        console.log('🗑️ Inventory (Staff): Handling movement deletions:', deletedIds);
        const tbody = document.getElementById('movements-tbody');
        if (!tbody) return;
        
        deletedIds.forEach(id => {
            const row = tbody.querySelector(`tr[data-movement-id="${id}"]`);
            if (row) {
                row.remove();
                console.log('🗑️ Inventory (Staff): Removed movement row:', id);
            }
        });
        
        // Update known IDs after deletion
        updateKnownIds();
        
        // Reload table if any deletions occurred
        if (deletedIds.length > 0) {
            setTimeout(() => reloadMovementsTable(), 500);
        }
    }
    
    // Function to reload movements table
    function reloadMovementsTable() {
        console.log('📋 Inventory (Staff): Reloading movements table...');
        const urlParams = new URLSearchParams(window.location.search);
        const productId = urlParams.get('product_id') || '';
        const type = urlParams.get('type') || '';
        const month = urlParams.get('month') || '';
        const year = urlParams.get('year') || new Date().getFullYear();
        
        let reloadUrl = '{{ route("inventory.index") }}?';
        if (productId) reloadUrl += 'product_id=' + productId + '&';
        if (type) reloadUrl += 'type=' + type + '&';
        if (month) reloadUrl += 'month=' + month + '&';
        reloadUrl += 'year=' + year;
        
        fetch(reloadUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTbody = doc.querySelector('#movements-tbody');
            
            if (newTbody) {
                const currentTbody = document.getElementById('movements-tbody');
                if (currentTbody && currentTbody.parentNode) {
                    currentTbody.innerHTML = newTbody.innerHTML;
                    console.log('📋 Inventory (Staff): Movements table reloaded');
                    
                    // Highlight new rows
                    const rows = currentTbody.querySelectorAll('tr[data-movement-id]');
                    if (rows.length > 0) {
                        rows[0].style.backgroundColor = '#d4edda';
                        setTimeout(() => {
                            rows[0].style.transition = 'background-color 2s';
                            rows[0].style.backgroundColor = '';
                        }, 2000);
                    }
                    
                    // Update known IDs after reload
                    updateKnownIds();
                }
            }
        })
        .catch(err => {
            console.error('📋 Inventory (Staff): Error reloading movements table:', err);
        });
    }
    
    // Listen for product updates
    window.addEventListener('sync:productUpdates', function(event) {
        console.log('📦 Inventory (Staff): sync:productUpdates event received!', event);
        const products = event.detail || [];
        if (products.length > 0) {
            console.log('📦 Inventory (Staff): Received product updates:', products.length);
            reloadStaffProductsTable();
        }
    });
    
    // Listen for product deletions
    window.addEventListener('sync:productsDeleted', function(event) {
        console.log('🗑️ Inventory (Staff): sync:productsDeleted event received!', event);
        const deletedIds = event.detail || [];
        if (deletedIds.length > 0) {
            handleProductDeletions(deletedIds);
        }
    });
    
    // Listen for category updates
    window.addEventListener('sync:categoryUpdates', function(event) {
        console.log('📦 Inventory (Staff): sync:categoryUpdates event received!', event);
        const categories = event.detail || [];
        if (categories.length > 0) {
            console.log('📦 Inventory (Staff): Received category updates:', categories.length);
            reloadStaffProductsTable();
        }
    });
    
    // Listen for inventory movements
    window.addEventListener('sync:inventoryMovements', function(event) {
        console.log('📋 Inventory (Staff): sync:inventoryMovements event received!', event);
        const movements = event.detail || [];
        if (movements.length > 0) {
            console.log('📋 Inventory (Staff): Received inventory movements:', movements.length);
            reloadMovementsTable();
            reloadStaffProductsTable(); // Also refresh products table
        }
    });
    
    // Listen for movement deletions
    window.addEventListener('sync:inventoryMovementsDeleted', function(event) {
        console.log('🗑️ Inventory (Staff): sync:inventoryMovementsDeleted event received!', event);
        const deletedIds = event.detail || [];
        if (deletedIds.length > 0) {
            handleMovementDeletions(deletedIds);
        }
    });
});
</script>
@endif

@if(auth()->user()->isAdmin())
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('📦 Inventory: Page loaded, setting up real-time sync...');
    console.log('📦 Inventory: Current route:', '{{ \Illuminate\Support\Facades\Route::currentRouteName() }}');
    
    // Test if event listener is working
    console.log('📦 Inventory: Event listener registered for sync:productUpdates');
    
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
    
    // Helper function to update product quantity in tables
    function updateProductQuantity(productId, quantity, reorderLevel, unit, productName) {
        // Skip test products
        if (productId === 999999 || productName === 'TEST-PRODUCT') {
            console.log('📦 Inventory: Skipping test product');
            return;
        }
        
        const isLowStock = quantity <= reorderLevel;
        
        // Update staff products table
        const staffRow = document.querySelector(`#staff-products-tbody tr[data-product-id="${productId}"]`);
        if (staffRow) {
            const quantityBadge = staffRow.querySelector('[data-product-quantity]');
            const statusBadge = staffRow.querySelector('[data-product-status]');
            
            if (quantityBadge) {
                quantityBadge.setAttribute('data-product-quantity', quantity);
                quantityBadge.textContent = parseInt(quantity).toLocaleString();
                quantityBadge.className = `badge bg-${isLowStock ? 'warning' : 'success'}`;
            }
            
            if (statusBadge) {
                statusBadge.textContent = isLowStock ? 'Low Stock' : 'In Stock';
                statusBadge.className = `badge bg-${isLowStock ? 'warning' : 'success'}`;
            }
        }
        
        // Update balance products table
        const balanceRow = document.querySelector(`#balance-products-tbody tr[data-product-id="${productId}"]`);
        if (balanceRow) {
            const currentStockBadge = balanceRow.querySelector('[data-product-current-stock]');
            const balanceBadge = balanceRow.querySelector('[data-product-balance]');
            const statusBadge = balanceRow.querySelector('[data-product-status]');
            
            if (currentStockBadge) {
                currentStockBadge.setAttribute('data-product-current-stock', quantity);
                currentStockBadge.textContent = parseInt(quantity).toLocaleString();
            }
            
            if (balanceBadge) {
                balanceBadge.setAttribute('data-product-balance', quantity);
                balanceBadge.textContent = parseInt(quantity).toLocaleString();
                balanceBadge.className = `badge bg-${isLowStock ? 'warning' : 'success'}`;
            }
            
            if (statusBadge) {
                statusBadge.textContent = isLowStock ? 'Low Stock' : 'In Stock';
                statusBadge.className = `badge bg-${isLowStock ? 'warning' : 'success'}`;
            }
        }
        
        // Show notification for low stock
        if (isLowStock) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-warning alert-dismissible fade show';
            alertDiv.innerHTML = `
                <strong>Low Stock Alert!</strong> Product has ${quantity} ${unit || 'pcs'} remaining.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            const mainContent = document.querySelector('.container-fluid');
            if (mainContent) {
                mainContent.insertBefore(alertDiv, mainContent.firstChild);
            }
        }
    }
    
    // Function to add/update product in inventory (same pattern as sales history)
    function addProductToInventory(product) {
        // Skip test products
        if (product.id === 999999 || product.name === 'TEST-PRODUCT') {
            console.log('📦 Inventory: Skipping test product');
            return;
        }
        
        console.log('📦 Inventory: Processing product update:', product.id, product.name, product);
        
        if (product.quantity !== undefined) {
            updateProductQuantity(
                product.id,
                product.quantity,
                product.reorder_level || 0,
                product.unit || 'pcs',
                product.name
            );
        }
    }
    
    // Function to add new inventory movement to table
    function addMovementToTable(movement) {
        // Skip test movements
        if (movement.id === 999999) {
            console.log('📋 Inventory: Skipping test movement');
            return;
        }
        
        console.log('📋 Inventory: Processing movement:', movement.id, movement.product_name);
        
        const tbody = document.getElementById('movements-tbody');
        if (!tbody) {
            console.log('📋 Inventory: Movements table body not found');
            return;
        }
        
        // Check if movement already exists (prevent duplicates)
        const existingRow = tbody.querySelector(`tr[data-movement-id="${movement.id}"]`);
        if (existingRow) {
            console.log('📋 Inventory: Movement already exists in table:', movement.id);
            return;
        }
        
        // Remove "no movements" row if it exists
        const noMovementsRow = document.getElementById('no-movements-row');
        if (noMovementsRow) {
            noMovementsRow.remove();
        }
        
        // Format date
        const movementDate = new Date(movement.created_at);
        const dateStr = movementDate.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
        
        // Create new row
        const newRow = document.createElement('tr');
        newRow.setAttribute('data-movement-id', movement.id);
        newRow.setAttribute('data-product-id', movement.product_id);
        
        // Add highlight animation
        newRow.style.backgroundColor = '#d4edda';
        setTimeout(() => {
            newRow.style.transition = 'background-color 2s';
            newRow.style.backgroundColor = '';
        }, 100);
        
        // Determine in/out values
        const inValue = (movement.type === 'in' || movement.type === 'adjustment') 
            ? parseInt(movement.quantity).toLocaleString() 
            : '<span class="text-muted">—</span>';
        const outValue = movement.type === 'out' 
            ? parseInt(movement.quantity).toLocaleString() 
            : '<span class="text-muted">—</span>';
        
        // Note: We don't have opening_balance and running_balance from sync
        // For now, we'll use placeholders or fetch them
        // The full balance calculation would require server-side computation
        newRow.innerHTML = `
            <td>${dateStr}</td>
            <td>${movement.product_name || 'Unknown'}</td>
            <td>—</td>
            <td>${inValue}</td>
            <td>${outValue}</td>
            <td>—</td>
            <td>${movement.reason || 'N/A'}</td>
            <td>${movement.user_name || 'Unknown'}</td>
        `;
        
        // Insert at the top (newest first)
        tbody.insertBefore(newRow, tbody.firstChild);
        
        console.log('📋 Inventory: Added movement to table:', movement.id);
    }
    
    // Listen for polling sync events (same pattern as sales history)
    window.addEventListener('sync:productUpdates', function(event) {
        console.log('📦 Inventory: sync:productUpdates event received!', event);
        const products = event.detail || [];
        if (!products.length) {
            console.log('📦 Inventory: No products in event detail');
            return;
        }
        
        console.log('📦 Inventory: Received product updates via polling:', products.length, products);
        
        products.forEach(function(product) {
            addProductToInventory(product);
        });
    });
    
    // Function to reload movements table (for accurate balance calculations)
    function reloadMovementsTable() {
        console.log('📋 Inventory: Reloading movements table...');
        // Get current filter parameters
        const urlParams = new URLSearchParams(window.location.search);
        const productId = urlParams.get('product_id') || '';
        const type = urlParams.get('type') || '';
        const month = urlParams.get('month') || '';
        const year = urlParams.get('year') || new Date().getFullYear();
        
        // Build URL with current filters
        let reloadUrl = '{{ route("inventory.index") }}?';
        if (productId) reloadUrl += 'product_id=' + productId + '&';
        if (type) reloadUrl += 'type=' + type + '&';
        if (month) reloadUrl += 'month=' + month + '&';
        reloadUrl += 'year=' + year;
        
        // Fetch the page and extract movements table
        fetch(reloadUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Parse the HTML response
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTbody = doc.querySelector('#movements-tbody');
            
            if (newTbody) {
                const currentTbody = document.getElementById('movements-tbody');
                if (currentTbody && currentTbody.parentNode) {
                    // Replace the tbody content
                    currentTbody.innerHTML = newTbody.innerHTML;
                    console.log('📋 Inventory: Movements table reloaded');
                    
                    // Highlight new rows
                    const rows = currentTbody.querySelectorAll('tr[data-movement-id]');
                    if (rows.length > 0) {
                        rows[0].style.backgroundColor = '#d4edda';
                        setTimeout(() => {
                            rows[0].style.transition = 'background-color 2s';
                            rows[0].style.backgroundColor = '';
                        }, 2000);
                    }
                }
            }
        })
        .catch(err => {
            console.error('📋 Inventory: Error reloading movements table:', err);
            // Fallback: just add movements without balances
            console.log('📋 Inventory: Falling back to simple movement addition');
        });
    }
    
    // Listen for inventory movements sync events
    window.addEventListener('sync:inventoryMovements', function(event) {
        console.log('📋 Inventory: sync:inventoryMovements event received!', event);
        const movements = event.detail || [];
        if (!movements.length) {
            console.log('📋 Inventory: No movements in event detail');
            return;
        }
        
        console.log('📋 Inventory: Received inventory movements via polling:', movements.length, movements);
        
        // Reload the table to get accurate balances
        // This ensures opening_balance and running_balance are correct
        reloadMovementsTable();
    });
    
    // Real-time updates with Laravel Echo (WebSocket)
    if (typeof Echo !== 'undefined') {
        console.log('✓ Inventory: Setting up WebSocket listeners...');
        
        // Listen for product updates
        Echo.channel('products')
            .listen('.product.updated', function(e) {
                console.log('✓ Inventory: Product updated via WebSocket:', e.product);
                
                // Convert WebSocket event data to match polling format
                const product = {
                    id: e.product.id || e.product.product_id,
                    name: e.product.name || e.product.product_name,
                    quantity: e.product.quantity,
                    reorder_level: e.product.reorder_level || 0,
                    unit: e.product.unit || 'pcs'
                };
                
                // Use the same function as polling
                addProductToInventory(product);
            });
        
        // Listen for inventory updates
        Echo.channel('inventory')
            .listen('.inventory.updated', function(e) {
                console.log('✓ Inventory: Inventory updated via WebSocket:', e.product);
                
                // Convert WebSocket event data to match polling format
                const product = {
                    id: e.product.product_id || e.product.id,
                    name: e.product.product_name || e.product.name,
                    quantity: e.product.quantity,
                    reorder_level: e.product.reorder_level || 0,
                    unit: e.product.unit || 'pcs'
                };
                
                // Use the same function as polling
                addProductToInventory(product);
            });
    } else {
        console.warn('⚠️ Inventory: Laravel Echo not available. Using polling fallback only.');
    }
});
</script>
@endif
@endif
@endsection
 
