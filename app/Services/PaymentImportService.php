<?php

namespace App\Services;

use Illuminate\Support\Str;
use App\Models\PaymentImport;
use Illuminate\Http\UploadedFile;

use App\Enums\PaymentImportStatus;
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
        $importId = (string) Str::uuid();
        $disk = config('payments.upload_disk');
        $path = "imports/{$importId}/source.csv";

        Storage::disk($disk)->putFileAs("imports/{$importId}", $file, 'source.csv');

        $import = $this->paymentImportRepository->create([
            'import_id' => $importId,
            'status' => PaymentImportStatus::UPLOADED->value,
            'source_disk' => $disk,
            'source_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'chunk_count' => 0,
            'started_at' => now()
        ]);

        Log::info('Payment import created and chunking dispatched', ['import_id' => $importId]);

        return $import;
    }
}
