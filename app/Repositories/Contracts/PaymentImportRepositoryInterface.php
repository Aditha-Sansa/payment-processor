<?php

namespace App\Repositories\Contracts;

use App\Models\PaymentImport;

interface PaymentImportRepositoryInterface
{
    public function create(array $data): PaymentImport;

    public function findOrFail(string $id): PaymentImport;
}
