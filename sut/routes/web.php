<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProductApiController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

// --- public -------------------------------------------------------------------------------
Route::get('/', fn () => redirect('/verify'));

Route::get('/verify', [PublicController::class, 'verifyForm']);
Route::post('/verify', [PublicController::class, 'verify']);

Route::get('/{prefix}/{gtin}', [PublicController::class, 'product'])
    ->where('prefix', '01')
    ->where('gtin', '[0-9]+');

// The JSON API is public and must be declared before the management routes.
Route::get('/products.json', [ProductApiController::class, 'index']);
Route::get('/products/{gtin}.json', [ProductApiController::class, 'show'])->where('gtin', '[0-9]+');

// --- authentication -----------------------------------------------------------------------
Route::get('/login', [AuthController::class, 'show']);
Route::post('/login', [AuthController::class, 'store']);
Route::post('/logout', [AuthController::class, 'destroy']);

// --- management (admin only) ---------------------------------------------------------------
Route::middleware('admin')->group(function () {
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::get('/companies/deactivated', [CompanyController::class, 'deactivated']);
    Route::get('/companies/new', [CompanyController::class, 'create']);
    Route::post('/companies', [CompanyController::class, 'store']);
    Route::get('/companies/{company}', [CompanyController::class, 'show']);
    Route::get('/companies/{company}/edit', [CompanyController::class, 'edit']);
    Route::put('/companies/{company}', [CompanyController::class, 'update']);
    Route::post('/companies/{company}/deactivate', [CompanyController::class, 'deactivate']);
    Route::post('/companies/{company}/reactivate', [CompanyController::class, 'reactivate']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/new', [ProductController::class, 'create']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::get('/products/{product}/edit', [ProductController::class, 'edit']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::post('/products/{product}/hide', [ProductController::class, 'hide']);
    Route::post('/products/{product}/unhide', [ProductController::class, 'unhide']);
    Route::post('/products/{product}/remove-image', [ProductController::class, 'removeImageAction']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
});
