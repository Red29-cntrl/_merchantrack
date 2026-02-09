@extends('layouts.app')

@section('title', 'Sales')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Sales History</h2>
    </div>

    <!-- Filters Card - All in One Line -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('sales.index') }}" class="row g-2 align-items-end">
                <!-- Search -->
                <div class="col-md-2">
                    <label class="form-label small mb-1">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Sale number..." 
                           value="{{ request('search') }}">
                </div>
                
                <!-- Date From -->
                <div class="col-md-2">
                    <label class="form-label small mb-1">From Date</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                
                <!-- Date To -->
                <div class="col-md-2">
                    <label class="form-label small mb-1">To Date</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                
                <!-- Sort By -->
                <div class="col-md-2">
                    <label class="form-label small mb-1">Sort By</label>
                    <select name="sort_by" class="form-select form-select-sm">
                        <option value="date" {{ (isset($sortBy) && $sortBy == 'date' || !isset($sortBy)) ? 'selected' : '' }}>Date</option>
                        <option value="sale_number" {{ (isset($sortBy) && $sortBy == 'sale_number') ? 'selected' : '' }}>Sale Number</option>
                        <option value="cashier" {{ (isset($sortBy) && $sortBy == 'cashier') ? 'selected' : '' }}>Cashier</option>
                        <option value="items" {{ (isset($sortBy) && $sortBy == 'items') ? 'selected' : '' }}>Items</option>
                        <option value="total" {{ (isset($sortBy) && $sortBy == 'total') ? 'selected' : '' }}>Total</option>
                    </select>
                </div>
                
                <!-- Sort Order -->
                <div class="col-md-2">
                    <label class="form-label small mb-1">Order</label>
                    <select name="sort_order" class="form-select form-select-sm">
                        <option value="desc" {{ (isset($sortOrder) && $sortOrder == 'desc') ? 'selected' : '' }}>Descending</option>
                        <option value="asc" {{ (isset($sortOrder) && $sortOrder == 'asc') ? 'selected' : '' }}>Ascending</option>
                    </select>
                </div>
                
                <!-- Filter Button -->
                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                        @php
                            $hasActiveFilters = request('search') || request('date_from') || request('date_to') || 
                                                (request('sort_by') && request('sort_by') != 'sale_number') ||
                                                (request('sort_order') && request('sort_order') != 'desc');
                        @endphp
                        @if($hasActiveFilters)
                        <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary" title="Clear Filters">
                            <i class="fas fa-times"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Sales List</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0">Sale #</th>
                            <th class="border-0">Date</th>
                            <th class="border-0">Cashier</th>
                            <th class="border-0 text-center">Items</th>
                            <th class="border-0 text-end">Total</th>
                            <th class="border-0 text-center">Payment</th>
                            <th class="border-0 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sales-tbody">
                        @forelse($sales as $sale)
                        <tr data-sale-id="{{ $sale->id }}" data-sale-number="{{ $sale->sale_number }}">
                            <td><strong>{{ $sale->sale_number }}</strong></td>
                            <td>{{ $sale->created_at->setTimezone('Asia/Manila')->format('M d, Y h:i:s A') }}</td>
                            <td>{{ $sale->user->name }}</td>
                            <td class="text-center"><span class="badge bg-secondary">{{ $sale->items->count() }}</span></td>
                            <td class="text-end"><strong>₱{{ number_format($sale->total, 2) }}</strong></td>
                            <td class="text-center"><span class="badge bg-info">{{ ucfirst($sale->payment_method) }}</span></td>
                            <td class="text-center">
                                <a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr id="no-sales-row">
                            <td colspan="7" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted mb-0">No sales found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($sales->hasPages())
        <div class="card-footer bg-light">
            {{ $sales->links() }}
        </div>
        @endif
    </div>
</div>

@if(auth()->user()->isAdmin())
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('📊 Sales History: Page loaded, setting up real-time sync...');
    console.log('📊 Sales History: Current route:', '{{ \Illuminate\Support\Facades\Route::currentRouteName() }}');
    
    // Test if event listener is working
    console.log('📊 Sales History: Event listener registered for sync:newSales');
    
    // Function to re-sort the table based on current sort settings
    function resortTable() {
        const tbody = document.getElementById('sales-tbody');
        if (!tbody) return;
        
        const urlParams = new URLSearchParams(window.location.search);
        const sortBy = urlParams.get('sort_by') || 'date';
        const sortOrder = urlParams.get('sort_order') || 'desc';
        
        // Get all rows (excluding no-sales row)
        const rows = Array.from(tbody.querySelectorAll('tr[data-sale-id]'));
        if (rows.length === 0) return;
        
        // Extract numeric part of sale number
        function getSaleNumberNumeric(saleNumber) {
            const parts = saleNumber.split('-');
            return parseInt(parts[parts.length - 1]) || 0;
        }
        
        // Sort rows
        rows.sort((a, b) => {
            let compareA, compareB;
            
            if (sortBy === 'sale_number') {
                const saleNumA = a.getAttribute('data-sale-number') || a.cells[0]?.textContent?.trim() || '';
                const saleNumB = b.getAttribute('data-sale-number') || b.cells[0]?.textContent?.trim() || '';
                compareA = getSaleNumberNumeric(saleNumA);
                compareB = getSaleNumberNumeric(saleNumB);
            } else if (sortBy === 'date' || !sortBy) {
                // Parse dates from table cells
                const dateTextA = a.cells[1]?.textContent?.trim() || '';
                const dateTextB = b.cells[1]?.textContent?.trim() || '';
                compareA = new Date(dateTextA).getTime();
                compareB = new Date(dateTextB).getTime();
            } else {
                // For other sorts, keep current order
                return 0;
            }
            
            if (isNaN(compareA)) compareA = 0;
            if (isNaN(compareB)) compareB = 0;
            
            const diff = compareA - compareB;
            return sortOrder === 'desc' ? -diff : diff;
        });
        
        // Re-append rows in sorted order
        rows.forEach(row => tbody.appendChild(row));
        
        console.log('📊 Sales History: Table re-sorted by', sortBy, sortOrder);
    }
    
    // Re-sort table when sort dropdowns change
    const sortBySelect = document.querySelector('select[name="sort_by"]');
    const sortOrderSelect = document.querySelector('select[name="sort_order"]');
    
    if (sortBySelect) {
        sortBySelect.addEventListener('change', function() {
            // Wait a bit for the form to potentially submit, then re-sort if still on page
            setTimeout(resortTable, 100);
        });
    }
    
    if (sortOrderSelect) {
        sortOrderSelect.addEventListener('change', function() {
            setTimeout(resortTable, 100);
        });
    }
    
    // Initial sort on page load
    resortTable();
    
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
    
    // Helper function to format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        const options = { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        };
        return date.toLocaleDateString('en-US', options);
    }
    
    // Helper function to check if sale matches current filters
    function matchesFilters(sale) {
        const urlParams = new URLSearchParams(window.location.search);
        const search = urlParams.get('search');
        const dateFrom = urlParams.get('date_from');
        const dateTo = urlParams.get('date_to');
        
        // If no filters are active, show all sales
        if (!search && !dateFrom && !dateTo) {
            console.log('📊 Sales History: No filters active, showing all sales');
            return true;
        }
        
        // If filters are active, only show sales that match
        if (search) {
            if (!sale.sale_number || !sale.sale_number.toLowerCase().includes(search.toLowerCase())) {
                console.log('📊 Sales History: Sale filtered out by search:', sale.sale_number);
                return false;
            }
        }
        
        if (dateFrom) {
            const saleDate = new Date(sale.created_at).toISOString().split('T')[0];
            if (saleDate < dateFrom) {
                console.log('📊 Sales History: Sale filtered out by date_from:', sale.sale_number, saleDate, '<', dateFrom);
                return false;
            }
        }
        
        if (dateTo) {
            const saleDate = new Date(sale.created_at).toISOString().split('T')[0];
            if (saleDate > dateTo) {
                console.log('📊 Sales History: Sale filtered out by date_to:', sale.sale_number, saleDate, '>', dateTo);
                return false;
            }
        }
        
        console.log('📊 Sales History: Sale matches all filters:', sale.sale_number);
        return true;
    }
    
    // Function to add new sale to table
    function addSaleToTable(sale) {
        // Skip test sales
        if (sale.id === 999999 || sale.sale_number === 'TEST-SALE') {
            console.log('📊 Sales History: Skipping test sale');
            return;
        }
        
        const tbody = document.getElementById('sales-tbody');
        if (!tbody) return;
        
        // Remove "no sales" row if it exists
        const noSalesRow = document.getElementById('no-sales-row');
        if (noSalesRow) {
            noSalesRow.remove();
        }
        
        // Check if sale already exists (prevent duplicates)
        const existingRow = tbody.querySelector(`tr[data-sale-id="${sale.id}"]`);
        if (existingRow) {
            console.log('📊 Sales History: Sale already exists in table:', sale.sale_number);
            return;
        }
        
        // Create new row
        const newRow = document.createElement('tr');
        newRow.setAttribute('data-sale-id', sale.id);
        newRow.setAttribute('data-sale-number', sale.sale_number);
        
        // Add highlight animation
        newRow.style.backgroundColor = '#d4edda';
        setTimeout(() => {
            newRow.style.transition = 'background-color 2s';
            newRow.style.backgroundColor = '';
        }, 100);
        
        const formattedDate = formatDate(sale.created_at);
        const cashierName = sale.cashier_name || sale.user_name || 'Unknown';
        const paymentMethod = (sale.payment_method || 'cash').charAt(0).toUpperCase() + (sale.payment_method || 'cash').slice(1);
        const totalFormatted = parseFloat(sale.total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        // Note: We don't have items count from sync, so we'll use a placeholder or fetch it
        // For now, we'll add a placeholder badge
        newRow.innerHTML = `
            <td><strong>${sale.sale_number}</strong></td>
            <td>${formattedDate}</td>
            <td>${cashierName}</td>
            <td class="text-center"><span class="badge bg-secondary">-</span></td>
            <td class="text-end"><strong>₱${totalFormatted}</strong></td>
            <td class="text-center"><span class="badge bg-info">${paymentMethod}</span></td>
            <td class="text-center">
                <a href="/sales/${sale.id}" class="btn btn-sm btn-outline-primary" title="View Details">
                    <i class="fas fa-eye"></i>
                </a>
            </td>
        `;
        
        // Insert sale in correct position based on current sort order
        const urlParams = new URLSearchParams(window.location.search);
        const sortBy = urlParams.get('sort_by') || 'date';
        const sortOrder = urlParams.get('sort_order') || 'desc';
        
        // Extract numeric part of sale number for comparison (e.g., "0000-0000-0000-0138" -> 138)
        function getSaleNumberNumeric(saleNumber) {
            const parts = saleNumber.split('-');
            return parseInt(parts[parts.length - 1]) || 0;
        }
        
        const newSaleNumberNumeric = getSaleNumberNumeric(sale.sale_number);
        const newSaleDate = new Date(sale.created_at);
        let inserted = false;
        
        // Find correct insertion position based on sort order
        for (let i = 0; i < tbody.children.length; i++) {
            const row = tbody.children[i];
            if (!row.getAttribute('data-sale-id')) continue; // Skip header/no-sales row
            
            let shouldInsertBefore = false;
            
            if (sortBy === 'sale_number') {
                const rowSaleNumber = row.getAttribute('data-sale-number') || row.cells[0]?.textContent?.trim() || '';
                const rowSaleNumberNumeric = getSaleNumberNumeric(rowSaleNumber);
                
                if (sortOrder === 'desc') {
                    // Descending: higher numbers first
                    shouldInsertBefore = newSaleNumberNumeric > rowSaleNumberNumeric;
                } else {
                    // Ascending: lower numbers first
                    shouldInsertBefore = newSaleNumberNumeric < rowSaleNumberNumeric;
                }
            } else if (sortBy === 'date' || !sortBy) {
                // Default to date sorting
                // Parse date from table cell (format: "M d, Y h:i:s A" or ISO string)
                const rowDateText = row.cells[1]?.textContent?.trim() || '';
                let rowDate = new Date(rowDateText);
                
                // If parsing failed, try to get from data attribute or use sale date
                if (isNaN(rowDate.getTime())) {
                    // Try parsing common date formats
                    rowDate = new Date(rowDateText.replace(/(\w+) (\d+), (\d+) (\d+):(\d+):(\d+) (AM|PM)/, 
                        (match, month, day, year, hour, min, sec, ampm) => {
                            const monthMap = {Jan:0,Feb:1,Mar:2,Apr:3,May:4,Jun:5,Jul:6,Aug:7,Sep:8,Oct:9,Nov:10,Dec:11};
                            let h = parseInt(hour);
                            if (ampm === 'PM' && h !== 12) h += 12;
                            if (ampm === 'AM' && h === 12) h = 0;
                            return new Date(year, monthMap[month], day, h, parseInt(min), parseInt(sec));
                        }));
                }
                
                if (sortOrder === 'desc') {
                    // Descending: newer dates first
                    shouldInsertBefore = newSaleDate > rowDate || isNaN(rowDate.getTime());
                } else {
                    // Ascending: older dates first
                    shouldInsertBefore = newSaleDate < rowDate || isNaN(rowDate.getTime());
                }
            } else {
                // For other sorts (cashier, total, items), default to top for new sales
                shouldInsertBefore = (sortOrder === 'desc' && i === 0) || (sortOrder === 'asc' && i === tbody.children.length - 1);
            }
            
            if (shouldInsertBefore) {
                tbody.insertBefore(newRow, row);
                inserted = true;
                break;
            }
        }
        
        // If not inserted (should be last or first), add at appropriate end
        if (!inserted) {
            if (sortOrder === 'desc') {
                // Add at top for descending
                tbody.insertBefore(newRow, tbody.firstChild);
            } else {
                // Add at bottom for ascending
                tbody.appendChild(newRow);
            }
        }
        
        // Notification removed as per user request
        
        // Re-sort the entire table to ensure correct order
        // This fixes any existing incorrectly positioned sales
        resortTable();
        
        console.log('📊 Sales History: Added new sale to table:', sale.sale_number, 'at position based on sort:', sortBy, sortOrder);
    }
    
    // Listen for polling sync events (fallback when WebSocket not available)
    window.addEventListener('sync:newSales', function(event) {
        console.log('📊 Sales History: sync:newSales event received!', event);
        const sales = event.detail || [];
        if (!sales.length) {
            console.log('📊 Sales History: No sales in event detail');
            return;
        }
        
        console.log('📊 Sales History: Received new sales via polling:', sales.length, sales);
        
        sales.forEach(function(sale) {
            console.log('📊 Sales History: Processing sale:', sale.sale_number, sale);
            // Only add if matches current filters (or no filters active)
            if (matchesFilters(sale)) {
                console.log('📊 Sales History: Sale matches filters, adding to table');
                addSaleToTable(sale);
            } else {
                console.log('📊 Sales History: Sale filtered out:', sale.sale_number);
            }
        });
    });
    
    // Test the event listener after a delay
    setTimeout(function() {
        console.log('📊 Sales History: Testing event listener...');
        const testEvent = new CustomEvent('sync:newSales', { 
            detail: [{ 
                id: 999999, 
                sale_number: 'TEST-SALE', 
                total: 100, 
                created_at: new Date().toISOString(),
                cashier_name: 'Test',
                payment_method: 'cash'
            }] 
        });
        window.dispatchEvent(testEvent);
        console.log('📊 Sales History: Test event dispatched');
    }, 2000);
    
    // Real-time updates with Laravel Echo (WebSocket)
    if (typeof Echo !== 'undefined') {
        console.log('✓ Sales History: Setting up WebSocket listeners...');
        
        Echo.channel('sales')
            .listen('.sale.created', function(e) {
                console.log('✓ Sales History: New sale via WebSocket:', e.sale);
                
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
                
                // Only add if matches current filters (or no filters active)
                if (matchesFilters(sale)) {
                    addSaleToTable(sale);
                } else {
                    console.log('📊 Sales History: Sale filtered out:', sale.sale_number);
                }
            });
    } else {
        console.warn('⚠️ Sales History: Laravel Echo not available. Using polling fallback only.');
    }
});
</script>
@endif
@endsection

