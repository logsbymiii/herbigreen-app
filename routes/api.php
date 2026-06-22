<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Api\AbsenWebAppController;

Route::post('/webhook/fonnte', [WebhookController::class, 'receiveFonnte']);
Route::post('/webhook/telegram', [WebhookController::class, 'receiveTelegram']);

Route::post('/webapp/submit-absen', [AbsenWebAppController::class, 'submitAbsen']);
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
