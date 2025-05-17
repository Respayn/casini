<?php

namespace App\Http\Controllers;

use App\Data\Accounting\WorkActData;
use App\Http\Requests\IntegrationAuthRequest;
use App\Services\AccountingService;
use Illuminate\Http\JsonResponse;

class AccountingController extends Controller
{
    public function handleWorkActs(IntegrationAuthRequest $request, AccountingService $service): JsonResponse
    {
        $service->processWorkActs(WorkActData::collection($request->input('data')));
        return response()->json(['success' => true]);
    }
}
