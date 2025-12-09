@extends('layouts.app')

@section('title', 'Product Details')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4"><i class="fas fa-box me-2"></i>Product Details</h2>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-body">
                    <h4>{{ $product->name }}</h4>
                    <p class="text-muted">SKU: {{ $product->sku }}</p>
                    <p>{{ $product->description }}</p>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Category:</strong> {{ $product->category->name }}</p>
                            <p><strong>Price:</strong> ₱{{ number_format($product->price, 2) }}</p>
                            <p><strong>Cost:</strong> ₱{{ number_format($product->cost ?? 0, 2) }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Stock:</strong> {{ $product->quantity }} {{ $product->unit }}</p>
                            <p><strong>Reorder Level:</strong> {{ $product->reorder_level }}</p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-{{ $product->is_active ? 'success' : 'secondary' }}">
                                    {{ $product->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('products.edit', $product) }}" class="btn btn-warning w-100 mb-2">
                        <i class="fas fa-edit me-2"></i>Edit Product
                    </a>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary w-100">
                        <i class="fas fa-arrow-left me-2"></i>Back to Products
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

