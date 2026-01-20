<?php

namespace App\Services;

use Illuminate\Support\Str;
use App\Models\PaymentImport;
use Illuminate\Http\UploadedFile;

use App\Enums\PaymentImportStatus;
use App\Jobs\SplitCsvIntoChunksJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Contracts\PaymentImportRepositoryInterface;

class PaymentImportService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private readonly PaymentImportRepositoryInterface $paymentImportRepository
    ) {
    }

    public function createAndDispatch(UploadedFile $file): PaymentImport
    {
        $publicId = (string) Str::uuid7();
        $disk = config('payments.upload_disk');
        $path = "imports/{$publicId}/source.csv";

        Storage::disk($disk)->putFileAs("imports/{$publicId}", $file, 'source.csv');

        $import = $this->paymentImportRepository->create([
            'public_id' => $publicId,
            'status' => PaymentImportStatus::UPLOADED->value,
            'source_disk' => $disk,
            'source_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'chunk_count' => 0,
            'started_at' => now()
        ]);

        SplitCsvIntoChunksJob::dispatch($import->id)
            ->onQueue(config('payments.queue.chunking'));

        Log::info('Payment import created', ['import_id' => $import->id, 'public_id' => $publicId]);

        return $import;
    }

    public function markProcessing(int $importId, array $meta = []): void
    {
        $this->paymentImportRepository->updateStatusById($importId, PaymentImportStatus::PROCESSING->value, $meta);
    }

    public function markChunking(int $importId, array $meta = []): void
    {
        $this->paymentImportRepository->updateStatusById($importId, PaymentImportStatus::CHUNKING->value, $meta);
    }

    public function complete(int $importId): void
    {
        $this->paymentImportRepository->markCompletedById($importId);
    }

    public function fail(int $importId, \Throwable $e): void
    {
        $this->paymentImportRepository->markFailedById($importId, $e->getMessage());
    }
}
