<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Test route
Route::get('test', function() {
    return response()->json(['message' => 'API routes are working']);
});

// Orders routes
Route::prefix('v1')->group(function () {
    Route::get('test', function() {
        return response()->json(['message' => 'V1 API routes are working']);
    });
    
    Route::controller(OrderController::class)->group(function () {
        Route::get('orders', 'index');
        Route::post('orders', 'store');
        Route::put('orders/{order}', 'update');
        Route::patch('orders/{order}/status', 'updateStatus');
        Route::delete('orders/{order}', 'destroy');
    });
});