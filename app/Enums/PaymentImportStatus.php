<?php

namespace App\Enums;

enum PaymentImportStatus: string
{
    case UPLOADED   = 'uploaded';
    case CHUNKING   = 'chunking';
    case PROCESSING = 'processing';
    case COMPLETED  = 'completed';
    case FAILED     = 'failed';
}
