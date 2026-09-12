<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

require __DIR__.'/swagger.php';

Route::post('/organizations', [OrganizationController::class, 'store']);
Route::get('/organizations/{organization}', [OrganizationController::class, 'show']);
Route::get('/organizations/{organization}/reviews', [OrganizationController::class, 'reviews']);

// Старое имя того же боевого store(): только очередь, без парсинга в HTTP.
Route::post('/yandex/parse', [OrganizationController::class, 'store']);

// Служебный sync-путь для Swagger/отладки. В production middleware отдаёт 404 до валидации.
Route::post('/organizations/parse', [OrganizationController::class, 'parse'])
    ->middleware('parser.sync');
