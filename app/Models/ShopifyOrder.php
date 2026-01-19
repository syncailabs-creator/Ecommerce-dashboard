<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopifyOrder extends Model
{
    protected $table = 'shopify_orders';

    protected $fillable = [
        'order_id',
        'name',
        'total_price',
        'created_at',
        'financial_status',
        'utm_term',
        'utm_content',
        'utm_campaign',
        'tags',
        'created_date',
        'updated_date',
        'deleted_date',
    ];
}
