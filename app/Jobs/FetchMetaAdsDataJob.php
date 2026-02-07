<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\MetaAdsAccount;
use App\Models\MetaAdsCampaign;
use App\Models\MetaAdsCampaignMaster;
use App\Models\MetaAdsSet;
use App\Models\MetaAdsSetMaster;
use App\Models\MetaAdsAd;
use App\Models\MetaAdsAdMaster;
use Carbon\Carbon;

class FetchMetaAdsDataJob implements ShouldQueue
{
    use Queueable;

    protected $since;
    protected $until;
    protected $level; // 'campaign', 'adset', 'ad'

    /**
     * Create a new job instance.
     *
     * @param string $since YYYY-MM-DD
     * @param string $until YYYY-MM-DD
     * @param string $level
     */
    public function __construct($since, $until, $level)
    {
        $this->since = $since;
        $this->until = $until;
        $this->level = $level;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        set_time_limit(300); // 5 mins

        $accessToken = config('services.meta_ads.access_token');
        if (!$accessToken) {
            Log::error("FetchMetaAdsDataJob: Access Token missing");
            return;
        }

        // $accounts = MetaAdsAccount::all();
        $accounts = MetaAdsAccount::where('account_id', 'act_1554426265636855')->get();

        foreach ($accounts as $account) {
            $url = "https://graph.facebook.com/v24.0/{$account->account_id}/insights";
            
            // Define fields based on level
            $fields = '';
            if ($this->level == 'campaign') {
                $fields = 'campaign_id,campaign_name,reach,impressions,frequency,spend,cpm,cpc,ctr,inline_link_clicks,clicks,cost_per_landing_page_view,actions,cost_per_action_type,purchase_roas';
            } elseif ($this->level == 'adset') {
                $fields = 'adset_id,adset_name,campaign_id,campaign_name,impressions,clicks,spend,reach,cpc,cpm,ctr,frequency,actions,cost_per_action_type';
            } elseif ($this->level == 'ad') {
                $fields = 'ad_id,ad_name,adset_id,adset_name,campaign_id,campaign_name,impressions,clicks,spend,reach,cpc,cpm,ctr,frequency,actions,cost_per_action_type';
            }

            // Time range structure as requested
            // API expects encoded JSON for time_range parameter
            $timeRange = json_encode([
                'since' => $this->since,
                'until' => $this->until
            ]);

            try {
                $response = Http::withoutVerifying()->get($url, [
                    'level' => $this->level,
                    'fields' => $fields,
                    'access_token' => $accessToken,
                    'limit' => 500,
                    'time_range' => $timeRange
                ]);

                if ($response->successful()) {
                    $data = $response->json()['data'] ?? [];
                    if (!empty($data)) {
                        $this->processData($data, $account);
                    }
                } else {
                    Log::error("Meta API error for account {$account->id} level {$this->level}: " . $response->body());
                }
            } catch (\Exception $e) {
                Log::error("FetchMetaAdsDataJob Exception: " . $e->getMessage());
            }
        }
    }

    private function processData($data, $account)
    {
        // Use 'since' date as the created_at/date anchor for daily reports
        $reportDate = Carbon::parse($this->since);

        foreach ($data as $item) {
            // Processing logic extracted and adapted from Controller
            
            $conversions = 0;
            if (isset($item['actions'])) {
                foreach ($item['actions'] as $action) {
                    if ($action['action_type'] === 'purchase') {
                        $conversions = $action['value'];
                        break;
                    }
                }
            }

            $costPerConversion = 0;
            if (isset($item['cost_per_action_type'])) {
                foreach ($item['cost_per_action_type'] as $cpa) {
                    if ($cpa['action_type'] === 'purchase') {
                        $costPerConversion = $cpa['value'];
                        break;
                    }
                }
            }

            $clicks = 0;
            if (isset($item['actions'])) {
                foreach ($item['actions'] as $action) {
                    if ($action['action_type'] === 'link_click') {
                        $clicks = $action['value'];
                        break;
                    }
                }
            }

            if ($this->level == 'campaign') {
                $campaignMaster = MetaAdsCampaignMaster::updateOrCreate(
                    ['campaign_id' => $item['campaign_id'], 'meta_ads_account_id' => $account->id],
                    ['account_id' => $account->account_id, 'campaign_name' => $item['campaign_name'] ?? null]
                );

                MetaAdsCampaign::create([
                    'meta_ads_campaign_master_id' => $campaignMaster->id,
                    'impressions' => $item['impressions'] ?? 0,
                    'spend' => $item['spend'] ?? 0,
                    'reach' => $item['reach'] ?? 0,
                    'cpc' => $item['cpc'] ?? 0,
                    'cpm' => $item['cpm'] ?? 0,
                    'ctr' => $item['ctr'] ?? 0,
                    'frequency' => $item['frequency'] ?? 0,
                    'clicks' => $clicks,
                    'conversions' => $conversions,
                    'cost_per_conversion' => $costPerConversion,
                    'created_at' => $reportDate, // Store report date
                    'updated_at' => now(), 
                ]);

            } elseif ($this->level == 'adset') {
                $adSetMaster = MetaAdsSetMaster::updateOrCreate(
                    ['adset_id' => $item['adset_id'], 'meta_ads_account_id' => $account->id],
                    [
                        'account_id' => $account->account_id, 
                        'adset_name' => $item['adset_name'] ?? null,
                        'campaign_id' => $item['campaign_id'] ?? null,
                        'campaign_name' => $item['campaign_name'] ?? null
                    ]
                );

                MetaAdsSet::create([
                    'meta_ads_set_master_id' => $adSetMaster->id,
                    'impressions' => $item['impressions'] ?? 0,
                    'spend' => $item['spend'] ?? 0,
                    'reach' => $item['reach'] ?? 0,
                    'cpc' => $item['cpc'] ?? 0,
                    'cpm' => $item['cpm'] ?? 0,
                    'ctr' => $item['ctr'] ?? 0,
                    'frequency' => $item['frequency'] ?? 0,
                    'clicks' => $clicks,
                    'conversions' => $conversions,
                    'cost_per_conversion' => $costPerConversion,
                    'created_at' => $reportDate, 
                    'updated_at' => now(), 
                ]);

            } elseif ($this->level == 'ad') {
                $adMaster = MetaAdsAdMaster::updateOrCreate(
                    ['ad_id' => $item['ad_id'], 'meta_ads_account_id' => $account->id],
                    [
                        'account_id' => $account->account_id,
                        'ad_name' => $item['ad_name'] ?? null,
                        'adset_id' => $item['adset_id'] ?? null,
                        'adset_name' => $item['adset_name'] ?? null,
                        'campaign_id' => $item['campaign_id'] ?? null,
                        'campaign_name' => $item['campaign_name'] ?? null,
                    ]
                );

                MetaAdsAd::create([
                    'meta_ads_ad_master_id' => $adMaster->id,
                    'impressions' => $item['impressions'] ?? 0,
                    'spend' => $item['spend'] ?? 0,
                    'reach' => $item['reach'] ?? 0,
                    'cpc' => $item['cpc'] ?? 0,
                    'cpm' => $item['cpm'] ?? 0,
                    'ctr' => $item['ctr'] ?? 0,
                    'frequency' => $item['frequency'] ?? 0,
                    'clicks' => $clicks,
                    'conversions' => $conversions,
                    'cost_per_conversion' => $costPerConversion,
                    'created_at' => $reportDate,
                    'updated_at' => now(), 
                ]);
            }
        }
    }
}
