<?php

namespace App\Http\Controllers;

use App\Data\Payment\PaymentData;
use App\Http\Requests\PaymentRequest;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function handlePayments(PaymentRequest $request): JsonResponse
    {
        $request->authenticate();
        $this->paymentService->processPayments(
            PaymentData::collection($request->input('data'))
        );
        return response()->json(['success' => true]);
    }
}
