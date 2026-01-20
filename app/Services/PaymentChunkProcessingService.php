<?php

namespace App\Services;

use RuntimeException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\PaymentRowValidatorService;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\ExchangeRateRepositoryInterface;
use App\Repositories\Contracts\PaymentImportRepositoryInterface;

class PaymentChunkProcessingService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentImportRepositoryInterface $imports,
        private readonly ExchangeRateRepositoryInterface $ratesRepo,
        private readonly PaymentRowValidatorService $validator,
    ) {
    }

    public function processChunk(
        string $importId,
        string $workDisk,
        string $chunkPath,
        int $startLine
    ) {
        $stream = Storage::disk($workDisk)->readStream($chunkPath);
        if (!is_resource($stream)) {
            throw new RuntimeException("Cannot read chunk: {$workDisk}:{$chunkPath}");
        }

        $header = fgetcsv($stream);
        if (!$header) {
            fclose($stream);
            throw new RuntimeException("Chunk missing header: {$chunkPath}");
        }
        $header = array_map(fn($h) => strtolower(trim((string) $h)), $header);

        $rates = $this->ratesRepo->getLatestRates(config('payments.exchange.base', 'USD'));

        $validRows = [];
        $valid = 0;
        $invalid = 0;

        $logTmp = fopen('php://temp', 'w+');
        $logPath = preg_replace('/chunks\/chunk_/', 'logs/chunk_', $chunkPath);
        $logPath = preg_replace('/\.csv$/', '.ndjson', $logPath);

        $lineNumber = $startLine - 1;

        while (($row = fgetcsv($stream)) !== false) {
            $lineNumber++;

            if (count($row) === 1 && trim((string) $row[0]) === '') {
                continue;
            }

            $assoc = [];
            foreach ($header as $i => $key) {
                $assoc[$key] = $row[$i] ?? null;
            }

            $result = $this->validator->validate($assoc);

            if (!$result['ok']) {
                $invalid++;
                $this->writeLog($logTmp, [
                    'line' => $lineNumber,
                    'status' => 'failure',
                    'reference_no' => $assoc['reference_no'] ?? null,
                    'errors' => $result['errors'] ?? ['Unknown error'],
                ]);
                continue;
            }

            $n = $result['normalized'];

            $currency = $n['currency'];
            $amount = $n['original_amount'];


            if ($currency === 'USD') {
                $usd = $amount;
                $exchangeRate = 1.0;
            } else {
                $r = $rates[$currency] ?? null;
                if (!$r || $r <= 0) {
                    $invalid++;
                    $this->writeLog($logTmp, [
                        'line' => $lineNumber,
                        'status' => 'failure',
                        'reference_no' => $n['reference_no'],
                        'errors' => ["Missing exchange rate for {$currency}"],
                    ]);
                    continue;
                }

                $usd = $amount / $r;
                $exchangeRate = 1.0 / $r;
            }

            $paymentPublicId = Str::uuid7();

            $valid++;
            $validRows[] = [
                'public_id' => $paymentPublicId,
                'payment_import_id' => $importId,
                'row_number' => $lineNumber,
                'customer_id' => $n['customer_id'],
                'customer_name' => $n['customer_name'],
                'customer_email' => $n['customer_email'],
                'reference_no' => $n['reference_no'],
                'original_amount' => $amount,
                'currency' => $currency,
                'usd_amount' => round($usd, 6),
                'exchange_rate' => $exchangeRate,
                'paid_at' => $n['paid_at'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $this->writeLog($logTmp, [
                'line' => $lineNumber,
                'status' => 'success',
                'reference_no' => $n['reference_no'],
                'usd_amount' => round($usd, 6),
                'currency' => $currency,
            ]);

            // Periodic flush to keep memory stable
            if (count($validRows) >= 2000) {
                $this->payments->bulkInsert($validRows, 1000);
                $validRows = [];
            }
        }

        // flush remaining
        if ($validRows) {
            $this->payments->bulkInsert($validRows, 1000);
        }

        fclose($stream);

        rewind($logTmp);
        Storage::disk($workDisk)->writeStream($logPath, $logTmp);
        fclose($logTmp);

        $this->imports->incrementValidInvalidById($importId, $valid, $invalid);

        Log::info('Chunk processed', [
            'import_id' => $importId,
            'chunk' => $chunkPath,
            'valid' => $valid,
            'invalid' => $invalid,
            'log' => $logPath,
        ]);

    }

    private function writeLog($stream, array $payload): void
    {
        fwrite($stream, json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n");
    }
}
