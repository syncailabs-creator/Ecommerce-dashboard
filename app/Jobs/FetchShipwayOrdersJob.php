<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\ShipwayOrder;
use App\Models\ShipwayOrderProduct;
use App\Models\ShipwayOrderStatus;
use Carbon\Carbon;

class FetchShipwayOrdersJob implements ShouldQueue
{
    use Queueable;

    protected $date;

    /**
     * Create a new job instance.
     *
     * @param string $date Y-m-d format
     */
    public function __construct($date)
    {
        $this->date = $date;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        set_time_limit(600); // 10 minutes limit per job
        ini_set('memory_limit', '512M'); // Increase memory limit

        $username = config('services.shipway.username');
        $password = config('services.shipway.password');

        if (!$username || !$password) {
            Log::error('FetchShipwayOrdersJob: Shipway credentials missing.');
            return;
        }

        $page = 1;
        $hasMore = true;
        $totalProcessed = 0;
        
        do {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(120)
                    ->withBasicAuth($username, $password)
                    ->get("https://app.shipway.com/api/getorders", [
                        'page' => $page,
                        'date_from' => $this->date,
                        'date_to' => $this->date,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['success']) && $data['success'] == 1 && !empty($data['message']) && is_array($data['message'])) {
                        
                        $orders = $data['message'];
                        $count = count($orders);
                        
                        // Process orders in batches of 50 to reduce memory usage
                        $this->processBatch($orders);
                        
                        $totalProcessed += $count;

                        if ($count < 100) {
                            $hasMore = false;
                        } else {
                            $page++;
                        }

                    } else {
                        $hasMore = false;
                        if (isset($data['error']) && $data['error']) {
                             Log::warning("FetchShipwayOrdersJob ({$this->date}): API returned error: " . $data['error']);
                        }
                    }

                } else {
                    Log::error("FetchShipwayOrdersJob ({$this->date}): Failed to fetch orders: " . $response->status());
                    $hasMore = false;
                }
            } catch (\Exception $e) {
                Log::error("FetchShipwayOrdersJob ({$this->date}): Exception: " . $e->getMessage());
                $hasMore = false;
            }

        } while ($hasMore);
        
        Log::info("FetchShipwayOrdersJob ({$this->date}): Completed. Total orders processed: {$totalProcessed}");
    }

    /**
     * Process orders in batches to optimize performance
     */
    private function processBatch($orders)
    {
        // Process in chunks of 50 orders
        $chunks = array_chunk($orders, 50);
        
        foreach ($chunks as $chunk) {
            DB::transaction(function () use ($chunk) {
                foreach ($chunk as $orderData) {
                    $this->processOrder($orderData);
                }
            });
            
            // Clear memory after each chunk
            gc_collect_cycles();
        }
    }

    private function processOrder($data)
    {
        if (!isset($data['order_id'])) {
             return;
        }

        $orderDate = isset($data['order_date']) ? Carbon::parse($data['order_date']) : null;
        
        // Update or create the main order (no nested transaction needed)
        $order = ShipwayOrder::updateOrCreate(
            ['order_id' => $data['order_id']], 
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
                'shipment_status_name' => isset($data['shipment_status_name']) ? strtoupper(str_replace(' ', '_', $data['shipment_status_name'])) : null,
                'order_date' => $orderDate,
            ]
        );

        // Bulk process products
        if (isset($data['products']) && is_array($data['products']) && !empty($data['products'])) {
            // Delete existing products in one query
            ShipwayOrderProduct::where('shipway_order_id', $order->order_id)->delete();

            // Prepare bulk insert data
            $productsToInsert = [];
            $now = now();
            
            foreach ($data['products'] as $item) {
                $productsToInsert[] = [
                    'shipway_order_id' => $order->order_id,
                    'hsn_code' => $item['hsn_code'] ?? null,
                    'product' => $item['product'] ?? null,
                    'price' => $item['price'] ?? null,
                    'amount' => $item['amount'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            
            // Bulk insert products
            if (!empty($productsToInsert)) {
                ShipwayOrderProduct::insert($productsToInsert);
            }
        }

        // Bulk process shipment statuses
        if (isset($data['shipment_status_scan']) && is_array($data['shipment_status_scan']) && !empty($data['shipment_status_scan'])) {
            // Get existing statuses for this order in one query
            $existingStatuses = ShipwayOrderStatus::where('shipway_order_id', $order->order_id)
                ->pluck('datetime', 'status')
                ->toArray();
            
            $statusesToInsert = [];
            $statusesToUpdate = [];
            $now = now();
            
            foreach ($data['shipment_status_scan'] as $item) {
                $orderStatus = isset($item['status']) ? strtoupper(str_replace(' ', '_', $item['status'])) : null;
                
                if ($orderStatus) {
                    if (isset($existingStatuses[$orderStatus])) {
                        // Status exists, prepare for update
                        $statusesToUpdate[] = [
                            'status' => $orderStatus,
                            'updated_datetime' => $item['datetime'] ?? null,
                        ];
                    } else {
                        // New status, prepare for insert
                        $statusesToInsert[] = [
                            'shipway_order_id' => $order->order_id,
                            'status' => $orderStatus,
                            'datetime' => $item['datetime'] ?? null,
                            'updated_datetime' => $item['datetime'] ?? null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }
            
            // Bulk insert new statuses
            if (!empty($statusesToInsert)) {
                ShipwayOrderStatus::insert($statusesToInsert);
            }
            
            // Update existing statuses
            foreach ($statusesToUpdate as $statusData) {
                ShipwayOrderStatus::where('shipway_order_id', $order->order_id)
                    ->where('status', $statusData['status'])
                    ->update([
                        'updated_datetime' => $statusData['updated_datetime'],
                        'updated_at' => $now
                    ]);
            }
        }
    }
}
