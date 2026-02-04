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
        set_time_limit(300); // 5 minutes limit per job

        $username = config('services.shipway.username');
        $password = config('services.shipway.password');

        if (!$username || !$password) {
            Log::error('FetchShipwayOrdersJob: Shipway credentials missing.');
            return;
        }

        $page = 1;
        $hasMore = true;
        
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
                        
                        foreach ($orders as $orderData) {
                            $this->processOrder($orderData);
                        }

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
                    Log::error("FetchShipwayOrdersJob ({$this->date}): Failed to fetch orders: " . $response->status() . ' - ' . $response->body());
                    $hasMore = false;
                }
            } catch (\Exception $e) {
                Log::error("FetchShipwayOrdersJob ({$this->date}): Exception: " . $e->getMessage());
                // Retry logic is built into ShouldQueue if configured, otherwise we break to avoid infinite loops on hard errors
                $hasMore = false;
            }

        } while ($hasMore);
    }

    private function processOrder($data)
    {
        if (!isset($data['order_id'])) {
             return;
        }

        $orderDate = isset($data['order_date']) ? Carbon::parse($data['order_date']) : null;
        
        DB::transaction(function () use ($data, $orderDate) {
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
                    'shipment_status_name' => $data['shipment_status_name'] ?? null,
                    'order_date' => $orderDate,
                ]
            );

            if (isset($data['products']) && is_array($data['products'])) {
                 ShipwayOrderProduct::where('shipway_order_id', $order->order_id)->forceDelete();

                foreach ($data['products'] as $item) {
                    ShipwayOrderProduct::create([
                        'shipway_order_id' => $order->order_id,
                        'hsn_code' => $item['hsn_code'] ?? null,
                        'product' => $item['product'] ?? null,
                        'price' => $item['price'] ?? null,
                        'amount' => $item['amount'] ?? null,
                    ]);
                }
            }
        });
    }
}
