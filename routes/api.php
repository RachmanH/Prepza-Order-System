<?php

use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CashierOrderController;
use App\Http\Controllers\Api\VoiceOrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/menus', [MenuController::class, 'index']);
Route::post('/menus/resolve', [MenuController::class, 'resolve']);
Route::post('/orders/voice', [VoiceOrderController::class, 'store']);
Route::post('/orders/voice/preview', [VoiceOrderController::class, 'preview']);
Route::post('/orders/voice/transcribe', [VoiceOrderController::class, 'transcribe']);

Route::get('/queue/orders', [CashierOrderController::class, 'index']);
Route::get('/queue/board', [CashierOrderController::class, 'board']);
Route::post('/queue/trends/update', [CashierOrderController::class, 'updateTrend']);
Route::patch('/queue/orders/{order}/start', [CashierOrderController::class, 'startProcessing']);
Route::patch('/queue/orders/{order}/finish', [CashierOrderController::class, 'finish']);
Route::patch('/queue/orders/{order}/cancel', [CashierOrderController::class, 'cancel']);
Route::patch('/queue/orders/{order}/external-update', [CashierOrderController::class, 'simulateExternalUpdate']);
Route::post('/queue/orders/{order}/append-voice', [CashierOrderController::class, 'appendVoice']);
Route::patch('/queue/orders/{order}/items/{item}', [CashierOrderController::class, 'updateItem']);
Route::delete('/queue/orders/{order}/items/{item}', [CashierOrderController::class, 'removeItem']);

Route::get('/cashier/orders', [CashierOrderController::class, 'index']);
Route::patch('/cashier/orders/{order}/confirm', [CashierOrderController::class, 'confirm']);
Route::patch('/cashier/orders/{order}/cancel', [CashierOrderController::class, 'cancel']);
Route::post('/cashier/orders/{order}/append-voice', [CashierOrderController::class, 'appendVoice']);
Route::patch('/cashier/orders/{order}/items/{item}', [CashierOrderController::class, 'updateItem']);
Route::delete('/cashier/orders/{order}/items/{item}', [CashierOrderController::class, 'removeItem']);

Route::middleware(['web', 'super_admin'])->prefix('/admin')->group(function () {
    // Category routes
    Route::get('/categories', [CategoryController::class, 'adminIndex']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::patch('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    
    // Menu routes
    Route::get('/menus', [MenuController::class, 'adminIndex']);
    Route::post('/menus', [MenuController::class, 'store']);
    Route::patch('/menus/{menu}', [MenuController::class, 'update']);
    Route::delete('/menus/{menu}', [MenuController::class, 'destroy']);
    Route::delete('/menus/{menu}/image', [MenuController::class, 'removeImage']);
    Route::patch('/menus/{menu}/toggle', [MenuController::class, 'toggle']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
