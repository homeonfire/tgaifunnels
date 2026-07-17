<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FunnelController;
use App\Http\Controllers\Api\BotController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Наш новый эндпоинт
Route::get('/funnels', [FunnelController::class, 'index']);
Route::post('/funnels', [FunnelController::class, 'store']);
Route::get('/funnels/{id}', [FunnelController::class, 'show']);
Route::post('/funnels/{id}/schema', [FunnelController::class, 'updateSchema']);

Route::get('/bots', [BotController::class, 'index']);