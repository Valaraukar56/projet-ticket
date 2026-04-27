<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CasinoController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\LeaderboardController;
use Illuminate\Support\Facades\Route;

// API Routes (avant le catch-all)
Route::prefix('api')->group(function () {
    // Auth
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/balance', [AuthController::class, 'updateBalance']);
    Route::delete('/account', [AuthController::class, 'deleteAccount']);

    // Classement
    Route::get('/leaderboard', [LeaderboardController::class, 'index']);

    // Casino Tycoon
    Route::get('/casino', [CasinoController::class, 'get']);
    Route::post('/casino', [CasinoController::class, 'save']);
    Route::post('/casino/unlock', [CasinoController::class, 'unlock']);

    // Chat
    Route::get('/chat', [ChatController::class, 'index']);
    Route::post('/chat', [ChatController::class, 'store']);

    // Admin
    Route::prefix('admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'stats']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::put('/users/{id}/balance', [AdminController::class, 'updateUserBalance']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
        Route::get('/tickets', [AdminController::class, 'getTicketSettings']);
        Route::put('/tickets', [AdminController::class, 'updateTicketSettings']);
    });
});

// Catch-all pour Vue SPA (doit être EN DERNIER)
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
