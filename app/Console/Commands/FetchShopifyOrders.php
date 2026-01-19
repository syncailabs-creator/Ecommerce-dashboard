<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\ShopifyOrder;
use App\Models\ShopifyOrderProduct;
use Carbon\Carbon;

class FetchShopifyOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-shopify-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch orders from Shopify and store them in the database';

    /**
     * Execute the console command.
     */

    public function handle()
    {
        $this->info('Fetching orders from Shopify...');

        $shop = env('SHOPIFY_DOMAIN');
        $version = env('SHOPIFY_API_VERSION', '2025-01');
        $url = "https://{$shop}/admin/api/{$version}/orders.json?status=any&limit=250";
        $accessToken = env('SHOPIFY_ACCESS_TOKEN');

        if (!$shop || !$accessToken) {
            $this->error('Shopify initialization failed. Please set SHOPIFY_DOMAIN and SHOPIFY_ACCESS_TOKEN in .env');
            return;
        }

        do {
            try {
                $response = Http::withoutVerifying()->withHeaders([
                    'X-Shopify-Access-Token' => $accessToken,
                    'Content-Type' => 'application/json',
                ])->get($url);

                if ($response->successful()) {
                    $orders = $response->json()['orders'] ?? [];
                    $count = count($orders);
                    $this->info("Found $count orders in this batch.");

                    foreach ($orders as $orderData) {
                        $this->processOrder($orderData);
                    }

                    // Check for Link header for pagination
                    $linkHeader = $response->header('Link');
                    $url = $this->getNextPageUrl($linkHeader);

                } else {
                    $this->error('Failed to fetch orders: ' . $response->status() . ' - ' . $response->body());
                    $url = null; // Stop loop on error
                }
            } catch (\Exception $e) {
                file_put_contents('error_log.txt', $e->getMessage());
                $this->error('Exception: ' . $e->getMessage());
                $url = null;
            }
        } while ($url);

        $this->info('All orders processed successfully.');
    }

    private function getNextPageUrl($linkHeader)
    {
        if (!$linkHeader) {
            return null;
        }

        // Link header format: <url>; rel="next"
        if (preg_match('/<([^>]+)>;\s*rel="next"/', $linkHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function processOrder($data)
    {
        // Extract UTM parameters from note_attributes
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
        // Store or update ShopifyOrder
        // Using order_id as the unique identifier
        $order = ShopifyOrder::updateOrCreate(
            ['order_id' => $data['id']],
            [
                'name' => $data['name'],
                'total_price' => $data['total_price'],
                'financial_status' => $data['financial_status'],
                'utm_term' => $utmTerm,
                'utm_content' => $utmContent,
                'utm_campaign' => $utmCampaign,
                'tags' => $data['tags'],
                'order_date' => isset($data['created_at']) ? Carbon::parse($data['created_at']) : null,
            ]
        );

        // Process Line Items
        if (isset($data['line_items']) && is_array($data['line_items'])) {
            // Option: Create items only if they don't exist?
            // Actually, simplified approach: we can just add them.
            // But to avoid duplicates on re-run, we should probably check.
            // However, shopify_order_products table structure doesn't seem to have a unique line_item_id based on previous file view.
            // It has `shopify_order_id`, `name`, `price`.
            // If we run this multiple times, we might duplicate products if we are not careful.
            // Since we don't have a line_item_id in the migration/model (assumed from previous steps),
            // we will delete existing products for this order and re-insert them to ensure sync.

            ShopifyOrderProduct::where('shopify_order_id', $order->id)->delete();

            foreach ($data['line_items'] as $item) {
                ShopifyOrderProduct::create([
                    'shopify_order_id' => $order->id, // Linking to our local ID
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'created_at' => now(), // Or from order?
                ]);
            }
        }
        dd($data);
    }
}
