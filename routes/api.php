<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/transactions/{id}/return', [\App\Http\Controllers\Api\TransactionController::class, 'returnItem']);
    
    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });

    // Rute KHUSUS OWNER
    Route::middleware('role:OWNER')->group(function () {
        Route::get('/owner-test', function () {
            return response()->json(['message' => 'Selamat datang, Owner!']);
        });
    });

    // Rute KHUSUS ADMIN
    Route::middleware('role:ADMIN')->group(function () {
        Route::get('/admin-test', function () {
            return response()->json(['message' => 'Selamat datang, Kasir!']);
        });
    });

        // Rute Produk
    Route::get('/products', [\App\Http\Controllers\Api\ProductController::class, 'index']);
    
    // Rute KHUSUS OWNER untuk CRUD Produk
    Route::middleware('role:OWNER')->group(function () {
        Route::get('/reports/dashboard', [\App\Http\Controllers\Api\ReportController::class, 'dashboard']);
        Route::post('/products', [\App\Http\Controllers\Api\ProductController::class, 'store']);
        Route::put('/products/{id}', [\App\Http\Controllers\Api\ProductController::class, 'update']);
        Route::delete('/products/{id}', [\App\Http\Controllers\Api\ProductController::class, 'destroy']);
        
        // Rute Khusus Owner untuk mengubah pengaturan
        Route::put('/store', [\App\Http\Controllers\Api\SettingController::class, 'update']);
        Route::post('/store/qris', [\App\Http\Controllers\Api\SettingController::class, 'uploadQris']);

        Route::get('/export/transactions', [\App\Http\Controllers\Api\ExportController::class, 'transactions']);
        Route::get('/export/products', [\App\Http\Controllers\Api\ExportController::class, 'products']);
        
        // Tambahkan rute ganti PIN di sini
        Route::put('/store/admin-pin', [\App\Http\Controllers\Api\SettingController::class, 'updateAdminPin']);
        Route::get('/audit-logs', [\App\Http\Controllers\Api\AuditLogController::class, 'index']);
    });

        // Rute Transaksi (Admin & Owner bisa akses)
    Route::post('/transactions', [\App\Http\Controllers\Api\TransactionController::class, 'store']);
    Route::get('/transactions', [\App\Http\Controllers\Api\TransactionController::class, 'index']);
    Route::post('/transactions/{id}/void', [\App\Http\Controllers\Api\TransactionController::class, 'void']);
    
    // Rute Hapus Permanen (KHUSUS OWNER)
    Route::middleware('role:OWNER')->group(function () {
        Route::delete('/transactions/{id}', [\App\Http\Controllers\Api\TransactionController::class, 'destroy']);
    });

});