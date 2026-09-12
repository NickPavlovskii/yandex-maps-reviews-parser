<?php

use Illuminate\Support\Facades\Route;
use L5Swagger\Http\Controllers\SwaggerAssetController;
use L5Swagger\Http\Controllers\SwaggerController;
use L5Swagger\Http\Middleware\Config as L5SwaggerConfig;

Route::group([
    'l5-swagger.documentation' => 'default',
    'middleware' => [L5SwaggerConfig::class],
], static function (): void {
    Route::get('/documentation', [SwaggerController::class, 'api'])
        ->name('l5-swagger.default.api');

    Route::get('/documentation/docs', [SwaggerController::class, 'docs'])
        ->name('l5-swagger.default.docs');

    Route::get('/documentation/docs/asset/{asset}', [SwaggerAssetController::class, 'index'])
        ->where('asset', '.*')
        ->name('l5-swagger.default.asset');

    Route::get('/documentation/oauth2-callback', [SwaggerController::class, 'oauth2Callback'])
        ->name('l5-swagger.default.oauth2_callback');
});
