<?php

namespace App\Services;

use RuntimeException;
use Illuminate\Support\Facades\Storage;

class CsvChunkerService
{
    public function chunk(
        string $sourceDisk,
        string $sourcePath,
        string $workDisk,
        string $importPublicId,
        int $chunkRows
    ): array {
        $readStream = Storage::disk($sourceDisk)->readStream($sourcePath);
        if (!is_resource($readStream)) {
            throw new RuntimeException("Cannot open source CSV stream: {$sourceDisk}:{$sourcePath}");
        }

        $header = fgetcsv($readStream);
        if (!$header || count($header) < 7) {
            throw new RuntimeException('Invalid CSV header or empty file.');
        }

        $expected = ['customer_id', 'customer_name', 'customer_email', 'amount', 'currency', 'reference_no', 'date_time'];
        $normalizedHeader = array_map(
            fn($h) => strtolower(trim((string) $h)),
            $header
        );

        if ($normalizedHeader !== $expected) {
            throw new RuntimeException('CSV header mismatch. Expected: ' . implode(',', $expected));
        }

        $chunks = [];
        $totalRows = 0;
        $chunkIndex = 0;

        $currentCount = 0;
        $currentTmp = null;
        $currentPath = null;
        $startLine = 2; // the header of csv fileis line 1

        $openNewChunk = function () use (&$currentTmp, &$currentPath, &$currentCount, &$chunkIndex, $workDisk, $importPublicId, $header, &$startLine, $totalRows) {
            $currentTmp = fopen('php://temp', 'w+');
            if (!is_resource($currentTmp)) {
                throw new RuntimeException('Failed to create temp stream for chunk.');
            }

            // include header in each chunk
            fputcsv($currentTmp, $header);

            $chunkIndex++;
            $currentCount = 0;

            $currentPath = sprintf("imports/%s/chunks/chunk_%06d.csv", $importPublicId, $chunkIndex);
        };

        $flushChunk = function () use (&$currentTmp, &$currentPath, $workDisk, &$chunks, &$startLine, &$totalRows, &$currentCount) {
            if (!is_resource($currentTmp)) {
                return;
            }

            rewind($currentTmp);
            Storage::disk($workDisk)->writeStream($currentPath, $currentTmp);
            fclose($currentTmp);

            $chunks[] = [
                'path' => $currentPath,
                'startLine' => $startLine,
            ];

            // chunk start line
            $startLine = $startLine + $currentCount;
        };

        // Prepare a new chunk file with the header.
        $openNewChunk();

        // Start adding chunk data
        while (($row = fgetcsv($readStream)) !== false) {
            // skip fully empty lines
            if (count($row) === 1 && trim((string) $row[0]) === '') {
                continue;
            }

            $currentCount++;
            $totalRows++;

            fputcsv($currentTmp, $row);

            if ($currentCount >= $chunkRows) {
                $flushChunk();
                $openNewChunk();
            }
        }

        // flush final partial chunk if it has data rows
        if ($currentCount > 0) {
            $flushChunk();
        }
        fclose($readStream);

        return [
            'totalRows' => $totalRows,
            'chunkCount' => count($chunks),
            'chunks' => $chunks,
        ];
    }
}
