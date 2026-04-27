<?php

use Illuminate\Support\Facades\Route;

// Catch-all pour Vue SPA
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
