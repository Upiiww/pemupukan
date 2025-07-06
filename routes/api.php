<?php

use App\Http\Controllers\SoilDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NpkDataController;

// data npk
Route::get('/npk-data', [NpkDataController::class, 'index']);
Route::post('/npk-data', [NpkDataController::class, 'store']);
Route::get('/npk-data/{device_id}', [NpkDataController::class, 'show']);

//data soil
Route::get('/soil-data', [SoilDataController::class, 'index']);
Route::post('/soil-data', [SoilDataController::class, 'store']);

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
