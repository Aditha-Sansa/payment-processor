<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadPaymentCsvRequest;
use App\Services\PaymentImportService;
use Illuminate\Http\Request;

class PaymentImportController extends Controller
{
    public function store(UploadPaymentCsvRequest $request, PaymentImportService $paymentImportService)
    {
        $import = $paymentImportService->createAndDispatch($request->file('file'));

        return response()->json([
            'import_id' => $import->import_id,
            'status' => $import->status,
        ], 202);
    }

}
