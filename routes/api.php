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
Route::get('/files/{uuid}/download', [\App\Http\Controllers\FileController::class, 'download']);
Route::post('/files', [\App\Http\Controllers\FileController::class, 'insert']);
Route::post('/user/register', [\App\Http\Controllers\AuthController::class, 'register']);
Route::post('/user/login', [\App\Http\Controllers\AuthController::class, 'login']);
Route::post('/user/whitelist', [\App\Http\Controllers\AuthController::class, 'whitelistUser']);
Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'show']);
Route::group(['middleware' => ['web']], function(){
    Route::get('/user/google/redirect', [\App\Http\Controllers\GoogleController::class, 'redirect']);
    Route::get('/user/google/callback', [\App\Http\Controllers\GoogleController::class, 'callback']);
});
