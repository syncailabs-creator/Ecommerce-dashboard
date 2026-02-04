<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\ShopifyOrder;
use App\Models\ShopifyOrderProduct;
use Illuminate\Support\Facades\Log;

class FetchShopifyOrdersJob implements ShouldQueue
{
    use Queueable;

    protected $startDate;
    protected $endDate;

    /**
     * Create a new job instance.
     *
     * @param string $startDate ISO 8601 string
     * @param string $endDate ISO 8601 string
     */
    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Increase time limit for this job
        set_time_limit(300);

        $shop = config('services.shopify.domain');
        $version = config('services.shopify.api_version', '2025-01');
        $accessToken = config('services.shopify.access_token');

        if (!$shop || !$accessToken) {
            Log::error('FetchShopifyOrdersJob: Shopify credentials missing.');
            return;
        }

        $url = "https://{$shop}/admin/api/{$version}/orders.json?status=any&limit=250&created_at_min={$this->startDate}&created_at_max={$this->endDate}";

        try {
            do {
                $response = Http::withoutVerifying()->withHeaders([
                    'X-Shopify-Access-Token' => $accessToken,
                    'Content-Type' => 'application/json',
                ])->get($url);

                if ($response->successful()) {
                    $orders = $response->json()['orders'] ?? [];
                    $count = count($orders);
                    
                    if ($count > 0) {
                        $upsertData = [];
                        $shopifyOrderIds = [];
                        $now = now();

                        foreach ($orders as $data) {
                            $utmTerm = null;
                            $utmContent = null;
                            $utmCampaign = null;
                    
                            if (isset($data['note_attributes']) && is_array($data['note_attributes'])) {
                                foreach ($data['note_attributes'] as $attr) {
                                    if ($attr['name'] === 'utm_term') {
                                        $utmTerm = $attr['value'];
                                    } elseif ($attr['name'] === 'utm_content') {
                                        $utmContent = $attr['value'];
                                    } elseif ($attr['name'] === 'utm_campaign') {
                                        $utmCampaign = $attr['value'];
                                    }
                                }
                            }

                            $upsertData[] = [
                                'order_id' => $data['id'],
                                'name' => str_replace('#', '', $data['name']),
                                'total_price' => $data['total_price'],
                                'financial_status' => $data['financial_status'],
                                'utm_term' => $utmTerm,
                                'utm_content' => $utmContent,
                                'utm_campaign' => $utmCampaign,
                                'tags' => $data['tags'],
                                'order_date' => isset($data['created_at']) ? \Carbon\Carbon::parse($data['created_at']) : null,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                            $shopifyOrderIds[] = $data['id'];
                        }

                        DB::transaction(function() use ($upsertData, $shopifyOrderIds, $orders, $now) {
                            // Bulk Upsert Orders
                            ShopifyOrder::upsert(
                                $upsertData,
                                ['order_id'],
                                ['name', 'total_price', 'financial_status', 'utm_term', 'utm_content', 'utm_campaign', 'tags', 'order_date', 'updated_at']
                            );

                            // Fetch Local IDs mapping
                            $savedOrders = ShopifyOrder::whereIn('order_id', $shopifyOrderIds)
                                ->pluck('id', 'order_id');

                            $productsData = [];
                            $localIdsToClean = $savedOrders->values()->toArray();

                            foreach ($orders as $data) {
                                if (!isset($data['line_items']) || !is_array($data['line_items'])) continue;
                                
                                $remoteId = $data['id'];
                                if (!isset($savedOrders[$remoteId])) continue;
                                
                                $localId = $savedOrders[$remoteId];

                                foreach ($data['line_items'] as $item) {
                                    $productsData[] = [
                                        'shopify_order_id' => $localId,
                                        'name' => $item['name'],
                                        'price' => $item['price'],
                                        'created_at' => $now,
                                        'updated_at' => $now,
                                    ];
                                }
                            }

                            if (!empty($localIdsToClean)) {
                                ShopifyOrderProduct::whereIn('shopify_order_id', $localIdsToClean)->delete();
                            }

                            if (!empty($productsData)) {
                                foreach (array_chunk($productsData, 500) as $chunk) {
                                    ShopifyOrderProduct::insert($chunk);
                                }
                            }
                        });
                    }

                    $linkHeader = $response->header('Link');
                    if ($linkHeader && preg_match('/<([^>]+)>;\s*rel="next"/', $linkHeader, $matches)) {
                        $url = $matches[1];
                    } else {
                        $url = null;
                    }

                } else {
                    Log::error('FetchShopifyOrdersJob: API Error: ' . $response->status() . ' Body: ' . $response->body());
                    $url = null;
                }
            } while ($url);

        } catch (\Exception $e) {
            Log::error('FetchShopifyOrdersJob Exception: ' . $e->getMessage());
            // Optionally release the job back to queue?
            // $this->release(60); 
        }
    }
}
