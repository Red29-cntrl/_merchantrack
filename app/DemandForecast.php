<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DemandForecast extends Model
{
    protected $fillable = [
        'product_id', 'forecast_date', 'predicted_demand',
        'confidence_level', 'method', 'historical_data'
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'confidence_level' => 'decimal:2',
        'historical_data' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
