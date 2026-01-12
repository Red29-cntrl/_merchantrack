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
                    <tbody>
                        @forelse($products as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->category->name }}</td>
                            <td>
                                <span class="badge bg-{{ $product->isLowStock() ? 'warning' : 'success' }}">
                                    {{ number_format($product->quantity, 0) }}
                                </span>
                            </td>
                            <td>
                                <span class="text-muted">{{ $product->unit ? ucfirst($product->unit) : 'Pcs' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $product->isLowStock() ? 'warning' : 'success' }}">
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
                            <th>Stock</th>
                            <th>Unit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($balanceProducts as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->category->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-{{ $product->isLowStock() ? 'warning' : 'success' }}">
                                    {{ number_format($product->quantity, 0) }}
                                </span>
                            </td>
                            <td>
                                <span class="text-muted">{{ $product->unit ? ucfirst($product->unit) : 'Pcs' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $product->isLowStock() ? 'warning' : 'success' }}">
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
                {{ $balanceProducts->links() }}
                @else
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Stock</th>
                            <th>In</th>
                            <th>Out</th>
                            <th>Balance</th>
                            <th>Reason</th>
                            <th>Staff</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $movement)
                        @if($movement->product)
                        <tr>
                            <td>{{ $movement->created_at->setTimezone('Asia/Manila')->format('M d, Y h:i:s A') }}</td>
                            <td>{{ $movement->product->name }}</td>
                            <td>{{ number_format($movement->opening_balance ?? 0, 0) }}</td>
                            <td>
                                @if($movement->type === 'out')
                                    —
                                @else
                                    {{ number_format($movement->quantity, 0) }}
                                @endif
                            </td>
                            <td>
                                @if($movement->type === 'out')
                                    {{ number_format($movement->quantity, 0) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ number_format($movement->running_balance ?? 0, 0) }}</td>
                            <td>{{ $movement->reason ?? 'N/A' }}</td>
                            <td>{{ $movement->user->name ?? 'N/A' }}</td>
                        </tr>
                        @endif
                        @empty
                        <tr>
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
@endif
@endsection
 
