@extends('layouts.app')

@section('title', 'Category Details')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4"><i class="fas fa-tag me-2"></i>Category: {{ $category->name }}</h2>

    <div class="card mb-3">
        <div class="card-body">
            <p><strong>Description:</strong> {{ $category->description ?? 'N/A' }}</p>
            <p><strong>Total Products:</strong> {{ $category->products->count() }}</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Products in this Category</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($category->products as $product)
                        <tr>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->name }}</td>
                            <td>₱{{ number_format($product->price, 2) }}</td>
                            <td>{{ $product->quantity }} {{ $product->unit }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">No products in this category</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

