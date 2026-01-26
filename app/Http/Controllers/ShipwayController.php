<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

use App\Models\ShipwayOrder;
use App\Models\ShipwayOrderStatus;

class ShipwayController extends Controller
{
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
}
