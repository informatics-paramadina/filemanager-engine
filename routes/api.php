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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/files', [\App\Http\Controllers\FileController::class, 'index']);
Route::get('/files/{uuid}', [\App\Http\Controllers\FileController::class, 'show']);
Route::post('/files', [\App\Http\Controllers\FileController::class, 'insert']);
