@extends('layouts.app')

@section('title', 'Sale Details')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4"><i class="fas fa-receipt me-2"></i>Sale Details</h2>

    <div class="card">
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <p><strong>Sale Number:</strong> {{ $sale->sale_number }}</p>
                    <p><strong>Date:</strong> {{ $sale->created_at->format('M d, Y H:i') }}</p>
                    <p><strong>Cashier:</strong> {{ $sale->user->name }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Payment Method:</strong> {{ ucfirst($sale->payment_method) }}</p>
                    @if($sale->notes)
                    <p><strong>Notes:</strong> {{ $sale->notes }}</p>
                    @endif
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->items as $item)
                        <tr>
                            <td>{{ $item->product->name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>₱{{ number_format($item->unit_price, 2) }}</td>
                            <td>₱{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3">Subtotal:</th>
                            <th>₱{{ number_format($sale->subtotal, 2) }}</th>
                        </tr>
                        @if($sale->tax > 0)
                        <tr>
                            <th colspan="3">Tax:</th>
                            <th>₱{{ number_format($sale->tax, 2) }}</th>
                        </tr>
                        @endif
                        @if($sale->discount > 0)
                        <tr>
                            <th colspan="3">Discount:</th>
                            <th>₱{{ number_format($sale->discount, 2) }}</th>
                        </tr>
                        @endif
                        <tr>
                            <th colspan="3">Total:</th>
                            <th>₱{{ number_format($sale->total, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-3">
                <a href="{{ route('sales.index') }}" class="btn btn-secondary">Back to Sales</a>
            </div>
        </div>
    </div>
</div>
@endsection

