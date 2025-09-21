<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\SuppliersController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\PurchasesController;
use App\Http\Controllers\StockMovementsController;
use App\Http\Controllers\SaleReturnsController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Product
    Route::get('products', [ProductsController::class, 'index']);
    Route::post('products', [ProductsController::class, 'store']);
    Route::get('products/{id}', [ProductsController::class, 'show']);
    Route::put('products/{id}', [ProductsController::class, 'update']);
    Route::delete('products/{id}', [ProductsController::class, 'destroy']);

    // Categories routes
    Route::post('categories', [CategoriesController::class, 'store']);
    Route::get('categories', [CategoriesController::class, 'index']);
    Route::get('categories/{id}', [CategoriesController::class, 'show']);
    Route::put('categories/{id}', [CategoriesController::class, 'update']);
    Route::delete('categories/{id}', [CategoriesController::class, 'destroy']);

    // Suppliers routes
    Route::get('suppliers', [SuppliersController::class, 'index']);
    Route::post('suppliers', [SuppliersController::class, 'store']);
    Route::get('suppliers/{id}', [SuppliersController::class, 'show']);
    Route::put('suppliers/{id}', [SuppliersController::class, 'update']);
    Route::delete('suppliers/{id}', [SuppliersController::class, 'destroy']);

    // Customers routes
    Route::get('customers', [CustomersController::class, 'index']);
    Route::post('customers', [CustomersController::class, 'store']);
    Route::get('customers/{id}', [CustomersController::class, 'show']);
    Route::put('customers/{id}', [CustomersController::class, 'update']);
    Route::delete('customers/{id}', [CustomersController::class, 'destroy']);

    // Sales routes
    Route::get('sales', [SalesController::class, 'index']);
    Route::post('sales', [SalesController::class, 'store']);
    Route::get('sales/{id}', [SalesController::class, 'show']);
    Route::put('sales/{id}', [SalesController::class, 'update']);
    Route::delete('sales/{id}', [SalesController::class, 'destroy']);

    // Purchases routes
    Route::get('purchases', [PurchasesController::class, 'index']);
    Route::post('purchases', [PurchasesController::class, 'store']);
    Route::get('purchases/{id}', [PurchasesController::class, 'show']);
    Route::put('purchases/{id}', [PurchasesController::class, 'update']);
    Route::delete('purchases/{id}', [PurchasesController::class, 'destroy']);

    // Stock Movements routes
    Route::get('stock-movements', [StockMovementsController::class, 'index']);
    Route::post('stock-movements', [StockMovementsController::class, 'store']);
    Route::get('stock-movements/{id}', [StockMovementsController::class, 'show']);
    Route::put('stock-movements/{id}', [StockMovementsController::class, 'update']);
    Route::delete('stock-movements/{id}', [StockMovementsController::class, 'destroy']);
    Route::get('stock-movements/product/{productId}', [StockMovementsController::class, 'getByProduct']);

    // Sale Returns routes
    Route::get('sale-returns', [SaleReturnsController::class, 'index']);
    Route::post('sale-returns', [SaleReturnsController::class, 'store']);
    Route::get('sale-returns/{id}', [SaleReturnsController::class, 'show']);
    Route::put('sale-returns/{id}', [SaleReturnsController::class, 'update']);
    Route::delete('sale-returns/{id}', [SaleReturnsController::class, 'destroy']);
});
