<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Tournament\TournamentController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API Version 1
Route::prefix('v1')->group(function () {
    
    // Tournament routes
    Route::prefix('tournaments')->group(function () {
        // Public routes (no authentication required)
        Route::get('/', [TournamentController::class, 'index']);
        Route::get('/featured', [TournamentController::class, 'featured']);
        Route::get('/upcoming', [TournamentController::class, 'upcoming']);
        Route::get('/statistics', [TournamentController::class, 'statistics']);
        Route::get('/{slug}', [TournamentController::class, 'show']);
        
        // Protected routes (authentication required)
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/', [TournamentController::class, 'store']);
            Route::put('/{slug}', [TournamentController::class, 'update']);
            Route::delete('/{slug}', [TournamentController::class, 'destroy']);
            Route::post('/{slug}/register', [TournamentController::class, 'registerPlayer']);
        });
    });
    
}); 