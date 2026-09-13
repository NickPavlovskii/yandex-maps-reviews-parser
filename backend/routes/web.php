<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware(['throttle:login-route', 'throttle:login'])
    ->name('login');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/user', [AuthController::class, 'user'])->name('user');
});

Route::get('/{spa?}', SpaController::class)
    ->where('spa', '^(?!api(?:/|$)|sanctum(?:/|$)|up$|user$).*');
