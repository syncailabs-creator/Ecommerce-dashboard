<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShipwayController;

Route::get('clear', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('config:cache');
    Artisan::call('view:clear');
    Artisan::call('storage:link');
    return "Cleared!";
});
Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/shopify-orders/fetch-recent', [\App\Http\Controllers\ShopifyOrderController::class, 'fetchRecent'])->name('shopify_orders.fetch_recent');
Route::get('/shipway/fetch-orders', [ShipwayController::class, 'fetchOrders'])->name('shipway.fetch_orders');
Route::get('/meta-ads/fetch-campaigns', [\App\Http\Controllers\MetaAdsController::class, 'fetchCampaigns'])->name('meta_ads.fetch_campaigns');
Route::get('/meta-ads/fetch-adsets', [\App\Http\Controllers\MetaAdsController::class, 'fetchAdSets'])->name('meta_ads.fetch_adsets');
Route::get('/meta-ads/fetch-ads', [\App\Http\Controllers\MetaAdsController::class, 'fetchAds'])->name('meta_ads.fetch_ads');



Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/shopify-orders', [\App\Http\Controllers\ShopifyOrderController::class, 'index'])->name('shopify_orders.index');
    Route::post('/shopify-orders/sync', [\App\Http\Controllers\ShopifyOrderController::class, 'sync'])->name('shopify_orders.sync');

    Route::get('/shopify-orders/export', [\App\Http\Controllers\ShopifyOrderController::class, 'export'])->name('shopify_orders.export');
    Route::get('/shopify-orders/{id}', [\App\Http\Controllers\ShopifyOrderController::class, 'show'])->name('shopify_orders.show');
    Route::get('/reports/payment-type', [\App\Http\Controllers\PaymentTypeReportController::class, 'index'])->name('reports.payment_type');

});

require __DIR__.'/auth.php';

Route::any('/shipway/webhook', [ShipwayController::class, 'handleWebhook']);

