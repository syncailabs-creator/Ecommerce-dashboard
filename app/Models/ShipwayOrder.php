<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShipwayOrder extends Model
{
    use SoftDeletes;

    protected $table = 'shipway_orders';

    protected $guarded = [];

    public const STATUSES = [
        'DELIVERED',
        'IN TRANSIT',
        'LOST',
        'OUT FOR DELIVERY',
        'OUT FOR PICKUP',
        'PICKED UP',
        'REACHED AT DESTINATION HUB',
        'RETURN DELIVERED',
        'RTO DELIVERED',
        'RTO IN TRANSIT',
        'RTO NDR',
        'SHIPPED',
        'UNDELIVERED',
    ];

    public function products()
    {
        return $this->hasMany(ShipwayOrderProduct::class, 'shipway_order_id');
    }

    public function statuses()
    {
        return $this->hasMany(ShipwayOrderStatus::class, 'shipway_order_id');
    }

    public function shopifyOrder()
    {
        return $this->belongsTo(ShopifyOrder::class, 'order_id', 'name');
    }
}
