<x-app-layout>
    <div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8 font-sans">
        
        <!-- Breadcrumb / Back Navigation -->
        <div class="max-w-5xl mx-auto mb-6 flex items-center justify-between">
            <div class="text-sm text-slate-400">Order Information</div>
            <a href="{{ route('shopify_orders.index') }}" class="group flex items-center text-slate-500 hover:text-indigo-600 transition-colors duration-200">
                <div class="mr-2 p-2 rounded-full bg-white shadow-sm ring-1 ring-slate-200 group-hover:ring-indigo-100 group-hover:bg-indigo-50 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </div>
                <span class="font-medium text-sm">Back to Orders</span>
            </a>
        </div>

        <!-- Main "One Card" Container -->
        <div class="max-w-5xl mx-auto bg-white rounded-3xl shadow-[0_20px_60px_-15px_rgba(0,0,0,0.05)] overflow-hidden ring-1 ring-slate-100 p-4">
            
            <!-- Header Section with soft gradient -->
            <div class="px-8 py-8 bg-gradient-to-r from-slate-50 via-white to-slate-50 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <span class="px-3 py-1 bg-indigo-50 text-indigo-600 border border-indigo-100 rounded-full text-xs font-bold uppercase tracking-wider">Shopify Order</span>
                        @php
                            $statusColors = [
                                'paid' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                'pending' => 'bg-amber-50 text-amber-600 border-amber-100',
                                'refunded' => 'bg-rose-50 text-rose-600 border-rose-100',
                                'voided' => 'bg-slate-50 text-slate-600 border-slate-100',
                            ];
                            $statusClass = $statusColors[$order->financial_status] ?? 'bg-blue-50 text-blue-600 border-blue-100';
                        @endphp
                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border {{ $statusClass }}">
                            {{ $order->financial_status }}
                        </span>
                    </div>
                    <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">{{ $order->name }}</h1>
                    <div class="flex items-center gap-4 mt-3 text-slate-500 text-sm">
                        <div class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            {{ \Carbon\Carbon::parse($order->order_date)->format('F d, Y') }}
                        </div>
                        <div class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            {{ \Carbon\Carbon::parse($order->order_date)->format('h:i A') }}
                        </div>
                    </div>
                </div>
                
                <div class="text-right">
                    <p class="text-sm font-medium text-slate-400 uppercase tracking-wide mb-1">Total Amount</p>
                    <div class="text-4xl font-bold text-slate-800 flex items-baseline justify-end gap-1">
                        <span class="text-lg text-slate-400 font-medium">$</span>{{ number_format((float)$order->total_price, 2) }}
                    </div>
                </div>
            </div>

            <!-- Content Grid: Layout splitting Main Content (Items) and Sidebar (Context) -->
            <div class="flex flex-col lg:flex-row">
                
                <!-- Left Details Panel (Product List) -->
                <div class="lg:w-2/3 p-8 border-r border-slate-100">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                             <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                             Purchased Items
                        </h3>
                        <span class="text-xs font-semibold text-slate-400 uppercase">{{ $order->products->count() }} Items</span>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-slate-100 shadow-sm">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-100">
                                <tr>
                                    <th class="py-3 px-4 text-xs font-bold uppercase tracking-wider">Product Info</th>
                                    <th class="py-3 px-4 text-xs font-bold uppercase tracking-wider text-right">Price</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @forelse($order->products as $product)
                                <tr class="group hover:bg-slate-50/50 transition-colors">
                                    <td class="py-4 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 flex-shrink-0 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold text-sm">
                                                {{ substr($product->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <p class="font-semibold text-slate-700 group-hover:text-indigo-600 transition-colors">{{ $product->name }}</p>
                                                <p class="text-xs text-slate-400">ID: {{ $product->id }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <span class="font-medium text-slate-700">${{ number_format((float)$product->price, 2) }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="py-8 text-center text-slate-400 text-sm">
                                        No items available for this order.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-slate-50/50 border-t border-slate-100">
                                <tr>
                                    <td class="py-3 px-4 text-right font-medium text-slate-500 text-sm">Total</td>
                                    <td class="py-3 px-4 text-right font-bold text-slate-800">${{ number_format((float)$order->total_price, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Right Sidebar (Metadata) -->
                <div class="lg:w-1/3 p-8 bg-slate-50/30">
                    
                    <!-- Insight: Marketing -->
                    <div class="mb-8">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                             <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                             Marketing Insights
                        </h4>
                        
                        <div class="space-y-3">
                            <div class="p-3 bg-white rounded-xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">UTM Term</span>
                                <div class="font-medium text-slate-700 truncate" title="{{ $order->utm_term }}">{{ $order->utm_term ?: 'Direct / Not Set' }}</div>
                            </div>
                            <div class="p-3 bg-white rounded-xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">UTM Content</span>
                                <div class="font-medium text-slate-700 truncate" title="{{ $order->utm_content }}">{{ $order->utm_content ?: 'N/A' }}</div>
                            </div>
                            <div class="p-3 bg-white rounded-xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">UTM Campaign</span>
                                <div class="font-medium text-slate-700 truncate" title="{{ $order->utm_campaign }}">{{ $order->utm_campaign ?: 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Insight: Tags -->
                    <div>
                         <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                             <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                             Tags
                        </h4>
                        @if(!empty($order->tags))
                            <div class="flex flex-wrap gap-2">
                                @foreach(explode(',', $order->tags) as $tag)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-200 text-slate-800">
                                        {{ trim($tag) }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <div class="p-3 border-2 border-dashed border-slate-200 rounded-xl text-center">
                                <span class="text-xs text-slate-400 font-medium">No tags attached</span>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
            
            <!-- Quick Actions Footer -->
            <div class="bg-gray-50 px-8 py-4 border-t border-slate-100 flex justify-between items-center text-xs text-slate-400">
                <span>Data last updated: {{ $order->updated_at->format('M d, Y h:i A') }}</span>
            </div>

        </div>
    </div>
</x-app-layout>
