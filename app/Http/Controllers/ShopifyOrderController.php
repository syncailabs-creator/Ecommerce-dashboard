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

}
