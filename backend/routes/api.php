<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FunnelController;
use App\Http\Controllers\Api\BotController;
use App\Http\Controllers\Api\AiKeyController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/funnels', [FunnelController::class, 'index']);
Route::post('/funnels', [FunnelController::class, 'store']);
Route::get('/funnels/{id}', [FunnelController::class, 'show']);
Route::post('/funnels/{id}/schema', [FunnelController::class, 'updateSchema']);

Route::apiResource('bots', BotController::class);

Route::get('ai-keys/{id}/models', [\App\Http\Controllers\Api\AiKeyController::class, 'getModels']);
Route::apiResource('ai-keys', AiKeyController::class);

