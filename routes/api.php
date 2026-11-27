<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Маршруты интеграции с 1С
Route::prefix('/1c-integration')->group(function () {
    // Акты выполненных работ
    Route::post('/work-acts', [AccountingController::class, 'handleWorkActs'])
        ->withoutMiddleware([
            'auth:sanctum',
            'session',
        ])
        ->name('api.1c-integration.work-acts');

    // Платежные операции (ДРС и сверка)
    Route::post('/payments', [PaymentController::class, 'handlePayments'])
        ->name('api.1c-integration.payments');
});

// Адрес, на который 1С отправляет платежи в SAO: при переезде в 1С меняется только домен
Route::post('/processOperations', [PaymentController::class, 'handlePayments'])
    ->name('api.1c-integration.process-operations');

Route::get('/user', function (Request $request) {
    return $request->user();
});
