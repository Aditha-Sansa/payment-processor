<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'provider',
        'base',
        'date',
        'rates',
        'fetched_at',
    ];

    protected $casts = [
        'rates' => 'array',
        'date' => 'date',
        'fetched_at' => 'datetime',
    ];
}
