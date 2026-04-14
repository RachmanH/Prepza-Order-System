<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard', [
            'orderHariIni'   => \App\Models\Order::whereDate('created_at', today())->count(),
            'orderWaiting'   => \App\Models\Order::where('status', 'waiting')->count(),
            'orderProses'    => \App\Models\Order::where('status', 'processing')->count(),
            'orderSelesai'   => \App\Models\Order::where('status', 'done')->whereDate('created_at', today())->count(),
        ]);
    })->name('dashboard');

    Route::get('/order-kiosk', function () {
        return view('order-kiosk');
    })->name('order.kiosk');

    Route::get('/queue-management', function () {
        return view('queue-management');
    })->name('queue.management');

    Route::get('/queue-board', function () {
        return view('queue-board');
    })->name('queue.board');

    Route::get('/cashier-panel', function () {
        return redirect()->route('queue.management');
    })->name('cashier.panel');

    Route::get('/menu-management', function () {
        return view('menu-management');
    })->middleware('super_admin')->name('menu.management');
});
