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
                    <p><strong>Date:</strong> {{ $sale->created_at->setTimezone('Asia/Manila')->format('M d, Y h:i:s A') }}</p>
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
                            <th>Unit</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->items as $item)
                        <tr>
                            <td>{{ $item->product->name }}</td>
                            <td>{{ number_format($item->quantity, 0) }}</td>
                            <td>{{ ucfirst($item->unit ?? $item->product->unit ?? 'pcs') }}</td>
                            <td>₱{{ number_format($item->unit_price, 2) }}</td>
                            <td>₱{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4">Subtotal:</th>
                            <th>₱{{ number_format($sale->subtotal, 2) }}</th>
                        </tr>
                        @if($sale->tax > 0)
                        <tr>
                            <th colspan="4">Tax:</th>
                            <th>₱{{ number_format($sale->tax, 2) }}</th>
                        </tr>
                        @endif
                        @if($sale->discount > 0)
                        <tr>
                            <th colspan="4">Discount:</th>
                            <th>₱{{ number_format($sale->discount, 2) }}</th>
                        </tr>
                        @endif
                        <tr>
                            <th colspan="4">Total:</th>
                            <th>₱{{ number_format($sale->total, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-3">
                <button onclick="goBack()" class="btn btn-secondary">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function goBack() {
    // Check if user came from dashboard
    const referrer = document.referrer;
    const dashboardUrl = '{{ route("dashboard") }}';
    const salesIndexUrl = '{{ route("sales.index") }}';
    
    console.log('Referrer:', referrer);
    
    // If referrer contains dashboard, go back to dashboard
    if (referrer && (referrer.includes('/dashboard') || referrer.indexOf('dashboard') !== -1)) {
        console.log('Going back to dashboard');
        window.location.href = dashboardUrl;
    } else if (window.history.length > 1) {
        // Use browser back if available
        console.log('Using browser back');
        window.history.back();
    } else {
        // Fallback to sales index
        console.log('Fallback to sales index');
        window.location.href = salesIndexUrl;
    }
}
</script>
@endsection
