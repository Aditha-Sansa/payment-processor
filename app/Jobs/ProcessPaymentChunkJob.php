<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Throwable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\PaymentChunkProcessingService;

class ProcessPaymentChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $timeout = 300;
    public int $tries = 3;
    public int $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $importId,
        public string $chunkPath,
        public int $startLine,
        public string $workDisk,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentChunkProcessingService $paymentChunkProcessor): void
    {
        $paymentChunkProcessor->processChunk(
            importId: $this->importId,
            workDisk: $this->workDisk,
            chunkPath: $this->chunkPath,
            startLine: $this->startLine,
        );
    }

    public function failed(Throwable $e): void
    {
        \Log::error('ProcessPaymentChunkJob failed', [
            'import_id' => $this->importId,
            'chunk' => $this->chunkPath,
            'error' => $e->getMessage(),
        ]);
    }
}
