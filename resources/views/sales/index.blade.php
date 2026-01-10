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
                        <option value="sale_number" {{ (isset($sortBy) && $sortBy == 'sale_number') ? 'selected' : '' }}>Sale Number</option>
                        <option value="date" {{ (isset($sortBy) && $sortBy == 'date') ? 'selected' : '' }}>Date</option>
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
                    <tbody>
                        @forelse($sales as $sale)
                        <tr>
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
                        <tr>
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
@endsection

