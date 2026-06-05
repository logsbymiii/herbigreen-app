<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

Route::post('/webhook/fonnte', [WebhookController::class, 'receive']);
Route::post('/webhook/telegram', [WebhookController::class, 'receiveTelegram']);
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
