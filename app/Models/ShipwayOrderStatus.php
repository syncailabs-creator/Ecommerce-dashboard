<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShipwayOrderStatus extends Model
{
    use SoftDeletes;

    protected $table = 'shipway_orders_status';

    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(ShipwayOrder::class, 'shipway_order_id');
    }
}
