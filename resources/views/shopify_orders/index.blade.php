<x-app-layout>
    <div class="min-h-screen bg-slate-50/50 font-sans text-slate-600">
        
        <!-- Top Navigation / Breadcrumb -->
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <a href="{{ route('dashboard') }}" class="hover:text-primary-600 transition-colors cursor-default cursor-pointer">Dashboard</a>
                    <svg class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    <span class="font-medium text-slate-800">Order Classification</span>
                </div>
                <!-- Optional: Add an action button here if needed in future (e.g. Sync Orders) -->
            </div>
        </nav>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
            
            <!-- Hero Header -->
            <!-- Hero Header -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden mb-6">
                <div class="p-4 bg-white border-b border-slate-50 relative overflow-hidden">
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <h1 class="text-3xl font-bold tracking-tight text-slate-900 mb-2">
                                Order Classification
                            </h1>
                            <!-- <p class="text-slate-500 text-sm max-w-2xl">
                                Manage and track all your incoming orders from Shopify in one place. Filter by status, date, or order ID to find exactly what you need.
                            </p> -->
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
                        <!-- <div class="hidden md:flex items-center gap-3">
                             <button onclick="syncOrders()" id="syncBtn" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-100 text-slate-600 text-sm font-medium transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <svg id="syncIcon" class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <svg id="syncLoader" class="hidden w-5 h-5 text-primary-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span id="syncText">Sync Orders</span>
                             </button>

                             <button onclick="exportOrders()" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-medium transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span>Export CSV</span>
                             </button>
                        </div> -->
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
                                <!-- <th class="py-4 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Order ID</th> -->
                                <th class="py-4 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Order Date</th>
                                <th class="py-4 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Name</th>
                                <th class="py-4 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Total Price</th>
                                <th class="py-4 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Financial Status</th>
                                <!-- <th class="py-4 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">UTM Term</th> -->
                                <th class="py-4 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Campaign</th>
                                <th class="py-4 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Ad Set</th>
                                <th class="py-4 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Ad</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-slate-700 text-sm">
                            <!-- DataTables will populate this -->
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
    
    <!-- Custom Style Overrides for this Page to match Show Page -->
    <style>
        /* Override DataTables Global Styles to match Show Page Theme (Slate vs Primary) */
        table.dataTable thead th {
            background-color: #f8fafc !important; /* bg-slate-50 */
            color: #64748b !important; /* text-slate-500 */
            border-bottom-color: #e2e8f0 !important; /* border-slate-200 */
            font-weight: 700 !important;
            text-transform: uppercase;
            font-size: 0.75rem; /* text-xs */
        }
        
        table.dataTable tbody td {
            border-bottom-color: #f1f5f9 !important; /* divide-slate-100 usually, using slate-100 hex */
            padding: 1rem 1.5rem !important; /* Comfortable padding */
            color: #334155 !important; /* text-slate-700 */
        }
        
        table.dataTable tbody tr:hover {
            background-color: #f8fafc !important; /* bg-slate-50/50ish */
        }

        /* Input Fields in Header */
        .filters input, .filters select {
            background-color: #fff;
            border-color: #e2e8f0;
            border-radius: 0.5rem;
            color: #475569;
            font-size: 0.75rem;
            padding: 0.5rem;
            width: 100%;
            transition: all 0.2s;
        }
        .filters input:focus, .filters select:focus {
            border-color: #457975; /* Primary 500 */
            ring: 1px #457975;
            outline: none;
        }
        
        /* Pagination Styling adjustment */
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

        /* Remove default datatables border */
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
            pageLength: 30,
            order: [[1, "desc"]], // Default sort by Order Date (Column 1)
            ajax: {
                url: "{{ route('shopify_orders.index') }}",
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
                // {data: 'order_id', name: 'order_id'},
                {data: 'order_date', name: 'order_date'},
                {data: 'name', name: 'name'},
                {data: 'total_price', name: 'total_price'},
                {data: 'financial_status', name: 'financial_status'},
                // {data: 'utm_term', name: 'utm_term'},
                {data: 'campaign_name', name: 'campaign_name', sortable:false},
                {data: 'adset_name', name: 'adset_name', sortable:false},
                {data: 'ad_name', name: 'ad_name', sortable:false},
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

      function exportOrders() {
        // Build the query string based on current input values
        var searchParams = new URLSearchParams();
        
        // We need to find the inputs in the ACTUAL header that has .filters class
        var $filters = $('.data-table thead tr.filters');

        $filters.find('th').each(function(index) {
            var $input = $(this).find('input, select');
            if ($input.length > 0) {
                var val = $input.val();
                if (val) {
                     // index corresponds to column index
                    searchParams.append('columns[' + index + '][search][value]', val);
                }
            }
        });

        window.location.href = "{{ route('shopify_orders.export') }}?" + searchParams.toString();
      }

      function syncOrders() {
          var btn = document.getElementById('syncBtn');
          var icon = document.getElementById('syncIcon');
          var loader = document.getElementById('syncLoader');
          var text = document.getElementById('syncText');

          // Loading State
          btn.disabled = true;
          btn.classList.add('opacity-75', 'cursor-not-allowed');
          icon.classList.add('hidden');
          loader.classList.remove('hidden');
          text.innerText = 'Syncing...';

          $.ajax({
              url: "{{ route('shopify_orders.sync') }}",
              type: "POST",
              data: {
                  _token: "{{ csrf_token() }}"
              },
              success: function(response) {
                  if(response.success) {
                      // Reload DataTable
                      $('.data-table').DataTable().ajax.reload();
                      alert('Synced successfully!');
                  } else {
                      alert('Sync failed: ' + response.message);
                  }
              },
              error: function(xhr) {
                  alert('Error syncing orders. Please try again.');
              },
              complete: function() {
                  // Reset State
                  btn.disabled = false;
                  btn.classList.remove('opacity-75', 'cursor-not-allowed');
                  icon.classList.remove('hidden');
                  loader.classList.add('hidden');
                  text.innerText = 'Sync Orders';
              }
          });
      }
    </script>
    @endpush
</x-app-layout>
