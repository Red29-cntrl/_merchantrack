<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'sku', 'barcode', 'description', 'category_id', 'supplier_id', 'price', 'cost',
        'quantity', 'reorder_level', 'unit', 'image', 'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function demandForecasts()
    {
        return $this->hasMany(DemandForecast::class);
    }

    public function isLowStock()
    {
        return $this->quantity <= $this->reorder_level;
    }
}
