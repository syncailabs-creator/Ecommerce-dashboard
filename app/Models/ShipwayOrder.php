<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShipwayOrder extends Model
{
    use SoftDeletes;

    protected $table = 'shipway_orders';

    protected $guarded = [];

    public function products()
    {
        return $this->hasMany(ShipwayOrderProduct::class, 'shipway_order_id');
    }

    public function statuses()
    {
        return $this->hasMany(ShipwayOrderStatus::class, 'shipway_order_id');
    }
}
