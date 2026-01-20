<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentImport extends Model
{
    protected $fillable = [
        'public_id',
        'original_filename',
        'source_disk',
        'source_path',
        'status',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'chunk_count',
        'started_at',
        'completed_at',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
