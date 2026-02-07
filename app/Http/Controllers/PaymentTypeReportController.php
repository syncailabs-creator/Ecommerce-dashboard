<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class PaymentTypeReportController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $subQuery = DB::table('shopify_orders')
                ->select(
                    DB::raw('DATE(order_date) as date'),
                    DB::raw('COUNT(*) as total_count'),
                    DB::raw("SUM(CASE WHEN financial_status = 'paid' THEN 1 ELSE 0 END) as paid_count"),
                    DB::raw("SUM(CASE WHEN financial_status = 'partially_paid' THEN 1 ELSE 0 END) as partially_paid_count"),
                    DB::raw("SUM(CASE WHEN financial_status = 'pending' THEN 1 ELSE 0 END) as pending_count")
                )
                ->whereNotNull('order_date');
                
            if ($request->has('date_filter') && $request->date_filter != 'All') {
                switch ($request->date_filter) {
                    case 'Today':
                        $subQuery->whereDate('order_date', Carbon::today());
                        break;
                    case 'Yesterday':
                        $subQuery->whereDate('order_date', Carbon::yesterday());
                        break;
                    case 'This Week':
                         $subQuery->whereBetween('order_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                         break;
                    case 'Last Week':
                         $subQuery->whereBetween('order_date', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
                         break;
                    case 'Last 7 Days':
                        $subQuery->whereDate('order_date', '>=', Carbon::now()->subDays(7));
                        break;
                    case 'This Month':
                        $subQuery->whereMonth('order_date', Carbon::now()->month)
                                 ->whereYear('order_date', Carbon::now()->year);
                        break;
                    case 'Last Month':
                        $subQuery->whereMonth('order_date', Carbon::now()->subMonth()->month)
                                 ->whereYear('order_date', Carbon::now()->subMonth()->year);
                        break;
                    case 'This Year':
                         $subQuery->whereYear('order_date', Carbon::now()->year);
                         break;
                     case 'Custom':
                         if($request->has('start_date') && $request->has('end_date')) {
                             $subQuery->whereBetween('order_date', [$request->start_date, Carbon::parse($request->end_date)->endOfDay()]);
                         }
                         break;
                }
            }

            $subQuery->groupBy(DB::raw('DATE(order_date)'));

            $query = DB::query()->fromSub($subQuery, 'daily_report');

            return DataTables::of($query)
                ->filterColumn('date', function($query, $keyword) {
                    $query->whereRaw("DATE_FORMAT(date, '%d-%m-%Y') like ?", ["%{$keyword}%"]);
                })
                ->addIndexColumn()
                ->editColumn('date', function($row){
                    return Carbon::parse($row->date)->format('d-m-Y');
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

        return view('reports.payment_type_report');
    }
}
