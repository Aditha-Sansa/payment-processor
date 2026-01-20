<?php

namespace App\Jobs;

use Throwable;
use Illuminate\Bus\Batch;
use App\Services\CsvChunkerService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessPaymentChunkJob;
use App\Services\PaymentImportService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Repositories\Contracts\PaymentImportRepositoryInterface;

class SplitCsvIntoChunksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $importId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(
        PaymentImportRepositoryInterface $imports,
        PaymentImportService $importService,
        CsvChunkerService $csvChunkerService
    ): void {
        $import = $imports->findByIdOrFail($this->importId);

        $importService->markChunking($this->importId);

        $workDisk = config('payments.work_disk');
        $chunkRows = config('payments.chunk_rows', 10000);

        $result = $csvChunkerService->chunk(
            $import->source_disk,
            $import->source_path,
            $workDisk,
            $import->public_id,
            $chunkRows
        );
        \Log::info('Chunker result', ['result' => $result]);
        $imports->setChunkingStatsById(
            $this->importId,
            $result['totalRows'],
            $result['chunkCount']
        );

        $jobs = [];
        foreach ($result['chunks'] as $i => $chunk) {
            $jobs[] = (new ProcessPaymentChunkJob(
                importId: $this->importId,
                chunkPath: $chunk['path'],
                startLine: $chunk['startLine'],
                workDisk: $workDisk,
            ))->onQueue(config('payments.queue.processing'));
        }

        $importService->markProcessing($this->importId, [
            'chunk_rows' => $chunkRows,
            'chunk_count' => $result['chunkCount'],
        ]);

        $importId = $this->importId;
        $publicId = $import->public_id;

        Bus::batch($jobs)
            ->name("payment-import:{$this->importId}")
            ->allowFailures()
            ->then(function (Batch $batch) use ($importId) {
                $importService = app(\App\Services\PaymentImportService::class);
                if ($batch->failedJobs === 0) {
                    $importService->complete($importId);
                } else {
                    //failed if any chunk fails
                    $importService->fail($importId, new \RuntimeException("Batch completed with {$batch->failedJobs} failed jobs."));
                }
            })
            ->catch(function (Batch $batch, Throwable $e) use ($importId) {
                $importService = app(\App\Services\PaymentImportService::class);
                $importService->fail($importId, $e);
            })
            ->finally(function (Batch $batch) use ($importId) {
                Log::info('Import batch finished', [
                    'import_id' => $importId,
                    'total_jobs' => $batch->totalJobs,
                    'failed_jobs' => $batch->failedJobs,
                ]);
            })
            ->onQueue(config('payments.queue.processing'))
            ->dispatch();
    }

    public function failed(Throwable $e): void
    {
        Log::error('SplitCsvIntoChunksJob failed', [
            'import_id' => $this->importId,
            'error' => $e->getMessage(),
        ]);
    }
}
