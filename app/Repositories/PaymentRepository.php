<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Repositories\Contracts\PaymentRepositoryInterface;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function bulkInsert(array $rows, int $chunkSize = 1000): void
    {
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            DB::table('payments')->insert($chunk);
        }
    }
}
