<?php

namespace App\Repositories\Contracts;

use App\Models\PaymentImport;

interface PaymentImportRepositoryInterface
{
    public function create(array $data): PaymentImport;

    public function findByIdOrFail(int $id): PaymentImport;

    public function findByPublicIdOrFail(string $id): PaymentImport;

    public function updateStatusById(int $id, string $status, array $meta = []): void;

    public function setChunkingStatsById(int $id, int $totalRows, int $chunkCount): void;

    public function incrementValidInvalidById(int $id, int $valid, int $invalid): void;

    public function markCompletedById(int $id): void;
    public function markFailedById(int $id, string $reason): void;
}
