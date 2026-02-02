<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class MetaPerformanceReportController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type', 'campaign'); // Default to campaign

        if ($request->ajax()) {
            $subQuery = DB::table('shopify_orders as o');
            $groupByCol = '';
            $selectName = '';

            // Switch logic based on type
            switch ($type) {
                case 'adset':
                    $subQuery->leftJoin('meta_ads_set_masters as s', 'o.utm_term', '=', 's.adset_id');
                    $groupByCol = "COALESCE(s.adset_name, 'Direct / Unknown')";
                    $selectName = "COALESCE(s.adset_name, 'Direct / Unknown') as name";
                    break;
                case 'ad':
                    $subQuery->leftJoin('meta_ads_ad_masters as a', 'o.utm_content', '=', 'a.ad_id');
                    $groupByCol = "COALESCE(a.ad_name, 'Direct / Unknown')";
                    $selectName = "COALESCE(a.ad_name, 'Direct / Unknown') as name";
                    break;
                case 'campaign':
                default:
                    $subQuery->leftJoin('meta_ads_campaign_masters as c', 'o.utm_campaign', '=', 'c.campaign_id');
                    $groupByCol = "COALESCE(c.campaign_name, 'Direct / Unknown')";
                    $selectName = "COALESCE(c.campaign_name, 'Direct / Unknown') as name";
                    break;
            }

            $subQuery->select(
                    DB::raw('DATE(o.order_date) as date'),
                    DB::raw($selectName),
                    DB::raw('COUNT(*) as total_count'),
                    DB::raw("SUM(CASE WHEN o.financial_status = 'paid' THEN 1 ELSE 0 END) as paid_count"),
                    DB::raw("SUM(CASE WHEN o.financial_status = 'partially_paid' THEN 1 ELSE 0 END) as partially_paid_count"),
                    DB::raw("SUM(CASE WHEN o.financial_status = 'pending' THEN 1 ELSE 0 END) as pending_count")
                );

            if ($request->has('date_filter') && $request->date_filter != 'All') {
                switch ($request->date_filter) {
                    case 'Today':
                        $subQuery->whereDate('o.order_date', \Carbon\Carbon::today());
                        break;
                    case 'Yesterday':
                        $subQuery->whereDate('o.order_date', \Carbon\Carbon::yesterday());
                        break;
                    case 'Last 7 Days':
                        $subQuery->whereDate('o.order_date', '>=', \Carbon\Carbon::now()->subDays(7));
                        break;
                    case 'This Month':
                        $subQuery->whereMonth('o.order_date', \Carbon\Carbon::now()->month)
                                 ->whereYear('o.order_date', \Carbon\Carbon::now()->year);
                        break;
                    case 'Last Month':
                        $subQuery->whereMonth('o.order_date', \Carbon\Carbon::now()->subMonth()->month)
                                 ->whereYear('o.order_date', \Carbon\Carbon::now()->subMonth()->year);
                        break;
                     case 'Custom':
                         if($request->has('start_date') && $request->has('end_date')) {
                             $subQuery->whereBetween('o.order_date', [$request->start_date, $request->end_date]);
                         }
                         break;
                }
            }

            $subQuery->whereNotNull('o.order_date') 
                ->groupBy(DB::raw('DATE(o.order_date)'), DB::raw($groupByCol));

            $query = DB::query()->fromSub($subQuery, 'performance_report');

            return DataTables::of($query)
                ->addIndexColumn()
                ->filterColumn('name', function($query, $keyword) {
                    $query->where('name', 'like', "%{$keyword}%");
                })
                ->filterColumn('date', function($query, $keyword) {
                    $query->whereRaw("DATE_FORMAT(date, '%d-%m-%Y') like ?", ["%{$keyword}%"]);
                })
                ->editColumn('date', function($row){
                    return \Carbon\Carbon::parse($row->date)->format('d-m-Y');
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

        return view('reports.meta_performance', ['reportType' => $type]);
    }
}
