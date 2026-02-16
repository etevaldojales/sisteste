<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/search/products', [ProductController::class, 'search']);
Route::post('/search/products/index', [ProductController::class, 'createSearchIndex']);
Route::delete('/search/products/index', [ProductController::class, 'deleteSearchIndex']);

Route::get('/products', [ProductController::class, 'index']);
Route::post('/products', [ProductController::class, 'store']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::put('/products/{id}', [ProductController::class, 'update']);
Route::delete('/products/{id}', [ProductController::class, 'destroy']);

Route::post('/products/{id}/image', [ProductController::class, 'uploadImage']);



