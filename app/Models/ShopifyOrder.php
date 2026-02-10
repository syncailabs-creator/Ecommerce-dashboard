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

    public function shipwayOrder()
    {
        return $this->hasOne(ShipwayOrder::class, 'order_id', 'name');
    }

    public function metaCampaign()
    {
        return $this->belongsTo(MetaAdsCampaignMaster::class, 'utm_campaign', 'campaign_id');
    }

    // public function metaAdSet()
    // {
    //     return $this->belongsTo(MetaAdsSetMaster::class, 'utm_term', 'adset_id');
    // }

    // public function metaAd()
    // {
    //     return $this->belongsTo(MetaAdsAdMaster::class, 'utm_content', 'ad_id');
    // }
    
    public function metaAdSet()
    {
        return $this->belongsTo(MetaAdsSetMaster::class, 'utm_content', 'adset_id');
    }

    public function metaAd()
    {
        return $this->belongsTo(MetaAdsAdMaster::class, 'utm_term', 'ad_id');
    }

}
