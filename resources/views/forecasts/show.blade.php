@extends('layouts.app')

@section('title', 'Forecast Details')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4"><i class="fas fa-chart-line me-2"></i>Forecast Details</h2>

    <div class="card">
        <div class="card-body">
            @if(!$forecast->product)
            <div class="alert alert-warning">
                <strong>Warning:</strong> The product associated with this forecast has been deleted.
            </div>
            @endif
            <div class="row mb-4">
                <div class="col-md-6">
                    <p><strong>Product:</strong> {{ $forecast->product->name ?? 'Deleted Product' }}</p>
                    <p><strong>Forecast Date:</strong> {{ $forecast->forecast_date->format('M d, Y') }}</p>
                    <p><strong>Predicted Demand:</strong> <span class="badge bg-primary">{{ $forecast->predicted_demand }}</span></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Confidence Level:</strong> {{ number_format($forecast->confidence_level, 1) }}%</p>
                    <p><strong>Method:</strong> {{ $forecast->method }}</p>
                    <p><strong>Generated:</strong> {{ $forecast->created_at->setTimezone('Asia/Manila')->format('M d, Y h:i:s A') }}</p>
                </div>
            </div>

            @if($forecast->historical_data)
            <div class="mt-4">
                <h5>Historical Data</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($forecast->historical_data as $data)
                            <tr>
                                <td>{{ $data['date'] ?? 'N/A' }}</td>
                                <td>{{ $data['total'] ?? 0 }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <div class="mt-3">
                <a href="{{ route('forecasts.index') }}" class="btn btn-secondary">Back to Forecasts</a>
            </div>
        </div>
    </div>
</div>
@endsection

