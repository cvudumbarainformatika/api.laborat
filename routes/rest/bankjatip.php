<?php

use App\Http\Controllers\Api\Simrs\Kasir\BankjatiminsertController;
use Illuminate\Support\Facades\Route;


Route::middleware(
    [
        'blockIP',
    ]
)
    ->group(function () {
        Route::post('/simrs/kasir/PaymentVirtual/insert', [BankjatiminsertController::class, 'insertqrisbayar']);
    });
