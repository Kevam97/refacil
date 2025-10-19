<?php

use App\Http\Controllers\v1\BalanceController;
use App\Http\Controllers\v1\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('transactions', [TransactionController::class, 'store']);
    Route::get('transactions/{user}', [TransactionController::class, 'history']);
    Route::get('balance/{user}', [BalanceController::class, 'show']);
});
