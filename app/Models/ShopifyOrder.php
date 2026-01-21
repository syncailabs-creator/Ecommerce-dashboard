<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopifyOrder extends Model
{
    protected $table = 'shopify_orders';

    protected $guarded = [];

    public function products()
    {
        return $this->hasMany(ShopifyOrderProduct::class, 'shopify_order_id');
    }

}
