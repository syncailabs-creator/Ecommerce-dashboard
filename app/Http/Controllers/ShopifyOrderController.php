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
                ->editColumn('order_date', function($row){
                    return $row->order_date ? \Carbon\Carbon::parse($row->order_date)->format('Y-m-d H:i:s') : '';
                })
                ->make(true);
        }

        return view('shopify_orders.index');
    }
}
