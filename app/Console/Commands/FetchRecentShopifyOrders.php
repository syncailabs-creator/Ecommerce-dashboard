<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ShopifyOrder;
use App\Models\ShopifyOrderProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class FetchRecentShopifyOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-recent-shopify-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches recent Shopify orders and updates local database matching by name.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        set_time_limit(3000);

        $shop = config('services.shopify.domain');
        $version = config('services.shopify.api_version', '2025-01');
        $accessToken = config('services.shopify.access_token');
        
        // Sync last 15 minutes
        $createdAtMin = Carbon::now()->subMinutes(15)->toIso8601String();
        
        $url = "https://{$shop}/admin/api/{$version}/orders.json?status=any&limit=250&created_at_min={$createdAtMin}";

        if (!$shop || !$accessToken) {
            $this->error('Shopify credentials missing.');
            return;
        }

        $this->info("Fetching recent orders from Shopify (Last 15 mins)...");

        try {
            $totalProcessed = 0;
            do {
                $response = Http::withoutVerifying()->withHeaders([
                    'X-Shopify-Access-Token' => $accessToken,
                    'Content-Type' => 'application/json',
                ])->get($url);

                if ($response->successful()) {
                    $orders = $response->json()['orders'] ?? [];
                    $count = count($orders);
                    $totalProcessed += $count;
                    
                    if ($count > 0) {
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

                            // Match by NAME as requested
                            // "if SELECT * FROM `shopify_orders` ORDER BY `name` ASC having name then don't create new just update it"
                            $cleanName = str_replace('#', '', $data['name']);

                            $order = ShopifyOrder::updateOrCreate(
                                ['name' => $cleanName], // Condition to match existing record
                                [
                                    'order_id' => $data['id'], // Update other fields
                                    'total_price' => $data['total_price'],
                                    'financial_status' => $data['financial_status'],
                                    'utm_term' => $utmTerm,
                                    'utm_content' => $utmContent,
                                    'utm_campaign' => $utmCampaign,
                                    'tags' => $data['tags'],
                                    'order_date' => isset($data['created_at']) ? Carbon::parse($data['created_at']) : null,
                                ]
                            );

                            // Handle Line Items
                            if (isset($data['line_items']) && is_array($data['line_items'])) {
                                ShopifyOrderProduct::where('shopify_order_id', $order->id)->delete();
                    
                                foreach ($data['line_items'] as $item) {
                                    ShopifyOrderProduct::create([
                                        'shopify_order_id' => $order->id,
                                        'name' => $item['name'],
                                        'price' => $item['price'],
                                        'created_at' => now(),
                                    ]);
                                }
                            }
                        }
                    }

                    $this->info("Processed batch of {$count} orders.");

                    $linkHeader = $response->header('Link');
                    if ($linkHeader && preg_match('/<([^>]+)>;\s*rel="next"/', $linkHeader, $matches)) {
                        $url = $matches[1];
                    } else {
                        $url = null;
                    }

                } else {
                    $this->error('Shopify API Error: ' . $response->status());
                    return;
                }
            } while ($url);

            $this->info("Synced successfully. Processed $totalProcessed orders.");

        } catch (\Exception $e) {
            $this->error('Exception: ' . $e->getMessage());
        }
    }
}
