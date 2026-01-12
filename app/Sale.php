<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'sale_number', 'user_id', 'subtotal', 'tax', 'discount',
        'total', 'payment_method', 'notes', 'sale_date'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Generate the next sequential sale number
     * Format: 0000-0000-0000-0001 (16 digits grouped by 4)
     * Uses database lock to prevent race conditions
     * Note: Should be called within a database transaction
     */
    public static function generateSaleNumber()
    {
        // Lock the table to prevent concurrent access and read all sale numbers
        $sales = static::lockForUpdate()->pluck('sale_number');

        $maxNumber = 0;

        foreach ($sales as $saleNumber) {
            // Remove any non-digit characters (handles legacy formats like SALE-XXXX, DR-XXXX, or dashed numbers)
            $digitsOnly = preg_replace('/\D/', '', $saleNumber);

            if ($digitsOnly === '' || !ctype_digit($digitsOnly)) {
                continue;
            }

            $number = (int) $digitsOnly;
            if ($number > $maxNumber) {
                $maxNumber = $number;
            }
        }

        // Increment from the highest number found, or start from 1
        $nextNumber = $maxNumber + 1;

        // Pad to 16 digits
        $padded = str_pad($nextNumber, 16, '0', STR_PAD_LEFT);

        // Group as 0000-0000-0000-0001
        return trim(chunk_split($padded, 4, '-'), '-');
    }
}
