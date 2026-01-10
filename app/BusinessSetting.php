<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    protected $fillable = [
        'business_name', 'receipt_type', 'business_type', 'address', 'proprietor',
        'vat_reg_tin', 'phone', 'email', 'bir_authority_to_print',
        'atp_date_issued', 'atp_valid_until', 'printer_name',
        'printer_tin', 'printer_accreditation_no',
        'printer_accreditation_date', 'receipt_footer_note'
    ];

    protected $casts = [
        'atp_date_issued' => 'date',
        'atp_valid_until' => 'date',
        'printer_accreditation_date' => 'date',
    ];

    /**
     * Get the business settings (singleton)
     */
    public static function getSettings()
    {
        return static::firstOrNew(['id' => 1]);
    }
}
