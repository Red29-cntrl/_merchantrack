@extends('layouts.app')

@section('title', 'Categories')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-tags me-2"></i>Categories</h2>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('categories.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add Category
        </a>
        @endif
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Products</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="categories-tbody">
                        @forelse($categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->description ?? 'N/A' }}</td>
                            <td><span class="badge bg-primary">{{ $category->products_count }}</span></td>
                            <td>
                                <a href="{{ route('categories.show', $category) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if(auth()->user()->isAdmin())
                                <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('categories.destroy', $category) }}" method="POST" class="d-inline">
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
                            <td colspan="4" class="text-center">No categories found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $categories->links() }}
        </div>
    </div>
</div>

@if(auth()->user()->isStaff())
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🏷️ Categories: Page loaded, setting up auto-refresh...');
    
    // Function to collect current category IDs
    function collectCategoryIds() {
        const rows = document.querySelectorAll('#categories-tbody tr');
        const ids = [];
        rows.forEach(row => {
            const categoryLink = row.querySelector('a[href*="/categories/"]');
            if (categoryLink) {
                const href = categoryLink.getAttribute('href');
                const match = href.match(/\/categories\/(\d+)/);
                if (match) {
                    ids.push(parseInt(match[1]));
                }
            }
        });
        return ids;
    }
    
    // Function to update known IDs in localStorage
    function updateKnownCategoryIds() {
        const ids = collectCategoryIds();
        const known = JSON.parse(localStorage.getItem('sync_known_ids') || '{"products":[],"categories":[],"movements":[]}');
        known.categories = ids;
        localStorage.setItem('sync_known_ids', JSON.stringify(known));
    }
    
    // Initial collection of IDs
    updateKnownCategoryIds();
    
    // Function to reload categories table
    function reloadCategoriesTable() {
        console.log('🏷️ Categories: Reloading categories table...');
        // Get current page parameter
        const urlParams = new URLSearchParams(window.location.search);
        const page = urlParams.get('page') || '1';
        
        // Build URL with current page
        let reloadUrl = '{{ route("categories.index") }}?page=' + page;
        
        // Fetch the page and extract categories table
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
            const newTbody = doc.querySelector('#categories-tbody');
            const newPagination = doc.querySelector('.pagination');
            
            if (newTbody) {
                const currentTbody = document.getElementById('categories-tbody');
                if (currentTbody && currentTbody.parentNode) {
                    // Replace the tbody content
                    currentTbody.innerHTML = newTbody.innerHTML;
                    console.log('🏷️ Categories: Categories table reloaded');
                    
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
                    updateKnownCategoryIds();
                }
            }
        })
        .catch(err => {
            console.error('🏷️ Categories: Error reloading categories table:', err);
        });
    }
    
    // Function to handle category deletions
    function handleCategoryDeletions(deletedIds) {
        console.log('🗑️ Categories: Handling deletions:', deletedIds);
        const tbody = document.getElementById('categories-tbody');
        if (!tbody) return;
        
        deletedIds.forEach(id => {
            const rows = tbody.querySelectorAll('tr');
            rows.forEach(row => {
                const categoryLink = row.querySelector('a[href*="/categories/' + id + '"]');
                if (categoryLink) {
                    row.remove();
                    console.log('🗑️ Categories: Removed category row:', id);
                }
            });
        });
        
        // Update known IDs after deletion
        updateKnownCategoryIds();
        
        // Reload table if any deletions occurred (to ensure consistency)
        if (deletedIds.length > 0) {
            setTimeout(() => reloadCategoriesTable(), 500);
        }
    }
    
    // Listen for category updates
    window.addEventListener('sync:categoryUpdates', function(event) {
        console.log('🏷️ Categories: sync:categoryUpdates event received!', event);
        const categories = event.detail || [];
        if (!categories.length) {
            return;
        }
        
        console.log('🏷️ Categories: Received category updates via polling:', categories.length);
        reloadCategoriesTable();
    });
    
    // Listen for category deletions
    window.addEventListener('sync:categoriesDeleted', function(event) {
        console.log('🗑️ Categories: sync:categoriesDeleted event received!', event);
        const deletedIds = event.detail || [];
        if (deletedIds.length > 0) {
            handleCategoryDeletions(deletedIds);
        }
    });
});
</script>
@endif
@endsection

