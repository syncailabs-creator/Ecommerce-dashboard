<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

use App\Models\ShipwayOrder;
use App\Models\ShipwayOrderStatus;
use App\Models\ShipwayOrderProduct;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ShipwayController extends Controller
{
    public function deliveryReport(Request $request)
    {
        if ($request->ajax()) {
            $statuses = ShipwayOrder::STATUSES;
            
            $selects = [
                DB::raw('DATE(shopify_orders.order_date) as date'),
                DB::raw('COUNT(*) as total_count'),
            ];

            foreach ($statuses as $status) {
                $safeStatus = addslashes($status);
                // Use a safe column name (lowercase, underscores)
                $colName = str_replace(' ', '_', strtolower($status));
                $selects[] = DB::raw("SUM(CASE WHEN shipway_orders.shipment_status_name = '$safeStatus' THEN 1 ELSE 0 END) as {$colName}_count");
            }

            // Start from shipway_orders, join shopify for date and tags
            $subQuery = DB::table('shipway_orders')
                ->leftJoin('shopify_orders', 'shipway_orders.order_id', '=', 'shopify_orders.name')
                ->select($selects);

            if ($request->has('filter_order_type') && $request->filter_order_type != 'All') {
                if ($request->filter_order_type == 'Abandoned') {
                    $subQuery->where('shopify_orders.tags', 'like', '%abandoned%');
                } elseif ($request->filter_order_type == 'Normal') {
                    $subQuery->where(function($q) {
                        $q->whereNull('shopify_orders.tags')
                          ->orWhere('shopify_orders.tags', 'not like', '%abandoned%');
                    });
                }
            }

            if ($request->has('date_filter') && $request->date_filter != 'All') {
                switch ($request->date_filter) {
                    case 'Today':
                        $subQuery->whereDate('shopify_orders.order_date', Carbon::today());
                        break;
                    case 'Yesterday':
                        $subQuery->whereDate('shopify_orders.order_date', Carbon::yesterday());
                        break;
                    case 'This Week':
                         $subQuery->whereBetween('shopify_orders.order_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                         break;
                    case 'Last Week':
                         $subQuery->whereBetween('shopify_orders.order_date', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
                         break;
                    case 'Last 7 Days':
                        $subQuery->whereDate('shopify_orders.order_date', '>=', Carbon::now()->subDays(7));
                        break;
                    case 'This Month':
                        $subQuery->whereMonth('shopify_orders.order_date', Carbon::now()->month)
                                 ->whereYear('shopify_orders.order_date', Carbon::now()->year);
                        break;
                    case 'Last Month':
                        $subQuery->whereMonth('shopify_orders.order_date', Carbon::now()->subMonth()->month)
                                 ->whereYear('shopify_orders.order_date', Carbon::now()->subMonth()->year);
                        break;
                    case 'This Year':
                         $subQuery->whereYear('shopify_orders.order_date', Carbon::now()->year);
                         break;
                     case 'Custom':
                         if($request->has('start_date') && $request->has('end_date')) {
                             $subQuery->whereBetween('shopify_orders.order_date', [$request->start_date, $request->end_date]);
                         }
                         break;
                }
            }

            $subQuery->whereNotNull('shopify_orders.order_date')
                ->groupBy(DB::raw('DATE(shopify_orders.order_date)'));

            $query = DB::query()->fromSub($subQuery, 'daily_status_report');

            $dataTable = DataTables::of($query)
                ->filterColumn('date', function($query, $keyword) {
                    $query->whereRaw("DATE_FORMAT(date, '%d-%m-%Y') like ?", ["%{$keyword}%"]);
                })
                ->addIndexColumn()
                ->editColumn('date', function($row){
                    return Carbon::parse($row->date)->format('d-m-Y');
                })
                ->addColumn('total_percentage', function($row){
                    return '100%';
                });

            foreach ($statuses as $status) {
                // Match the column name construction above
                $colNameBase = str_replace(' ', '_', strtolower($status));
                $countCol = "{$colNameBase}_count";
                
                $dataTable->addColumn("{$colNameBase}_percentage", function($row) use ($countCol) {
                    if ($row->total_count == 0) return '0%';
                    return round(($row->$countCol / $row->total_count) * 100, 2) . '%';
                });
            }

            return $dataTable->make(true);
        }

        return view('reports.delivery_report', ['statuses' => ShipwayOrder::STATUSES]);
    }

    public function handleWebhook(Request $request)
    {
        Log::info('Shipway Webhook Received:', $request->all());

        $orderId = $request->input('order_id');
        
        $order = ShipwayOrder::where('order_id', $orderId)->first();

        if ($order) {
            Log::info("Order found in Shipway orders: " . $orderId);
            
            $orderStatus = $request->input('new_current_status');

            $existingStatus = ShipwayOrderStatus::where('shipway_order_id', $orderId)
                ->where('status', $orderStatus)
                ->first();

            if ($existingStatus) {
               
                $existingStatus->update([
                    'updated_datetime' =>  $request->input('status_time'),
                    'updated_at' => now()
                ]);
            } else {                
                ShipwayOrderStatus::create([
                    'shipway_order_id' => $orderId,
                    'status' => $orderStatus,
                    'datetime' =>  $request->input('status_time'),
                    'updated_datetime' =>  $request->input('status_time'),
                ]);
            }
            
        } else {
            Log::info("Order not found in Shipway orders: " . $orderId);          
        }

        return response()->json([
            'status' => 'success'
        ]);
    }

    public function fetchOrders()
    {
        $username = config('services.shipway.username');
        $password = config('services.shipway.password');

        if (!$username || !$password) {
            return response()->json(['error' => 'Shipway credentials missing'], 500);
        }

        $startDate = Carbon::today()->format('Y-m-d');
        $endDate = Carbon::today()->format('Y-m-d');

        $page = 1;
        $hasMore = true;
        $processedCount = 0;

        do {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(120)
                    ->withBasicAuth($username, $password)
                    ->get("https://app.shipway.com/api/getorders", [
                        'page' => $page,
                        'date_from' => $startDate,
                        'date_to' => $endDate,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['success']) && $data['success'] == 1 && !empty($data['message']) && is_array($data['message'])) {
                        
                        $orders = $data['message'];
                        $count = count($orders);
                        
                        foreach ($orders as $orderData) {
                            $this->processOrder($orderData);
                            $processedCount++;
                        }

                        if ($count < 100) {
                            $hasMore = false;
                        } else {
                            $page++;
                        }

                    } else {
                        $hasMore = false;
                    }

                } else {
                    return response()->json(['error' => 'Failed to fetch orders', 'details' => $response->body()], 500);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => 'Exception occurred', 'message' => $e->getMessage()], 500);
            }

        } while ($hasMore);

        return response()->json(['success' => true, 'message' => "Fetched and processed $processedCount orders for today."]);
    }

    private function processOrder($data)
    {
        if (!isset($data['order_id'])) {
             return;
        }

        $orderDate = isset($data['order_date']) ? Carbon::parse($data['order_date']) : null;
        
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
    }
}
