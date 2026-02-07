<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Models\ShipwayOrder;

class DeliveryClassificationReportController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type', 'campaign'); // Default to campaign
        $statuses = ShipwayOrder::STATUSES;

        if ($request->ajax()) {
            $subQuery = DB::table('shopify_orders as o')
                ->leftJoin('shipway_orders as so', 'o.name', '=', 'so.order_id');
            
            $groupByCol = '';
            $selectName = '';

            // Switch logic based on type to join appropriate Meta Master table
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

            // Build dynamic selects for each status
            $selects = [
                DB::raw($selectName),
                DB::raw('COUNT(*) as total_count'),
            ];

            foreach ($statuses as $status) {
                $safeStatus = addslashes($status);
                $colName = str_replace(' ', '_', strtolower($status));
                // Note: counting based on shipway_orders shipment_status_name
                $selects[] = DB::raw("SUM(CASE WHEN so.shipment_status_name = '$safeStatus' THEN 1 ELSE 0 END) as {$colName}_count");
            }
            
            $subQuery->select($selects);

            // Date filtering on shopify_orders order_date
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
                             $subQuery->whereBetween('o.order_date', [$request->start_date, \Carbon\Carbon::parse($request->end_date)->endOfDay()]);
                         }
                         break;
                }
            }

            $subQuery->groupBy(DB::raw($groupByCol));

            $query = DB::query()->fromSub($subQuery, 'delivery_classification_report');

            $dataTable = DataTables::of($query)
                ->addIndexColumn()
                ->filterColumn('name', function($query, $keyword) {
                    $query->where('name', 'like', "%{$keyword}%");
                })
                ->addColumn('total_percentage', function($row){
                    return '100%';
                });

            // Add percentage columns for each status
            foreach ($statuses as $status) {
                $colNameBase = str_replace(' ', '_', strtolower($status));
                $countCol = "{$colNameBase}_count";
                
                $dataTable->addColumn("{$colNameBase}_percentage", function($row) use ($countCol) {
                    if ($row->total_count == 0) return '0%';
                    return round(($row->$countCol / $row->total_count) * 100, 2) . '%';
                });
            }

            return $dataTable->make(true);
        }

        return view('reports.delivery_classification', ['reportType' => $type, 'statuses' => $statuses]);
    }
}
