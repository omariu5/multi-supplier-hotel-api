<?php

use App\Http\Controllers\HotelSearchController;
use App\Http\Controllers\MockSupplierController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/hotels/search', [HotelSearchController::class, 'search']);

// Mock supplier routes
Route::get('/mock/supplier-a', [MockSupplierController::class, 'supplierA']);
Route::get('/mock/supplier-b', [MockSupplierController::class, 'supplierB']);
Route::get('/mock/supplier-c', [MockSupplierController::class, 'supplierC']);
Route::get('/mock/supplier-d', [MockSupplierController::class, 'supplierD']);
