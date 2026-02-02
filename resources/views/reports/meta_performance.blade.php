<x-app-layout>
    <div class="min-h-screen bg-slate-50/50 font-sans text-slate-600">
        
        <!-- Top Navigation -->
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <a href="{{ route('dashboard') }}" class="hover:text-primary-600 transition-colors cursor-default cursor-pointer">Dashboard</a>
                    <svg class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    <span class="font-medium text-slate-800">Meta Performance Report</span>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
            
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden mb-6">
                <div class="p-4 bg-white border-b border-slate-50 relative overflow-hidden">
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <h1 class="text-3xl font-bold tracking-tight text-slate-900 mb-2">
                                Meta Performance Report
                            </h1>
                        </div>
                        <div class="flex items-center gap-4 flex-wrap">
                            <select id="report_type_filter" class="rounded-lg border-slate-200 text-sm font-medium text-slate-700 focus:ring-primary-500 focus:border-primary-500 py-2 pl-3 pr-10">
                                <option value="campaign" {{ $reportType == 'campaign' ? 'selected' : '' }}>Campaign</option>
                                <option value="adset" {{ $reportType == 'adset' ? 'selected' : '' }}>Ad Set</option>
                                <option value="ad" {{ $reportType == 'ad' ? 'selected' : '' }}>Ad</option>
                            </select>

                            <select id="date_filter" class="rounded-lg border-slate-200 text-sm font-medium text-slate-700 focus:ring-primary-500 focus:border-primary-500 py-2 pl-3 pr-10">
                                <option value="All" {{ request('date_filter') == 'All' ? 'selected' : '' }}>All Time</option>
                                <option value="Today" {{ request('date_filter') == 'Today' ? 'selected' : '' }}>Today</option>
                                <option value="Yesterday" {{ request('date_filter') == 'Yesterday' ? 'selected' : '' }}>Yesterday</option>
                                <option value="Last 7 Days" {{ request('date_filter') == 'Last 7 Days' ? 'selected' : '' }}>Last 7 Days</option>
                                <option value="This Month" {{ request('date_filter') == 'This Month' ? 'selected' : '' }}>This Month</option>
                                <option value="Last Month" {{ request('date_filter') == 'Last Month' ? 'selected' : '' }}>Last Month</option>
                                <option value="Custom" {{ request('date_filter') == 'Custom' ? 'selected' : '' }}>Custom Range</option>
                            </select>
                            
                            <div id="custom_date_range" class="flex items-center gap-2 {{ request('date_filter') == 'Custom' ? '' : 'hidden' }}">
                                <input type="date" id="start_date" value="{{ request('start_date') }}" class="rounded-lg border-slate-200 text-sm font-medium text-slate-700 focus:ring-primary-500 focus:border-primary-500 py-2 px-3">
                                <span class="text-slate-400">-</span>
                                <input type="date" id="end_date" value="{{ request('end_date') }}" class="rounded-lg border-slate-200 text-sm font-medium text-slate-700 focus:ring-primary-500 focus:border-primary-500 py-2 px-3">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6">
                    <table class="w-full text-left border-separate border-spacing-0 data-table">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="py-4 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">No</th>
                                <th class="py-4 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Date</th>
                                <th class="py-4 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                                    @if($reportType == 'campaign') Campaign Name
                                    @elseif($reportType == 'adset') Ad Set Name
                                    @elseif($reportType == 'ad') Ad Name
                                    @endif
                                </th>
                                <th class="py-4 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Total (%)</th>
                                <th class="py-4 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Total Count</th>
                                <th class="py-4 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Paid (%)</th>
                                <th class="py-4 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Paid Count</th>
                                <th class="py-4 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Partially Paid (%)</th>
                                <th class="py-4 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Partially Count</th>
                                <th class="py-4 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">COD (%)</th>
                                <th class="py-4 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">COD Count</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-slate-700 text-sm">
                        </tbody>
                    </table>
                </div>
            </div>
            
        </main>
    </div>

    @push('scripts')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" rel="stylesheet">
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    
    <style>
        /* Shared Styles */
        table.dataTable thead th {
            background-color: #f8fafc !important;
            color: #64748b !important;
            border-bottom-color: #e2e8f0 !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            font-size: 0.75rem;
        }
        
        table.dataTable tbody td {
            border-bottom-color: #f1f5f9 !important;
            padding: 1rem 1.5rem !important;
            color: #334155 !important;
        }
        
        table.dataTable tbody tr:hover {
            background-color: #f8fafc !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #457975 !important;
            border-color: #457975 !important;
            color: white !important;
            border-radius: 0.5rem;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f1f5f9 !important;
            border-color: #e2e8f0 !important;
            color: #334155 !important;
            border-radius: 0.5rem;
        }

        table.dataTable.no-footer {
            border-bottom: none !important;
        }
    </style>

    <script type="text/javascript">
      $(function () {
        
        // Setup - add a text input to each footer cell
        $('.data-table thead tr')
            .clone(true)
            .addClass('filters')
            .appendTo('.data-table thead');

        var table = $('.data-table').DataTable({
            processing: true,
            serverSide: true,
            orderCellsTop: true,
            fixedHeader: true,
            pageLength: 30, 
            order: [[1, "desc"]],
            ajax: {
                url: "{{ route('reports.meta_performance') }}?type={{ $reportType }}",
                data: function(d) {
                    d.type = "{{ $reportType }}";
                    d.date_filter = $('#date_filter').val();
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                }
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'date', name: 'date'},
                {data: 'name', name: 'name'}, // Generic name field
                {data: 'total_percentage', name: 'total_percentage', orderable: false, searchable: false},
                {data: 'total_count', name: 'total_count'},
                {data: 'paid_percentage', name: 'paid_percentage', orderable: false, searchable: false},
                {data: 'paid_count', name: 'paid_count'},
                {data: 'partially_paid_percentage', name: 'partially_paid_percentage', orderable: false, searchable: false},
                {data: 'partially_paid_count', name: 'partially_paid_count'},
                {data: 'pending_percentage', name: 'pending_percentage', orderable: false, searchable: false},
                {data: 'pending_count', name: 'pending_count'},
            ],
            initComplete: function () {
                var api = this.api();
    
                // For each column
                api
                    .columns()
                    .eq(0)
                    .each(function (colIdx) {
                        // Set the header cell to contain the input element
                        var cell = $('.filters th').eq(
                            $(api.column(colIdx).header()).index()
                        );
                        var title = $(cell).text();
                        
                        // Skip 'No' column - clear it
                        if (title == 'No' || title.includes('%')) {
                             $(cell).html('');
                             $(cell).css('border-bottom', 'none'); 
                             return;
                        }

                        $(cell).html('<input type="text" placeholder="Filter..." style="width: 100%; border-radius: 4px; border: 1px solid #ddd; padding: 4px;" />');
    
                        // On every keypress in this input
                        $('input', $('.filters th').eq($(api.column(colIdx).header()).index()))
                            .off('keyup change')
                            .on('keyup', function (e) {
                                e.stopPropagation();
                                $(this).trigger('change');
                            })
                            .on('change', function (e) {
                                var val = $(this).val();
                                api
                                    .column(colIdx)
                                    .search(val ? val : '', false, true)
                                    .draw();
                            });
                    });
            },
        });

        $('#report_type_filter').change(function(){
            var type = $(this).val();
            var dateFilter = $('#date_filter').val();
            var startDate = $('#start_date').val();
            var endDate = $('#end_date').val();

            var newUrl = "{{ route('reports.meta_performance') }}?type=" + type;
            
            if(dateFilter) {
                newUrl += "&date_filter=" + dateFilter;
            }
            if(startDate) {
                newUrl += "&start_date=" + startDate;
            }
            if(endDate) {
                newUrl += "&end_date=" + endDate;
            }

            // Reload page with new type and existing date filters
            window.location.href = newUrl;
        });

        $('#date_filter').change(function(){
            var val = $(this).val();
            if(val === 'Custom'){
                $('#custom_date_range').removeClass('hidden');
            } else {
                $('#custom_date_range').addClass('hidden');
                table.draw();
            }
        });

        $('#start_date, #end_date').change(function(){
            if($('#start_date').val() && $('#end_date').val()){
                table.draw();
            }
        });

      });
    </script>
    @endpush
</x-app-layout>
