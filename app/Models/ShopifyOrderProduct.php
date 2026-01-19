<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopifyOrderProduct extends Model
{
    protected $table = 'shopify_order_products';

    // protected $fillable = [
    //     'shopify_order_id',
    //     'name',
    //     'price',
    //     'created_at',
    //     'updated_at',
    //     'deleted_at',
    // ];

    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(ShopifyOrder::class, 'shopify_order_id');
    }
}
