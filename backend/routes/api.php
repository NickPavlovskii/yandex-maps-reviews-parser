<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

require __DIR__.'/swagger.php';

Route::post('/organizations/parse', [OrganizationController::class, 'parse']);
Route::post('/organizations', [OrganizationController::class, 'store']);
Route::get('/organizations/{organization}', [OrganizationController::class, 'show']);
Route::get('/organizations/{organization}/reviews', [OrganizationController::class, 'reviews']);
Route::post('/yandex/parse', [OrganizationController::class, 'store']);
