@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-truck me-2"></i>Suppliers</h2>
        <a href="{{ route('suppliers.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add Supplier
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('suppliers.index') }}" class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, VAT TIN, or email" 
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>VAT REG. TIN NUMBER</th>
                            <th>Address</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $supplier)
                        <tr>
                            <td>{{ $supplier->name }}</td>
                            <td>{{ $supplier->vat_reg_tin_number ?? 'N/A' }}</td>
                            <td>{{ $supplier->address ?? 'N/A' }}</td>
                            <td>{{ $supplier->phone ?? 'N/A' }}</td>
                            <td>{{ $supplier->email ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-{{ $supplier->is_active ? 'success' : 'secondary' }}">
                                    {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('suppliers.toggle-status', $supplier) }}" method="POST" class="d-inline" style="margin: 0;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-{{ $supplier->is_active ? 'secondary' : 'success' }}" 
                                                title="{{ $supplier->is_active ? 'Deactivate' : 'Activate' }}">
                                            <i class="fas fa-{{ $supplier->is_active ? 'ban' : 'check' }}"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" class="d-inline delete-form" style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No suppliers found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $suppliers->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Handle delete form submission with confirmation
        $('.delete-form').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const supplierName = form.closest('tr').find('td:first').text().trim();
            
            if (confirm('Are you sure you want to delete the supplier "' + supplierName + '"?\n\nThis action cannot be undone.')) {
                form.off('submit').submit();
            }
        });
    });
</script>
@endsection

