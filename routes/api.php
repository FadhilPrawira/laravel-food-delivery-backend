<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// login
Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
// Logout
Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');

// Customer register
Route::post('/customer/register', [App\Http\Controllers\Api\AuthController::class, 'customerRegister']);
// Restaurant register
Route::post('/restaurant/register', [App\Http\Controllers\Api\AuthController::class, 'restaurantRegister']);
// Driver register
Route::post('/driver/register', [App\Http\Controllers\Api\AuthController::class, 'driverRegister']);

// Get detail authenticated user. All role
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Update latitude and longitude
// TODO: Change to POST, but in Postman send with x-www-form-urlencoded and key: _method, value: PUT
Route::put('/user/update-latlong', [App\Http\Controllers\Api\AuthController::class,  'updateLatLong'])->middleware('auth:sanctum');

// Get all restaurants
Route::get('/restaurants', [App\Http\Controllers\Api\AuthController::class, 'getRestaurants']);

// Get all product from a restaurants
Route::get('/restaurant/{id}/products', [App\Http\Controllers\Api\ProductController::class, 'getProductByRestaurantId'])->middleware('auth:sanctum');

// Product API resource
Route::apiResource('/products', App\Http\Controllers\Api\ProductController::class)->middleware('auth:sanctum');

// Create order
Route::post('/order', [App\Http\Controllers\Api\OrderController::class, 'createOrder'])->middleware('auth:sanctum');

// Get order by user id
Route::get('/order/user', [App\Http\Controllers\Api\OrderController::class, 'orderHistory'])->middleware('auth:sanctum');

// Get order by restaurant id
Route::get('/order/restaurant', [App\Http\Controllers\Api\OrderController::class, 'getOrderByStatus'])->middleware('auth:sanctum');

// Get order by driver id
Route::get('/order/driver', [App\Http\Controllers\Api\OrderController::class, 'getOrderByStatusDriver'])->middleware('auth:sanctum');

// Update purchase status by user
Route::put('/order/user/update-status/{id}', [App\Http\Controllers\Api\OrderController::class, 'updatePurchaseStatus'])->middleware('auth:sanctum');

// Update order status by restaurant
Route::put('/order/restaurant/update-status/{id}', [App\Http\Controllers\Api\OrderController::class, 'updateOrderStatus'])->middleware('auth:sanctum');

// Update order status by driver
Route::put('/order/driver/update-status/{id}', [App\Http\Controllers\Api\OrderController::class, 'updateOrderStatusDriver'])->middleware('auth:sanctum');
