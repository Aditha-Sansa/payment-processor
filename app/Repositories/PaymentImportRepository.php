<?php

namespace App\Repositories;

use App\Models\PaymentImport;
use App\Enums\PaymentImportStatus;
use Illuminate\Support\Facades\DB;
use App\Repositories\Contracts\PaymentImportRepositoryInterface;

class PaymentImportRepository implements PaymentImportRepositoryInterface
{
    public function create(array $data): PaymentImport
    {
        return PaymentImport::query()->create($data);
    }

    public function findByIdOrFail(int $id): PaymentImport
    {
        return PaymentImport::query()->findOrFail($id);
    }

    public function findByPublicIdOrFail(string $publicId): PaymentImport
    {
        return PaymentImport::query()->where('public_id', $publicId)->firstOrFail();
    }

    public function updateStatusById(int $id, string $status, array $meta = []): void
    {
        $import = PaymentImport::query()->findOrFail($id);

        $mergedMeta = array_merge($import->meta ?? [], $meta);

        $import->update([
            'status' => $status,
            'meta' => $mergedMeta,
        ]);
    }

    public function setChunkingStatsById(int $id, int $totalRows, int $chunkCount): void
    {
        PaymentImport::query()->whereKey($id)->update([
            'total_rows' => $totalRows,
            'chunk_count' => $chunkCount,
        ]);
    }


    public function incrementValidInvalidById(int $id, int $valid, int $invalid): void
    {
        PaymentImport::query()->whereKey($id)->increment('valid_rows', $valid);
        PaymentImport::query()->whereKey($id)->increment('invalid_rows', $invalid);
    }

    public function markCompletedById(int $id): void
    {
        PaymentImport::query()->whereKey($id)->update([
            'status' => PaymentImportStatus::COMPLETED->value,
            'completed_at' => now(),
        ]);
    }

    public function markFailedById(int $id, string $reason): void
    {
        $import = PaymentImport::query()->findOrFail($id);

        $meta = array_merge($import->meta ?? [], ['failure_reason' => $reason]);

        $import->update([
            'status' => PaymentImportStatus::FAILED->value,
            'completed_at' => now(),
            'meta' => $meta,
        ]);
    }

}
