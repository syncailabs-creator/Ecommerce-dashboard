<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/shopify-orders', [\App\Http\Controllers\ShopifyOrderController::class, 'index'])->name('shopify_orders.index');
    Route::post('/shopify-orders/sync', [\App\Http\Controllers\ShopifyOrderController::class, 'sync'])->name('shopify_orders.sync');
    Route::get('/shopify-orders/export', [\App\Http\Controllers\ShopifyOrderController::class, 'export'])->name('shopify_orders.export');
    Route::get('/shopify-orders/{id}', [\App\Http\Controllers\ShopifyOrderController::class, 'show'])->name('shopify_orders.show');

});

require __DIR__.'/auth.php';
