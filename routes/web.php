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

Route::get('job-fire', function () {
    Artisan::call('queue:work --stop-when-empty');
    return "Jobs Processed!";
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

Route::get('/meta-ads/fetch-previous-campaigns', [\App\Http\Controllers\MetaAdsController::class, 'fetchPreviousCampaigns'])->name('meta_ads.fetch_previous_campaigns');
Route::get('/meta-ads/fetch-previous-adsets', [\App\Http\Controllers\MetaAdsController::class, 'fetchPreviousAdSets'])->name('meta_ads.fetch_previous_adsets');
Route::get('/meta-ads/fetch-previous-ads', [\App\Http\Controllers\MetaAdsController::class, 'fetchPreviousAds'])->name('meta_ads.fetch_previous_ads');
    
Route::get('/meta-ads/fetch-accounts', [\App\Http\Controllers\MetaAdsController::class, 'fetchAdAccounts'])->name('meta_ads.fetch_accounts');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::any('/shopify-orders', [\App\Http\Controllers\ShopifyOrderController::class, 'index'])->name('shopify_orders.index');
    Route::post('/shopify-orders/sync', [\App\Http\Controllers\ShopifyOrderController::class, 'sync'])->name('shopify_orders.sync');

    Route::get('/shopify-orders/export', [\App\Http\Controllers\ShopifyOrderController::class, 'export'])->name('shopify_orders.export');
    Route::get('/shopify-orders/{id}', [\App\Http\Controllers\ShopifyOrderController::class, 'show'])->name('shopify_orders.show');
    Route::any('/reports/payment-type', [\App\Http\Controllers\PaymentTypeReportController::class, 'index'])->name('reports.payment_type');
    Route::any('/reports/meta-performance', [\App\Http\Controllers\MetaPerformanceReportController::class, 'index'])->name('reports.meta_performance');
    Route::any('/reports/delivery-classification', [\App\Http\Controllers\DeliveryClassificationReportController::class, 'index'])->name('reports.delivery_classification');
    Route::any('/reports/delivery-report', [ShipwayController::class, 'deliveryReport'])->name('shipway.reports.delivery');

});

require __DIR__.'/auth.php';

Route::any('/shipway/webhook', [ShipwayController::class, 'handleWebhook']);
