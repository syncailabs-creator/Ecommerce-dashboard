<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Shopify Orders') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-slate-600">
                    <table class="table w-full border-collapse border border-slate-200 data-table">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="border border-slate-200 p-2 text-left font-semibold text-slate-600 uppercase text-xs">No</th>
                                <th class="border border-slate-200 p-2 text-left font-semibold text-slate-600 uppercase text-xs">Order ID</th>
                                <th class="border border-slate-200 p-2 text-left font-semibold text-slate-600 uppercase text-xs">Name</th>
                                <th class="border border-slate-200 p-2 text-left font-semibold text-slate-600 uppercase text-xs">Total Price</th>
                                <th class="border border-slate-200 p-2 text-left font-semibold text-slate-600 uppercase text-xs">Financial Status</th>
                                <th class="border border-slate-200 p-2 text-left font-semibold text-slate-600 uppercase text-xs">UTM Term</th>
                                <th class="border border-slate-200 p-2 text-left font-semibold text-slate-600 uppercase text-xs">Order Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" rel="stylesheet">
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    


    <script type="text/javascript">
      $(function () {
        
        var table = $('.data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('shopify_orders.index') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'order_id', name: 'order_id'},
                {data: 'name', name: 'name'},
                {data: 'total_price', name: 'total_price'},
                {data: 'financial_status', name: 'financial_status'},
                {data: 'utm_term', name: 'utm_term'},
                {data: 'order_date', name: 'order_date'},
            ]
        });
        
      });
    </script>
    @endpush
</x-app-layout>
