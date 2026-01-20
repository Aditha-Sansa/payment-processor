<?php

namespace App\Repositories\Contracts;

interface PaymentRepositoryInterface
{
    public function bulkInsert(array $rows, int $chunkSize = 1000): void;
}
