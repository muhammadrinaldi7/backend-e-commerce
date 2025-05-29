<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriesController;
use App\Http\Controllers\Api\OrdersController;
use App\Http\Controllers\Api\PaymentsController;
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
Route::get('/products/{id}', [ProductsController::class, 'show']);
Route::get('/categories', [CategoriesController::class, 'index']);
Route::get('/products/category/{id}', [ProductsController::class, 'filterByCategory']);
Route::get('/products/search', [ProductsController::class, 'search']);

// Protected routes not in api resource
Route::post('/promote-to-admin/{id}', [AuthController::class, 'promoteToAdmin'])->middleware('auth:sanctum');
Route::get('/get-gallery/{id}', [ProductsController::class, 'getGallery'])->middleware('auth:sanctum');
Route::post('/add-gallery/{id}', [ProductsController::class, 'addGallery'])->middleware('auth:sanctum');
Route::delete('/delete-gallery/{id}', [ProductsController::class, 'deleteGallery'])->middleware('auth:sanctum');
Route::post('/xendit-callback', [PaymentsController::class, 'callback']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('products', ProductsController::class)->except(['index', 'show']);
    Route::apiResource('categories', CategoriesController::class)->except(['index']);
    Route::apiResource('payments', PaymentsController::class);
    Route::apiResource('orders', OrdersController::class)->only(['index', 'store', 'show']);
});
// Route::apiResource('orders', OrdersController::class)->middleware('auth:sanctum')->only(['index', 'store', 'show']);