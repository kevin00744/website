<?php
// routes/api.php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public endpoints
    Route::get('/posts',          [Api\PostApiController::class, 'index']);
    Route::get('/posts/{slug}',   [Api\PostApiController::class, 'show']);
    Route::get('/pages/{slug}',   [Api\PostApiController::class, 'show']);
    Route::get('/categories',     [Api\CategoryApiController::class, 'index']);
    Route::get('/tags',           [Api\TagApiController::class, 'index']);

    // Auth endpoints
    Route::post('/auth/login',    [Api\AuthApiController::class, 'login']);
    Route::post('/auth/logout',   [Api\AuthApiController::class, 'logout'])->middleware('auth:sanctum');

    // Protected API (for headless frontend)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me',         [Api\AuthApiController::class, 'me']);
        Route::apiResource('/posts', Api\PostApiController::class)->except(['index', 'show']);
    });
});
