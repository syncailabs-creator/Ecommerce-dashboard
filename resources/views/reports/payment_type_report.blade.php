<x-app-layout>
    <div class="min-h-screen bg-slate-50/50 font-sans text-slate-600">
        
        <!-- Top Navigation -->
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <a href="{{ route('dashboard') }}" class="hover:text-primary-600 transition-colors cursor-default cursor-pointer">Dashboard</a>
                    <svg class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    <span class="font-medium text-slate-800">Payment Type Report</span>
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
                                Payment Type Report
                            </h1>
                        </div>
                        <div class="flex items-center gap-4">
                            <select id="date_filter" class="rounded-lg border-slate-200 text-sm font-medium text-slate-700 focus:ring-primary-500 focus:border-primary-500 py-2 pl-3 pr-10">
                                <option value="All">All Time</option>
                                <option value="Today">Today</option>
                                <option value="Yesterday">Yesterday</option>
                                <option value="Last 7 Days">Last 7 Days</option>
                                <option value="This Month">This Month</option>
                                <option value="Last Month">Last Month</option>
                                <option value="Custom">Custom Range</option>
                            </select>
                            
                            <div id="custom_date_range" class="flex items-center gap-2 hidden">
                                <input type="date" id="start_date" class="rounded-lg border-slate-200 text-sm font-medium text-slate-700 focus:ring-primary-500 focus:border-primary-500 py-2 px-3">
                                <span class="text-slate-400">-</span>
                                <input type="date" id="end_date" class="rounded-lg border-slate-200 text-sm font-medium text-slate-700 focus:ring-primary-500 focus:border-primary-500 py-2 px-3">
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
        /* Shared Styles from Shopify Orders */
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
        
        var table = $('.data-table').DataTable({
            processing: true,
            serverSide: true,
            orderCellsTop: true,
            fixedHeader: true,
            pageLength: 30, // Default pagination
            order: [[1, "desc"]], // Default sort by Date (column 1) descending
            ajax: {
                url: "{{ route('reports.payment_type') }}",
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                data: function (d) {
                    d.date_filter = $('#date_filter').val();
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                }
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'date', name: 'date'},
                {data: 'total_percentage', name: 'total_percentage', orderable: false, searchable: false},
                {data: 'total_count', name: 'total_count'},
                {data: 'paid_percentage', name: 'paid_percentage', orderable: false, searchable: false},
                {data: 'paid_count', name: 'paid_count'},
                {data: 'partially_paid_percentage', name: 'partially_paid_percentage', orderable: false, searchable: false},
                {data: 'partially_paid_count', name: 'partially_paid_count'},
                {data: 'pending_percentage', name: 'pending_percentage', orderable: false, searchable: false},
                {data: 'pending_count', name: 'pending_count'},
            ],
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
