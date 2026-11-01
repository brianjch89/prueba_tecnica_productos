<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductPriceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// TABLA "products"
// Obtener lista de productos
Route::get('/products', [ProductController::class, 'index']);

// Crear un nuevo producto
Route::post('/products', [ProductController::class, 'store']);

// Obtener un producto por ID
Route::get('/products/{id}', [ProductController::class, 'show']);

// Actualizar un producto
Route::put('/products/{id}', [ProductController::class, 'update']);

// Eliminar un producto
Route::delete('/products/{id}', [ProductController::class, 'destroy']);


// TABLA "products_prices"
// Obtener lista de precios de un producto
Route::get('/products/{id}/prices', [ProductPriceController::class, 'getPricesByProducts']);

// Crear un nuevo precio para un producto
Route::post('/products/{id}/prices', [ProductPriceController::class, 'storePriceForAProduct']);
