<?php

namespace App\Repositories;

use App\Models\PaymentImport;
use App\Repositories\Contracts\PaymentImportRepositoryInterface;

class PaymentImportRepository implements PaymentImportRepositoryInterface
{
    public function create(array $data): PaymentImport
    {
        return PaymentImport::query()->create($data);
    }

    public function findOrFail(string $id): PaymentImport
    {
        return PaymentImport::query()->findOrFail($id);
    }
}
