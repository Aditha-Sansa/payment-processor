<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\PaymentImportService;
use App\Http\Requests\UploadPaymentCsvRequest;
use App\Repositories\Contracts\PaymentImportRepositoryInterface;

class PaymentImportController extends Controller
{
    public function store(UploadPaymentCsvRequest $request, PaymentImportService $paymentImportService)
    {
        $import = $paymentImportService->createAndDispatch($request->file('file'));

        return response()->json([
            'import_id' => $import->public_id,
            'status' => $import->status,
        ], 202);
    }

    public function show(string $publicId, PaymentImportRepositoryInterface $imports): JsonResponse
    {
        $import = $imports->findByPublicIdOrFail($publicId);

        return response()->json([
            'import_id' => $import->public_id,
            'status' => $import->status,
            'total_rows' => $import->total_rows,
            'valid_rows' => $import->valid_rows,
            'invalid_rows' => $import->invalid_rows,
            'chunk_count' => $import->chunk_count,
            'started_at' => optional($import->started_at)?->toIso8601String(),
            'completed_at' => optional($import->completed_at)?->toIso8601String(),
            'meta' => $import->meta,
        ]);
    }
}
