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
            $data = ShopifyOrder::query();
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

}
