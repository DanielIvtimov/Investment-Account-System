<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::post('/clients', [ClientController::class, 'store']);
Route::get('/clients/{client}', [ClientController::class, 'show']);

Route::post('/clients/{client}/deposit', [TransactionController::class, 'deposit']);
Route::post('/clients/{client}/withdraw', [TransactionController::class, 'withdraw']);
Route::post('/clients/{client}/buy', [TransactionController::class, 'buy']);
Route::post('/clients/{client}/sell', [TransactionController::class, 'sell']);

Route::get('/clients/{client}/transactions', [TransactionController::class, 'index']);
