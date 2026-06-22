<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AbsenWebAppController;

Route::redirect('/', '/admin');

Route::get('/webapp/absen', [AbsenWebAppController::class, 'showWebapp'])->name('webapp.absen');
