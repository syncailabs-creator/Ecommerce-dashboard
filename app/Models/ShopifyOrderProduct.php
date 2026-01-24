<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopifyOrderProduct extends Model
{
    protected $table = 'shopify_order_products';

    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(ShopifyOrder::class, 'shopify_order_id');
    }
}
