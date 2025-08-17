<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriesController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Categories routes
    Route::post('categories', [CategoriesController::class, 'store']);
    Route::get('categories', [CategoriesController::class, 'index']);
    Route::get('categories/{id}', [CategoriesController::class, 'show']);
    Route::put('categories/{id}', [CategoriesController::class, 'update']);
    Route::delete('categories/{id}', [CategoriesController::class, 'destroy']);
});