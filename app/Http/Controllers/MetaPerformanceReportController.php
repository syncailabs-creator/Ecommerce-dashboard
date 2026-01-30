<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class MetaPerformanceReportController extends Controller
{
    public function campaign(Request $request)
    {
        if ($request->ajax()) {
            $subQuery = DB::table('shopify_orders as o')
                ->leftJoin('meta_ads_campaign_masters as c', 'o.utm_campaign', '=', 'c.campaign_id')
                ->select(
                    DB::raw("COALESCE(c.campaign_name, 'Direct / Unknown') as campaign_name"),
                    DB::raw('COUNT(*) as total_count'),
                    DB::raw("SUM(CASE WHEN o.financial_status = 'paid' THEN 1 ELSE 0 END) as paid_count"),
                    DB::raw("SUM(CASE WHEN o.financial_status = 'partially_paid' THEN 1 ELSE 0 END) as partially_paid_count"),
                    DB::raw("SUM(CASE WHEN o.financial_status = 'pending' THEN 1 ELSE 0 END) as pending_count")
                )
                // ->whereNotNull('o.order_date') 
                ->groupBy(DB::raw("COALESCE(c.campaign_name, 'Direct / Unknown')"));

            $query = DB::query()->fromSub($subQuery, 'campaign_report');

            return DataTables::of($query)
                ->addIndexColumn()
                 // Assuming user might want to filter by campaign name
                ->filterColumn('campaign_name', function($query, $keyword) {
                    $query->where('campaign_name', 'like', "%{$keyword}%");
                })
                ->addColumn('total_percentage', function($row){
                    return '100%';
                })
                ->addColumn('paid_percentage', function($row){
                    if ($row->total_count == 0) return '0%';
                    return round(($row->paid_count / $row->total_count) * 100, 2) . '%';
                })
                ->addColumn('partially_paid_percentage', function($row){
                    if ($row->total_count == 0) return '0%';
                    return round(($row->partially_paid_count / $row->total_count) * 100, 2) . '%';
                })
                ->addColumn('pending_percentage', function($row){
                    if ($row->total_count == 0) return '0%';
                    return round(($row->pending_count / $row->total_count) * 100, 2) . '%';
                })
                ->make(true);
        }

        return view('reports.meta_performance_campaign');
    }

    public function adSet(Request $request)
    {
        if ($request->ajax()) {
            $subQuery = DB::table('shopify_orders as o')
                ->leftJoin('meta_ads_set_masters as s', 'o.utm_term', '=', 's.adset_id')
                ->select(
                    DB::raw("COALESCE(s.adset_name, 'Direct / Unknown') as adset_name"),
                    DB::raw('COUNT(*) as total_count'),
                    DB::raw("SUM(CASE WHEN o.financial_status = 'paid' THEN 1 ELSE 0 END) as paid_count"),
                    DB::raw("SUM(CASE WHEN o.financial_status = 'partially_paid' THEN 1 ELSE 0 END) as partially_paid_count"),
                    DB::raw("SUM(CASE WHEN o.financial_status = 'pending' THEN 1 ELSE 0 END) as pending_count")
                )
                // ->whereNotNull('o.order_date') 
                ->groupBy(DB::raw("COALESCE(s.adset_name, 'Direct / Unknown')"));

            $query = DB::query()->fromSub($subQuery, 'adset_report');

            return DataTables::of($query)
                ->addIndexColumn()
                ->filterColumn('adset_name', function($query, $keyword) {
                    $query->where('adset_name', 'like', "%{$keyword}%");
                })
                ->addColumn('total_percentage', function($row){
                    return '100%';
                })
                ->addColumn('paid_percentage', function($row){
                    if ($row->total_count == 0) return '0%';
                    return round(($row->paid_count / $row->total_count) * 100, 2) . '%';
                })
                ->addColumn('partially_paid_percentage', function($row){
                    if ($row->total_count == 0) return '0%';
                    return round(($row->partially_paid_count / $row->total_count) * 100, 2) . '%';
                })
                ->addColumn('pending_percentage', function($row){
                    if ($row->total_count == 0) return '0%';
                    return round(($row->pending_count / $row->total_count) * 100, 2) . '%';
                })
                ->make(true);
        }

        return view('reports.meta_performance_adset');
    }

    public function ad(Request $request)
    {
        if ($request->ajax()) {
            $subQuery = DB::table('shopify_orders as o')
                ->leftJoin('meta_ads_ad_masters as a', 'o.utm_content', '=', 'a.ad_id')
                ->select(
                    DB::raw("COALESCE(a.ad_name, 'Direct / Unknown') as ad_name"),
                    DB::raw('COUNT(*) as total_count'),
                    DB::raw("SUM(CASE WHEN o.financial_status = 'paid' THEN 1 ELSE 0 END) as paid_count"),
                    DB::raw("SUM(CASE WHEN o.financial_status = 'partially_paid' THEN 1 ELSE 0 END) as partially_paid_count"),
                    DB::raw("SUM(CASE WHEN o.financial_status = 'pending' THEN 1 ELSE 0 END) as pending_count")
                )
                // ->whereNotNull('o.order_date') 
                ->groupBy(DB::raw("COALESCE(a.ad_name, 'Direct / Unknown')"));

            $query = DB::query()->fromSub($subQuery, 'ad_report');

            return DataTables::of($query)
                ->addIndexColumn()
                ->filterColumn('ad_name', function($query, $keyword) {
                    $query->where('ad_name', 'like', "%{$keyword}%");
                })
                ->addColumn('total_percentage', function($row){
                    return '100%';
                })
                ->addColumn('paid_percentage', function($row){
                    if ($row->total_count == 0) return '0%';
                    return round(($row->paid_count / $row->total_count) * 100, 2) . '%';
                })
                ->addColumn('partially_paid_percentage', function($row){
                    if ($row->total_count == 0) return '0%';
                    return round(($row->partially_paid_count / $row->total_count) * 100, 2) . '%';
                })
                ->addColumn('pending_percentage', function($row){
                    if ($row->total_count == 0) return '0%';
                    return round(($row->pending_count / $row->total_count) * 100, 2) . '%';
                })
                ->make(true);
        }

        return view('reports.meta_performance_ad');
    }
}
