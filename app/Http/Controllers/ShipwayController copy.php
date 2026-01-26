<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShipwayController extends Controller
{
    public function handleWebhook(Request $request)
    {
        Log::info('Shipway Webhook Received:', $request->all());

        return response()->json([
            'status' => 'success',
            'data' => $request->all()
        ]);
    }
}
