@extends('layouts.app')

@section('title', 'Sales')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4"><i class="fas fa-shopping-cart me-2"></i>Sales</h2>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('sales.index') }}" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by sale number" 
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
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
                            <th>Sale #</th>
                            <th>Date</th>
                            <th>Cashier</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                        <tr>
                            <td>{{ $sale->sale_number }}</td>
                            <td>{{ $sale->created_at->format('M d, Y H:i') }}</td>
                            <td>{{ $sale->user->name }}</td>
                            <td>{{ $sale->items->count() }}</td>
                            <td>₱{{ number_format($sale->total, 2) }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($sale->payment_method) }}</span></td>
                            <td>
                                <a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No sales found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $sales->links() }}
        </div>
    </div>
</div>
@endsection

