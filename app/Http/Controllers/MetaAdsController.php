<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MetaAdsAccount;
use App\Models\MetaAdsCampaign;
use App\Models\MetaAdsCampaignMaster;
use App\Models\MetaAdsSet;
use App\Models\MetaAdsSetMaster;
use App\Models\MetaAdsAd;
use App\Models\MetaAdsAdMaster;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaAdsController extends Controller
{
    public function fetchCampaigns()
    {
        set_time_limit(3000);
        try {
            $accounts = MetaAdsAccount::all();
            $accessToken = config('services.meta_ads.access_token');

            if (!$accessToken) {
                return response()->json(['success' => false, 'message' => 'Meta Access Token not found in .env'], 500);
            }

            foreach ($accounts as $account) {
                $url = "https://graph.facebook.com/v19.0/{$account->account_id}/insights";

                $response = Http::withoutVerifying()->get($url, [
                    'level' => 'campaign',
                    'fields' => 'campaign_id,campaign_name,reach,impressions,frequency,spend,cpm,cpc,ctr,inline_link_clicks,clicks,cost_per_landing_page_view,actions,cost_per_action_type,purchase_roas',
                    'access_token' => $accessToken,
                    'limit' => 500
                ]);

                if ($response->successful()) {
                    $data = $response->json()['data'] ?? [];

                    foreach ($data as $item) {
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

                        // Create or Update Campaign Master
                        $campaignMaster = MetaAdsCampaignMaster::updateOrCreate(
                            [
                                'campaign_id' => $item['campaign_id'],
                                'meta_ads_account_id' => $account->id
                            ],
                            [
                                'account_id' => $account->account_id,
                                'campaign_name' => $item['campaign_name'] ?? null,
                            ]
                        );

                        // Create or Update Campaign Insights (using master reference)
                        // Create or Update Campaign Insights (using master reference)
                        $today = now()->format('Y-m-d');
                        $existingCampaign = MetaAdsCampaign::where('meta_ads_campaign_master_id', $campaignMaster->id)
                            ->whereDate('created_at', $today)
                            ->first();

                        $campaignData = [
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
                        ];

                        if ($existingCampaign) {
                            $existingCampaign->update($campaignData);
                        } else {
                            $campaignData['meta_ads_campaign_master_id'] = $campaignMaster->id;
                            MetaAdsCampaign::create($campaignData);
                        }
                    }
                } else {
                    Log::error("Failed to fetch Meta Ads for account {$account->id} ({$account->account_id}): " . $response->body());
                }
            }

            return response()->json(['success' => true, 'message' => 'Meta Ads Campaigns fetched and stored successfully.']);

        } catch (\Exception $e) {
            Log::error("Error in fetchCampaigns: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function fetchAdSets()
    {
        set_time_limit(3000);
        try {
            $accounts = MetaAdsAccount::all();
            $accessToken = config('services.meta_ads.access_token');

            if (!$accessToken) {
                return response()->json(['success' => false, 'message' => 'Meta Access Token not found in .env'], 500);
            }

            foreach ($accounts as $account) {
                $url = "https://graph.facebook.com/v19.0/{$account->account_id}/insights";

                $response = Http::withoutVerifying()->get($url, [
                    'level' => 'adset',
                    'fields' => 'adset_id,adset_name,campaign_id,campaign_name,impressions,clicks,spend,reach,cpc,cpm,ctr,frequency,actions,cost_per_action_type',
                    'access_token' => $accessToken,
                    'limit' => 500
                ]);

                if ($response->successful()) {
                    $data = $response->json()['data'] ?? [];

                    foreach ($data as $item) {
                        // Conversions (actions -> purchase)
                        $conversions = 0;
                        if (isset($item['actions'])) {
                            foreach ($item['actions'] as $action) {
                                if ($action['action_type'] === 'purchase') {
                                    $conversions = $action['value'];
                                    break;
                                }
                            }
                        }

                        // Cost per conversion (cost_per_action_type -> purchase)
                        $costPerConversion = 0;
                        if (isset($item['cost_per_action_type'])) {
                            foreach ($item['cost_per_action_type'] as $cpa) {
                                if ($cpa['action_type'] === 'purchase') {
                                    $costPerConversion = $cpa['value'];
                                    break;
                                }
                            }
                        }

                        // Clicks (actions -> link_click)
                        $clicks = 0;
                        if (isset($item['actions'])) {
                            foreach ($item['actions'] as $action) {
                                if ($action['action_type'] === 'link_click') {
                                    $clicks = $action['value'];
                                    break;
                                }
                            }
                        }

                        // Create or Update AdSet Master
                        $adSetMaster = MetaAdsSetMaster::updateOrCreate(
                            [
                                'adset_id' => $item['adset_id'],
                                'meta_ads_account_id' => $account->id
                            ],
                            [
                                'account_id' => $account->account_id,
                                'adset_name' => $item['adset_name'] ?? null,
                                'campaign_id' => $item['campaign_id'] ?? null,
                                'campaign_name' => $item['campaign_name'] ?? null,
                            ]
                        );

                        // Create AdSet Insights (using master reference)
                        // Create or Update AdSet Insights (using master reference)
                        $today = now()->format('Y-m-d');
                        $existingAdSet = MetaAdsSet::where('meta_ads_set_master_id', $adSetMaster->id)
                            ->whereDate('created_at', $today)
                            ->first();

                        $adSetData = [
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
                        ];

                        if ($existingAdSet) {
                            $existingAdSet->update($adSetData);
                        } else {
                            $adSetData['meta_ads_set_master_id'] = $adSetMaster->id;
                            MetaAdsSet::create($adSetData);
                        }
                    }
                } else {
                    Log::error("Failed to fetch Meta AdSets for account {$account->id} ({$account->account_id}): " . $response->body());
                }
            }

            return response()->json(['success' => true, 'message' => 'Meta Ads Sets fetched and stored successfully.']);

        } catch (\Exception $e) {
            Log::error("Error in fetchAdSets: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function fetchAds()
    {
        set_time_limit(3000);
        try {
            $accounts = MetaAdsAccount::all();
            $accessToken = config('services.meta_ads.access_token');

            if (!$accessToken) {
                return response()->json(['success' => false, 'message' => 'Meta Access Token not found in .env'], 500);
            }

            foreach ($accounts as $account) {
                $url = "https://graph.facebook.com/v19.0/{$account->account_id}/insights";

                $response = Http::withoutVerifying()->get($url, [
                    'level' => 'ad',
                    'fields' => 'ad_id,ad_name,adset_id,adset_name,campaign_id,campaign_name,impressions,clicks,spend,reach,cpc,cpm,ctr,frequency,actions,cost_per_action_type',
                    'access_token' => $accessToken,
                    'limit' => 500
                ]);

                if ($response->successful()) {
                    $data = $response->json()['data'] ?? [];

                    foreach ($data as $item) {
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

                        // Create or Update Ad Master
                        $adMaster = MetaAdsAdMaster::updateOrCreate(
                            [
                                'ad_id' => $item['ad_id'],
                                'meta_ads_account_id' => $account->id
                            ],
                            [
                                'account_id' => $account->account_id,
                                'ad_name' => $item['ad_name'] ?? null,
                                'adset_id' => $item['adset_id'] ?? null,
                                'adset_name' => $item['adset_name'] ?? null,
                                'campaign_id' => $item['campaign_id'] ?? null,
                                'campaign_name' => $item['campaign_name'] ?? null,
                            ]
                        );

                        // Create Ad Insights (using master reference)
                        // Create or Update Ad Insights (using master reference)
                        $today = now()->format('Y-m-d');
                        $existingAd = MetaAdsAd::where('meta_ads_ad_master_id', $adMaster->id)
                            ->whereDate('created_at', $today)
                            ->first();

                        $adData = [
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
                        ];

                        if ($existingAd) {
                            $existingAd->update($adData);
                        } else {
                            $adData['meta_ads_ad_master_id'] = $adMaster->id;
                            MetaAdsAd::create($adData);
                        }
                    }
                } else {
                    Log::error("Failed to fetch Meta Ads (Ad level) for account {$account->id} ({$account->account_id}): " . $response->body());
                }
            }

            return response()->json(['success' => true, 'message' => 'Meta Ads Ads fetched and stored successfully.']);

        } catch (\Exception $e) {
            Log::error("Error in fetchAds: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function fetchPreviousCampaigns(Request $request)
    {
        set_time_limit(3000);
        try {
            $accounts = MetaAdsAccount::all();
            $accessToken = config('services.meta_ads.access_token');
            $date = $request->input('date', now()->subDay()->format('Y-m-d'));

            if (!$accessToken) {
                return response()->json(['success' => false, 'message' => 'Meta Access Token not found in .env'], 500);
            }

            foreach ($accounts as $account) {
                $url = "https://graph.facebook.com/v19.0/{$account->account_id}/insights";

                $response = Http::withoutVerifying()->get($url, [
                    'level' => 'campaign',
                    'fields' => 'campaign_id,campaign_name,reach,impressions,frequency,spend,cpm,cpc,ctr,inline_link_clicks,clicks,cost_per_landing_page_view,actions,cost_per_action_type,purchase_roas',
                    'access_token' => $accessToken,
                    'limit' => 500,
                    'time_range' => json_encode(['since' => $date, 'until' => $date])
                ]);

                if ($response->successful()) {
                    $data = $response->json()['data'] ?? [];

                    foreach ($data as $item) {
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

                        // Create or Update Campaign Master
                        $campaignMaster = MetaAdsCampaignMaster::updateOrCreate(
                            [
                                'campaign_id' => $item['campaign_id'],
                                'meta_ads_account_id' => $account->id
                            ],
                            [
                                'account_id' => $account->account_id,
                                'campaign_name' => $item['campaign_name'] ?? null,
                            ]
                        );

                        // Create or Update Campaign Insights (using master reference)
                        $existingCampaign = MetaAdsCampaign::where('meta_ads_campaign_master_id', $campaignMaster->id)
                            ->whereDate('created_at', $date)
                            ->first();

                        $campaignData = [
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
                        ];

                        if ($existingCampaign) {
                            $existingCampaign->update($campaignData);
                        } else {
                            $campaignData['meta_ads_campaign_master_id'] = $campaignMaster->id;
                            $campaignData['created_at'] = $date;
                            $campaignData['updated_at'] = $date;
                            MetaAdsCampaign::create($campaignData);
                        }
                    }
                } else {
                    Log::error("Failed to fetch Previous Meta Ads for account {$account->id} ({$account->account_id}): " . $response->body());
                }
            }

            return response()->json(['success' => true, 'message' => "Meta Ads Previous Campaigns fetched for {$date} and stored successfully."]);

        } catch (\Exception $e) {
            Log::error("Error in fetchPreviousCampaigns: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function fetchPreviousAdSets(Request $request)
    {
        set_time_limit(3000);
        try {
            $accounts = MetaAdsAccount::all();
            $accessToken = config('services.meta_ads.access_token');
            $date = $request->input('date', now()->subDay()->format('Y-m-d'));

            if (!$accessToken) {
                return response()->json(['success' => false, 'message' => 'Meta Access Token not found in .env'], 500);
            }

            foreach ($accounts as $account) {
                $url = "https://graph.facebook.com/v19.0/{$account->account_id}/insights";

                $response = Http::withoutVerifying()->get($url, [
                    'level' => 'adset',
                    'fields' => 'adset_id,adset_name,campaign_id,campaign_name,impressions,clicks,spend,reach,cpc,cpm,ctr,frequency,actions,cost_per_action_type',
                    'access_token' => $accessToken,
                    'limit' => 500,
                    'time_range' => json_encode(['since' => $date, 'until' => $date])
                ]);

                if ($response->successful()) {
                    $data = $response->json()['data'] ?? [];

                    foreach ($data as $item) {
                        // Conversions (actions -> purchase)
                        $conversions = 0;
                        if (isset($item['actions'])) {
                            foreach ($item['actions'] as $action) {
                                if ($action['action_type'] === 'purchase') {
                                    $conversions = $action['value'];
                                    break;
                                }
                            }
                        }

                        // Cost per conversion (cost_per_action_type -> purchase)
                        $costPerConversion = 0;
                        if (isset($item['cost_per_action_type'])) {
                            foreach ($item['cost_per_action_type'] as $cpa) {
                                if ($cpa['action_type'] === 'purchase') {
                                    $costPerConversion = $cpa['value'];
                                    break;
                                }
                            }
                        }

                        // Clicks (actions -> link_click)
                        $clicks = 0;
                        if (isset($item['actions'])) {
                            foreach ($item['actions'] as $action) {
                                if ($action['action_type'] === 'link_click') {
                                    $clicks = $action['value'];
                                    break;
                                }
                            }
                        }

                        // Create or Update AdSet Master
                        $adSetMaster = MetaAdsSetMaster::updateOrCreate(
                            [
                                'adset_id' => $item['adset_id'],
                                'meta_ads_account_id' => $account->id
                            ],
                            [
                                'account_id' => $account->account_id,
                                'adset_name' => $item['adset_name'] ?? null,
                                'campaign_id' => $item['campaign_id'] ?? null,
                                'campaign_name' => $item['campaign_name'] ?? null,
                            ]
                        );

                        // Create or Update AdSet Insights (using master reference)
                        $existingAdSet = MetaAdsSet::where('meta_ads_set_master_id', $adSetMaster->id)
                            ->whereDate('created_at', $date)
                            ->first();

                        $adSetData = [
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
                        ];

                        if ($existingAdSet) {
                            $existingAdSet->update($adSetData);
                        } else {
                            $adSetData['meta_ads_set_master_id'] = $adSetMaster->id;
                            $adSetData['created_at'] = $date;
                            $adSetData['updated_at'] = $date;
                            MetaAdsSet::create($adSetData);
                        }
                    }
                } else {
                    Log::error("Failed to fetch Previous Meta AdSets for account {$account->id} ({$account->account_id}): " . $response->body());
                }
            }

            return response()->json(['success' => true, 'message' => "Meta Ads Previous Sets fetched for {$date} and stored successfully."]);

        } catch (\Exception $e) {
            Log::error("Error in fetchPreviousAdSets: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function fetchPreviousAds(Request $request)
    {
        set_time_limit(3000);
        try {
            $accounts = MetaAdsAccount::all();
            $accessToken = config('services.meta_ads.access_token');
            $date = $request->input('date', now()->subDay()->format('Y-m-d'));

            if (!$accessToken) {
                return response()->json(['success' => false, 'message' => 'Meta Access Token not found in .env'], 500);
            }

            foreach ($accounts as $account) {
                $url = "https://graph.facebook.com/v19.0/{$account->account_id}/insights";

                $response = Http::withoutVerifying()->get($url, [
                    'level' => 'ad',
                    'fields' => 'ad_id,ad_name,adset_id,adset_name,campaign_id,campaign_name,impressions,clicks,spend,reach,cpc,cpm,ctr,frequency,actions,cost_per_action_type',
                    'access_token' => $accessToken,
                    'limit' => 500,
                    'time_range' => json_encode(['since' => $date, 'until' => $date])
                ]);

                if ($response->successful()) {
                    $data = $response->json()['data'] ?? [];

                    foreach ($data as $item) {
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

                        // Create or Update Ad Master
                        $adMaster = MetaAdsAdMaster::updateOrCreate(
                            [
                                'ad_id' => $item['ad_id'],
                                'meta_ads_account_id' => $account->id
                            ],
                            [
                                'account_id' => $account->account_id,
                                'ad_name' => $item['ad_name'] ?? null,
                                'adset_id' => $item['adset_id'] ?? null,
                                'adset_name' => $item['adset_name'] ?? null,
                                'campaign_id' => $item['campaign_id'] ?? null,
                                'campaign_name' => $item['campaign_name'] ?? null,
                            ]
                        );

                        // Create or Update Ad Insights (using master reference)
                        $existingAd = MetaAdsAd::where('meta_ads_ad_master_id', $adMaster->id)
                            ->whereDate('created_at', $date)
                            ->first();

                        $adData = [
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
                        ];

                        if ($existingAd) {
                            $existingAd->update($adData);
                        } else {
                            $adData['meta_ads_ad_master_id'] = $adMaster->id;
                            $adData['created_at'] = $date;
                            $adData['updated_at'] = $date;
                            MetaAdsAd::create($adData);
                        }
                    }
                } else {
                    Log::error("Failed to fetch Previous Meta Ads (Ad level) for account {$account->id} ({$account->account_id}): " . $response->body());
                }
            }

            return response()->json(['success' => true, 'message' => "Meta Ads Previous Ads fetched for {$date} and stored successfully."]);

        } catch (\Exception $e) {
            Log::error("Error in fetchPreviousAds: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
}
