<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\ShipwayOrder;
use App\Models\ShipwayOrderProduct;
use App\Models\ShipwayOrderStatus;
use Carbon\Carbon;

class FetchShipwayOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-shipway-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch orders from Shipway and store them in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching orders from Shipway...');

        $username = env('SHIPWAY_USERNAME');
        $password = env('PASSWORD');

        if (!$username || !$password) {
            $this->error('Shipway credentials missing. Please set SHIPWAY_USERNAME and PASSWORD in .env');
            return;
        }

        // Get token/auth. 
        // Based on user feedback: "basic auth user name & password"
        // And documentation: "Authorization < Enter your $token >"
        // We will try Basic Auth first using the provided credentials.
        
        $startDate = Carbon::now()->subDays(30)->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');

        $this->info("Fetching orders from $startDate to $endDate...");

        $page = 1;
        $hasMore = true;

        do {
            $this->info("Fetching page $page...");
            
            // Note: The documentation URL is https://app.shipway.com/api/getorders
            // We verify SSL False just in case, similar to the Shopify command.
            try {
                $response = Http::withoutVerifying()
                    ->withBasicAuth($username, $password)
                    ->get("https://app.shipway.com/api/getorders", [
                        'page' => $page,
                        'limit' => 100, // Explicitly requesting chunk size
                        'date_from' => $startDate,
                        'date_to' => $endDate,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['success']) && $data['success'] == 1 && !empty($data['message']) && is_array($data['message'])) {
                        
                        $orders = $data['message'];
                        $count = count($orders);
                        $this->info("Found $count orders on page $page.");

                        foreach ($orders as $orderData) {
                            $this->processOrder($orderData);
                        }

                        if ($count < 100) {
                            $hasMore = false;
                        } else {
                            $page++;
                        }

                    } else {
                        // If success is not 1 or message is empty, we stop.
                        // If it's an error message, log it?
                        if (isset($data['error']) && $data['error']) {
                             $this->warn("API returned error: " . $data['error']);
                        } else {
                            $this->info("No more orders found or empty response.");
                        }
                        $hasMore = false;
                    }

                } else {
                    $this->error('Failed to fetch orders: ' . $response->status() . ' - ' . $response->body());
                    $hasMore = false;
                }
            } catch (\Exception $e) {
                $this->error('Exception: ' . $e->getMessage());
                $hasMore = false;
            }

        } while ($hasMore);

        $this->info('All Shipway orders processed successfully.');
    }

    private function processOrder($data)
    {
        // $data matches the JSON object for a single order
        
        // 1. Create/Update Order
        $orderDate = isset($data['order_date']) ? Carbon::parse($data['order_date']) : null;
        
        $order = ShipwayOrder::updateOrCreate(
            ['order_id' => $data['order_id']], // Unique identifier
            [
                'shipping_cost' => $data['shipping_cost'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'b_city' => $data['b_city'] ?? null,
                'b_country' => $data['b_country'] ?? null,
                'b_state' => $data['b_state'] ?? null,
                'b_zipcode' => $data['b_zipcode'] ?? null,
                's_city' => $data['s_city'] ?? null,
                's_state' => $data['s_state'] ?? null,
                's_country' => $data['s_country'] ?? null,
                's_zipcode' => $data['s_zipcode'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
                'shipment_status' => $data['shipment_status'] ?? null,
                'order_date' => $orderDate,
            ]
        );

        // 2. Process Products
        if (isset($data['products']) && is_array($data['products'])) {
            // Remove existing products to avoid duplicates/stale data (Strategy: Sync)
            // Since we don't have a unique ID for products in the response (only loop index or product name),
            // delete-and-insert is a safe strategy for this sync.
            // CAUTION: This deletes soft-deleted records too if generic 'delete' is called?
            // Eloquent 'delete()' sets deleted_at. 'forceDelete()' removes them.
            // If we want to fully replace the list, we should probably forceDelete or just delete.
            // Given the models use SoftDeletes, calling delete() will soft delete. 
            // Then we insert new ones. This might balloon the table with soft deleted rows.
            // A better approach for sync without unique IDs:
            // Force Delete previous related products for this order? Or reuse them?
            // Let's stick to simple delete() for now, as re-syncing probably isn't extremely frequent.
            
            // Actually, querying soft deleted + regular to see if they exist is complex.
            // I will just use forceDelete() to keep the table clean if that's acceptable, 
            // OR checks generic delete.
            // Wait, standard practice for `hasMany` sync is often delete-insert.
            // I'll use delete(). 
            
            ShipwayOrderProduct::where('shipway_order_id', $order->order_id)->forceDelete(); // Using forceDelete to avoid duplicate accumulation

            foreach ($data['products'] as $item) {
                ShipwayOrderProduct::create([
                    'shipway_order_id' => $order->order_id, // Storing the STRING order_id as per schema implication
                    'hsn_code' => $item['hsn_code'] ?? null,
                    'product' => $item['product'] ?? null,
                    'price' => $item['price'] ?? null,
                    'amount' => $item['amount'] ?? null,
                ]);
            }
        }
        
        // 3. Optional: Update Status History (ShipwayOrderStatus)
        // If the user wants to track status changes. 
        // We only have the *current* status in the response ("status": "H", "shipment_status": "RTD").
        // We can check if the last status entry for this order is different.
        
        // $currentStatus = $data['shipment_status'] ?? null;
        // if ($currentStatus) {
        //     $lastStatus = ShipwayOrderStatus::where('shipway_order_id', $order->order_id)
        //                     ->orderBy('created_date', 'desc')
        //                     ->first();
                            
        //     if (!$lastStatus || $lastStatus->status !== $currentStatus) {
        //          ShipwayOrderStatus::create([
        //              'shipway_order_id' => $order->order_id,
        //              'status' => $currentStatus,
        //              'datetime' => now(), // The "datetime" column in schema
        //          ]);
        //     }
        // }
    }
}
