<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MetaAdsAccount;
use App\Models\MetaAdsCampaign;
use App\Models\MetaAdsSet;
use App\Models\MetaAdsAd;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaAdsController extends Controller
{
    public function fetchCampaigns()
    {
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

                        MetaAdsCampaign::create(
                            [
                                'campaign_id' => $item['campaign_id'],
                                'meta_ads_account_id' => $account->id,
                                'account_id' => $account->account_id,
                                'campaign_name' => $item['campaign_name'] ?? null,
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
                            ]
                        );
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

                        MetaAdsSet::updateOrCreate(
                            [
                                'adset_id' => $item['adset_id'],
                                'meta_ads_account_id' => $account->id
                            ],
                            [
                                'account_id' => $account->account_id,
                                'adset_name' => $item['adset_name'] ?? null,
                                'campaign_id' => $item['campaign_id'] ?? null,
                                'campaign_name' => $item['campaign_name'] ?? null,
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
                            ]
                        );
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

                        MetaAdsAd::updateOrCreate(
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
                            ]
                        );
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
}
