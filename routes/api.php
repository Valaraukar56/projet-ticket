<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CasinoController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/me', [AuthController::class, 'me']);
Route::post('/balance', [AuthController::class, 'updateBalance']);
Route::delete('/account', [AuthController::class, 'deleteAccount']);

// Classement
Route::get('/leaderboard', [LeaderboardController::class, 'index']);

// Paiements
Route::post('/payments/purchase', [PaymentController::class, 'purchase']);
Route::post('/payments/win', [PaymentController::class, 'win']);
Route::get('/payments', [PaymentController::class, 'history']);

// Tickets API avancée
Route::post('/tickets', [TicketController::class, 'store']);
Route::patch('/tickets/{id}/complete', [TicketController::class, 'complete']);
Route::get('/open-tickets', [TicketController::class, 'openTickets']);
Route::get('/closed-tickets', [TicketController::class, 'closedTickets']);
Route::get('/users/{email}/tickets', [TicketController::class, 'ticketsByUser']);

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
