@extends('layouts.app')

@section('title', 'Inventory Movements')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-warehouse me-2"></i>Inventory</h2>
        @if(auth()->user()->isAdmin())
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adjustModal">
            <i class="fas fa-plus me-2"></i>Adjust Stock
        </button>
        @endif
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
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
                                    {{ $product->quantity }}
                                </span>
                            </td>
                            <td>{{ $product->unit }}</td>
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

    <h3 class="mb-3"><i class="fas fa-history me-2"></i>Inventory Movements</h3>

    <div class="card mb-3">
        <div class="card-body">
            <form id="inventory-filter-form" method="GET" action="{{ route('inventory.index') }}" class="row g-3">
                <div class="col-md-4">
                    <select name="product_id" class="form-select" onchange="document.getElementById('inventory-filter-form').submit();">
                        <option value="">All Products</option>
                        @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select" onchange="document.getElementById('inventory-filter-form').submit();">
                        <option value="">All</option>
                        <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>In</option>
                        <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Out</option>
                        <option value="adjustment" {{ request('type') == 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                        @if(auth()->user()->isAdmin())
                        <option value="balance" {{ request('type') == 'balance' ? 'selected' : '' }}>Balance</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
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
                                    {{ $product->quantity }}
                                </span>
                            </td>
                            <td>{{ $product->unit }}</td>
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
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $movement)
                        @if($movement->product)
                        <tr>
                            <td>{{ $movement->created_at->format('M d, Y H:i') }}</td>
                            <td>{{ $movement->product->name }}</td>
                            <td>{{ $movement->opening_balance ?? 0 }}</td>
                            <td>
                                @if($movement->type === 'out')
                                    —
                                @else
                                    {{ $movement->quantity }}
                                @endif
                            </td>
                            <td>
                                @if($movement->type === 'out')
                                    {{ $movement->quantity }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $movement->running_balance ?? 0 }}</td>
                            <td>{{ $movement->reason ?? 'N/A' }}</td>
                            <td>{{ $movement->user->name ?? 'N/A' }}</td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No inventory movements found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>

    @if(!isset($showBalance) || !$showBalance)
    <div class="alert alert-info mt-2">
        Ledger shows running balance per product based on recorded movements. If a product started with stock, record an initial “Stock In” to reflect it.
    </div>
    @endif
</div>

@if(auth()->user()->isAdmin())
<!-- Adjust Stock Modal -->
<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="adjustForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Product</label>
                        <select class="form-select" id="product_id" name="product_id" required>
                            <option value="">Select Product</option>
                            @foreach($products as $product)
                            <option value="{{ $product->id }}" data-stock="{{ $product->quantity }}">
                                {{ $product->name }} (Current: {{ $product->quantity }} {{ $product->unit }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="in">Stock In</option>
                            <option value="out">Stock Out</option>
                            <option value="adjustment">Adjustment</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason</label>
                        <textarea class="form-control" id="reason" name="reason" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Adjust Stock</button>
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
 
