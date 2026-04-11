<?php

use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\CashierOrderController;
use App\Http\Controllers\Api\VoiceOrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/menus', [MenuController::class, 'index']);
Route::post('/menus/resolve', [MenuController::class, 'resolve']);
Route::post('/orders/voice', [VoiceOrderController::class, 'store']);
Route::post('/orders/voice/preview', [VoiceOrderController::class, 'preview']);
Route::post('/orders/voice/transcribe', [VoiceOrderController::class, 'transcribe']);

Route::get('/cashier/orders', [CashierOrderController::class, 'index']);
Route::patch('/cashier/orders/{order}/confirm', [CashierOrderController::class, 'confirm']);
Route::patch('/cashier/orders/{order}/cancel', [CashierOrderController::class, 'cancel']);
Route::post('/cashier/orders/{order}/append-voice', [CashierOrderController::class, 'appendVoice']);
Route::patch('/cashier/orders/{order}/items/{item}', [CashierOrderController::class, 'updateItem']);
Route::delete('/cashier/orders/{order}/items/{item}', [CashierOrderController::class, 'removeItem']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
