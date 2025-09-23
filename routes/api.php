<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\SaleReturnController;
use App\Http\Controllers\Api\WarehouseTransferController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
Route::post('/password/reset', [AuthController::class, 'resetPassword']);


Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::patch('/profile', [AuthController::class, 'updateProfile']);
    Route::patch('/password/change', [AuthController::class, 'changePassword']);
    Route::post('/token/refresh', [AuthController::class, 'refreshToken']);

    // Sales Management
    Route::apiResource('sales', SaleController::class);
    Route::get('sales/{sale}/receipt', [SaleController::class, 'receipt']);

    // Sale Returns with Approval System
    Route::apiResource('sale-returns', SaleReturnController::class);
    Route::patch('sale-returns/{saleReturn}/approve', [SaleReturnController::class, 'approve'])
         ->middleware('permission:approve_sale_returns');
    Route::patch('sale-returns/{saleReturn}/reject', [SaleReturnController::class, 'reject'])
         ->middleware('permission:approve_sale_returns');

    // Purchase Management
    Route::apiResource('purchases', PurchaseController::class);
    Route::patch('purchases/{purchase}/receive', [PurchaseController::class, 'receive']);

    // Purchase Returns with Approval System
    Route::apiResource('purchase-returns', PurchaseReturnController::class);
    Route::patch('purchase-returns/{purchaseReturn}/approve', [PurchaseReturnController::class, 'approve'])
         ->middleware('permission:approve_purchase_returns');
    Route::patch('purchase-returns/{purchaseReturn}/reject', [PurchaseReturnController::class, 'reject'])
         ->middleware('permission:approve_purchase_returns');

    // Warehouse Transfers
    Route::apiResource('warehouse-transfers', WarehouseTransferController::class);
    Route::patch('warehouse-transfers/{transfer}/complete', [WarehouseTransferController::class, 'complete']);

    // Stock Management
    Route::get('stocks', [StockController::class, 'index']);
    Route::get('stocks/warehouse/{warehouse}', [StockController::class, 'byWarehouse']);
    Route::post('stocks/adjust', [StockController::class, 'adjust'])
         ->middleware('permission:adjust_stock');
    Route::get('stocks/movements', [StockController::class, 'movements']);

    // Master Data
    Route::apiResource('products', ProductController::class);
    Route::apiResource('warehouses', WarehouseController::class)->middleware('permission:manage_warehouses');
    Route::apiResource('payment-methods', PaymentMethodController::class);

    // Reports
    Route::get('reports/sales', [ReportController::class, 'sales']);
    Route::get('reports/inventory', [ReportController::class, 'inventory']);
    Route::get('reports/stock-movements', [ReportController::class, 'stockMovements']);
});
