@extends('layouts.app')

@section('title', 'Products')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-box me-2"></i>Products</h2>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('products.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add Product
        </a>
        @endif
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('products.index') }}" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or SKU" 
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="low_stock" id="low_stock" 
                               value="1" {{ request('low_stock') ? 'checked' : '' }}>
                        <label class="form-check-label" for="low_stock">Low Stock</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        @if(request('search') || request('category') || request('low_stock'))
                        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary" title="Clear Filters">
                            <i class="fas fa-times"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="products-tbody">
                        @forelse($products as $product)
                        <tr>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category->name }}</td>
                            <td>₱{{ number_format($product->price, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $product->isLowStock() ? 'warning' : 'success' }}">
                                    {{ number_format($product->quantity, 0) }} {{ $product->unit }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $product->is_active ? 'success' : 'secondary' }}">
                                    {{ $product->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if(auth()->user()->isAdmin())
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No products found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $products->links() }}
        </div>
    </div>
</div>

@if(auth()->user()->isStaff())
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('📦 Products: Page loaded, setting up auto-refresh...');
    
    // Function to collect current product IDs
    function collectProductIds() {
        const rows = document.querySelectorAll('#products-tbody tr');
        const ids = [];
        rows.forEach(row => {
            const productId = row.querySelector('a[href*="/products/"]');
            if (productId) {
                const href = productId.getAttribute('href');
                const match = href.match(/\/products\/(\d+)/);
                if (match) {
                    ids.push(parseInt(match[1]));
                }
            }
        });
        return ids;
    }
    
    // Function to update known IDs in localStorage
    function updateKnownProductIds() {
        const ids = collectProductIds();
        const known = JSON.parse(localStorage.getItem('sync_known_ids') || '{"products":[],"categories":[],"movements":[]}');
        known.products = ids;
        localStorage.setItem('sync_known_ids', JSON.stringify(known));
    }
    
    // Initial collection of IDs
    updateKnownProductIds();
    
    // Function to reload products table
    function reloadProductsTable() {
        console.log('📦 Products: Reloading products table...');
        // Get current filter parameters
        const urlParams = new URLSearchParams(window.location.search);
        const search = urlParams.get('search') || '';
        const category = urlParams.get('category') || '';
        const lowStock = urlParams.get('low_stock') || '';
        const page = urlParams.get('page') || '1';
        
        // Build URL with current filters
        let reloadUrl = '{{ route("products.index") }}?';
        if (search) reloadUrl += 'search=' + encodeURIComponent(search) + '&';
        if (category) reloadUrl += 'category=' + category + '&';
        if (lowStock) reloadUrl += 'low_stock=' + lowStock + '&';
        reloadUrl += 'page=' + page;
        
        // Fetch the page and extract products table
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
            const newTbody = doc.querySelector('#products-tbody');
            const newPagination = doc.querySelector('.pagination');
            
            if (newTbody) {
                const currentTbody = document.getElementById('products-tbody');
                if (currentTbody && currentTbody.parentNode) {
                    // Replace the tbody content
                    currentTbody.innerHTML = newTbody.innerHTML;
                    console.log('📦 Products: Products table reloaded');
                    
                    // Update pagination if it exists
                    if (newPagination) {
                        const currentPagination = document.querySelector('.pagination');
                        if (currentPagination && currentPagination.parentNode) {
                            currentPagination.outerHTML = newPagination.outerHTML;
                        }
                    }
                    
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
                    updateKnownProductIds();
                }
            }
        })
        .catch(err => {
            console.error('📦 Products: Error reloading products table:', err);
        });
    }
    
    // Function to handle product deletions
    function handleProductDeletions(deletedIds) {
        console.log('🗑️ Products: Handling deletions:', deletedIds);
        const tbody = document.getElementById('products-tbody');
        if (!tbody) return;
        
        deletedIds.forEach(id => {
            const rows = tbody.querySelectorAll('tr');
            rows.forEach(row => {
                const productLink = row.querySelector('a[href*="/products/' + id + '"]');
                if (productLink) {
                    row.remove();
                    console.log('🗑️ Products: Removed product row:', id);
                }
            });
        });
        
        // Update known IDs after deletion
        updateKnownProductIds();
        
        // Reload table if any deletions occurred (to ensure consistency)
        if (deletedIds.length > 0) {
            setTimeout(() => reloadProductsTable(), 500);
        }
    }
    
    // Listen for product updates
    window.addEventListener('sync:productUpdates', function(event) {
        console.log('📦 Products: sync:productUpdates event received!', event);
        const products = event.detail || [];
        if (!products.length) {
            return;
        }
        
        console.log('📦 Products: Received product updates via polling:', products.length);
        reloadProductsTable();
    });
    
    // Listen for product deletions
    window.addEventListener('sync:productsDeleted', function(event) {
        console.log('🗑️ Products: sync:productsDeleted event received!', event);
        const deletedIds = event.detail || [];
        if (deletedIds.length > 0) {
            handleProductDeletions(deletedIds);
        }
    });
    
    // Listen for category updates (categories affect products display)
    window.addEventListener('sync:categoryUpdates', function(event) {
        console.log('📦 Products: sync:categoryUpdates event received!', event);
        const categories = event.detail || [];
        if (!categories.length) {
            return;
        }
        
        console.log('📦 Products: Received category updates via polling:', categories.length);
        reloadProductsTable();
    });
    
    // Listen for category deletions (may affect products)
    window.addEventListener('sync:categoriesDeleted', function(event) {
        console.log('🗑️ Products: sync:categoriesDeleted event received!', event);
        reloadProductsTable();
    });
});
</script>
@endif
@endsection

