<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NpkDataController;

// Route::get('/npk-data', [NpkDataController::class, 'index']);
// Route::post('/npk-data', [NpkDataController::class, 'store']);
// Route::get('/npk-data/{device_id}', [NpkDataController::class, 'show']);


Route::get('/', function () {
    return  redirect('/admin');
});
