<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriesController;
use App\Http\Controllers\Api\ProductsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public routes
Route::get('/products', [ProductsController::class, 'index']);
Route::get('/categories', [CategoriesController::class, 'index']);

// Admin routes
Route::post('/promote-to-admin/{id}', [AuthController::class, 'promoteToAdmin'])->middleware('auth:sanctum');
Route::post('/add-gallery/{id}', [ProductsController::class, 'addGallery'])->middleware('auth:sanctum');
Route::delete('/delete-gallery/{id}', [ProductsController::class, 'deleteGallery'])->middleware('auth:sanctum');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('products', ProductsController::class)->except(['index']);
    Route::apiResource('categories', CategoriesController::class)->except(['index']);
});