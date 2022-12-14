<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth.token')->post('/productsPrices', [\App\Http\Controllers\LowpriceController::class, 'productsPrices']);
Route::middleware('auth.token')->get('/productsPricesCsv/{merchantId}', [\App\Http\Controllers\LowpriceController::class, 'productPricesCsv']);
Route::middleware('auth.token')->get('/latestPrices/{merchantId}', [\App\Http\Controllers\LowpriceController::class, 'latestPrices']);
