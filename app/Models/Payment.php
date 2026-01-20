<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'public_id',
        'payment_import_id',
        'row_number',
        'customer_id',
        'customer_name',
        'customer_email',
        'reference_no',
        'original_amount',
        'currency',
        'usd_amount',
        'exchange_rate',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];
}
