@extends('layouts.app')

@section('title', 'Supplier Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-truck me-2"></i>Supplier Details</h2>
        <div>
            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-warning">
                <i class="fas fa-edit me-2"></i>Edit
            </a>
            <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Information</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th width="200">Name:</th>
                            <td>{{ $supplier->name }}</td>
                        </tr>
                        <tr>
                            <th>VAT REG. TIN NUMBER:</th>
                            <td>{{ $supplier->vat_reg_tin_number ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td>{{ $supplier->email ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Phone:</th>
                            <td>{{ $supplier->phone ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Address:</th>
                            <td>{{ $supplier->address ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge bg-{{ $supplier->is_active ? 'success' : 'secondary' }}">
                                    {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                        @if($supplier->notes)
                        <tr>
                            <th>Notes:</th>
                            <td>{{ $supplier->notes }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

