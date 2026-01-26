<?php

namespace App\Http\Controllers;

use App\Models\ShopifyOrder;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ShopifyOrderController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = ShopifyOrder::query()->orderBy('order_date', 'desc');
            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('name', function($row){
                     return '<a href="'.route('shopify_orders.show', $row->id).'" class="text-indigo-600 hover:text-indigo-900 font-medium transition duration-150">'.$row->name.'</a>';
                })
                ->editColumn('order_date', function($row){
                    return $row->order_date ? \Carbon\Carbon::parse($row->order_date)->format('Y-m-d H:i:s') : '';
                })
                ->rawColumns(['name'])
                ->make(true);
        }

        return view('shopify_orders.index');
    }

    public function show($id)
    {
        $order = ShopifyOrder::with('products')->findOrFail($id);
        return view('shopify_orders.show', compact('order'));
    }

    public function export(Request $request)
    {
        $query = ShopifyOrder::with('products');

        // Manual Filtering based on DataTable column inputs
        // Note: DataTables sends 'columns' array with 'search' => ['value' => '...']
        // Columns index mapping based on index.blade.php:
        // 1: order_id
        // 2: name
        // 3: total_price
        // 4: financial_status
        // 5: utm_term
        // 6: order_date

        if ($request->has('columns')) {
            $columns = $request->get('columns');

            if (!empty($columns[1]['search']['value'])) {
                $query->where('order_id', 'like', '%' . $columns[1]['search']['value'] . '%');
            }
            if (!empty($columns[2]['search']['value'])) {
                $query->where('name', 'like', '%' . $columns[2]['search']['value'] . '%');
            }
            if (!empty($columns[3]['search']['value'])) {
                $query->where('total_price', 'like', '%' . $columns[3]['search']['value'] . '%');
            }
            if (!empty($columns[4]['search']['value'])) {
                // Exact match for status ideally, but typically starts with logic in datatables
                $val = $columns[4]['search']['value'];
                // Clean regex chars if any came from datatable
                $val = str_replace(['^', '$'], '', $val);
                if($val) $query->where('financial_status', $val);
            }
            if (!empty($columns[5]['search']['value'])) {
                $query->where('utm_term', 'like', '%' . $columns[5]['search']['value'] . '%');
            }
            if (!empty($columns[6]['search']['value'])) {
                $query->where('order_date', 'like', '%' . $columns[6]['search']['value'] . '%');
            }
        }

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            
            // CSV Headers - Matching 'show.blade.php' view columns only
            fputcsv($handle, [
                'Order Name', 
                'Shopify Order ID',
                'Financial Status', 
                'Order Date', 
                'Total Price', 
                'UTM Term', 
                'UTM Content', 
                'UTM Campaign', 
                'Tags',
                'Product Name', 
                'Product ID', 
                'Product Price'
            ]);

            $query->chunk(100, function ($orders) use ($handle) {
                foreach ($orders as $order) {
                    // Common Order Data
                    $orderData = [
                        $order->name,
                        $order->order_id,
                        $order->financial_status,
                        $order->order_date, // Format if needed, e.g. \Carbon\Carbon::parse($order->order_date)->format('F d, Y h:i A')
                        $order->total_price,
                        $order->utm_term,
                        $order->utm_content,
                        $order->utm_campaign,
                        $order->tags
                    ];

                    if ($order->products->isEmpty()) {
                        // Export order even if no products (edge case)
                        fputcsv($handle, array_merge($orderData, ['', '', '']));
                    } else {
                        foreach ($order->products as $product) {
                            fputcsv($handle, array_merge($orderData, [
                                $product->name,
                                $product->id,
                                $product->price
                            ]));
                        }
                    }
                }
            });

            fclose($handle);
        }, 'shopify_orders_export_' . date('Y-m-d_H-i-s') . '.csv');
    }

    public function sync(Request $request) {
        // Raw debug to verify hit
        file_put_contents(public_path('debug_sync_log.txt'), "Sync method hit at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        
        set_time_limit(300); // Increase timeout for sync

        $shop = env('SHOPIFY_DOMAIN');
        $version = env('SHOPIFY_API_VERSION', '2025-01');
        $accessToken = env('SHOPIFY_ACCESS_TOKEN');
        
        // Sync last 2 days only
        $updatedAtMin = \Carbon\Carbon::now()->subDays(2)->toIso8601String();
        
        $url = "https://{$shop}/admin/api/{$version}/orders.json?status=any&limit=250&updated_at_min={$updatedAtMin}";

        if (!$shop || !$accessToken) {
            return response()->json(['success' => false, 'message' => 'Shopify credentials missing.'], 500);
        }

        try {
            do {
                $response = \Illuminate\Support\Facades\Http::withoutVerifying()->withHeaders([
                    'X-Shopify-Access-Token' => $accessToken,
                    'Content-Type' => 'application/json',
                ])->get($url);

                if ($response->successful()) {
                    $orders = $response->json()['orders'] ?? [];
                    
                    foreach ($orders as $data) {
                        // Logic similar to FetchShopifyOrders command
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

                        $order = \App\Models\ShopifyOrder::updateOrCreate(
                            ['order_id' => $data['id']],
                            [
                                'name' => $data['name'],
                                'total_price' => $data['total_price'],
                                'financial_status' => $data['financial_status'],
                                'utm_term' => $utmTerm,
                                'utm_content' => $utmContent,
                                'utm_campaign' => $utmCampaign,
                                'tags' => $data['tags'],
                                'order_date' => isset($data['created_at']) ? \Carbon\Carbon::parse($data['created_at']) : null,
                            ]
                        );

                        // Line Items
                        if (isset($data['line_items']) && is_array($data['line_items'])) {
                            \App\Models\ShopifyOrderProduct::where('shopify_order_id', $order->id)->delete();
                
                            foreach ($data['line_items'] as $item) {
                                \App\Models\ShopifyOrderProduct::create([
                                    'shopify_order_id' => $order->id,
                                    'name' => $item['name'],
                                    'price' => $item['price'],
                                    'created_at' => now(),
                                ]);
                            }
                        }
                    }

                    // Pagination
                    $linkHeader = $response->header('Link');
                    if ($linkHeader && preg_match('/<([^>]+)>;\s*rel="next"/', $linkHeader, $matches)) {
                        $url = $matches[1];
                    } else {
                        $url = null;
                    }

                } else {
                    return response()->json(['success' => false, 'message' => 'Shopify API Error: ' . $response->status()], 500);
                }
            } while ($url);

            return response()->json(['success' => true, 'message' => 'Synced successfully (Last 2 Days).']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Exception: ' . $e->getMessage()], 500);
        }
    }

    public function fetchRecent(Request $request) {
        set_time_limit(300);

        $shop = config('services.shopify.domain');
        $version = config('services.shopify.api_version', '2025-01');
        $accessToken = config('services.shopify.access_token');
        
        // Sync last 15 minutes
        $createdAtMin = \Carbon\Carbon::now()->subMinutes(15)->toIso8601String();
        
        $url = "https://{$shop}/admin/api/{$version}/orders.json?status=any&limit=250&created_at_min={$createdAtMin}";

        if (!$shop || !$accessToken) {
            return response()->json(['success' => false, 'message' => 'Shopify credentials missing.'], 500);
        }

        try {
            $totalProcessed = 0;
            do {
                $response = \Illuminate\Support\Facades\Http::withoutVerifying()->withHeaders([
                    'X-Shopify-Access-Token' => $accessToken,
                    'Content-Type' => 'application/json',
                ])->get($url);

                if ($response->successful()) {
                    $orders = $response->json()['orders'] ?? [];
                    $totalProcessed += count($orders);
                    
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

                        $order = \App\Models\ShopifyOrder::updateOrCreate(
                            ['order_id' => $data['id']],
                            [
                                'name' => $data['name'],
                                'total_price' => $data['total_price'],
                                'financial_status' => $data['financial_status'],
                                'utm_term' => $utmTerm,
                                'utm_content' => $utmContent,
                                'utm_campaign' => $utmCampaign,
                                'tags' => $data['tags'],
                                'order_date' => isset($data['created_at']) ? \Carbon\Carbon::parse($data['created_at']) : null,
                            ]
                        );

                        if (isset($data['line_items']) && is_array($data['line_items'])) {
                            \App\Models\ShopifyOrderProduct::where('shopify_order_id', $order->id)->delete();
                
                            foreach ($data['line_items'] as $item) {
                                \App\Models\ShopifyOrderProduct::create([
                                    'shopify_order_id' => $order->id,
                                    'name' => $item['name'],
                                    'price' => $item['price'],
                                    'created_at' => now(),
                                ]);
                            }
                        }
                    }

                    $linkHeader = $response->header('Link');
                    if ($linkHeader && preg_match('/<([^>]+)>;\s*rel="next"/', $linkHeader, $matches)) {
                        $url = $matches[1];
                    } else {
                        $url = null;
                    }

                } else {
                    return response()->json(['success' => false, 'message' => 'Shopify API Error: ' . $response->status()], 500);
                }
            } while ($url);

            return response()->json(['success' => true, 'message' => "Synced successfully. Processed $totalProcessed orders (Last 15 Mins)."]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Exception: ' . $e->getMessage()], 500);
        }
    }

}
