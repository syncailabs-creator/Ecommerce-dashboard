<x-app-layout>
    <div class="min-h-screen bg-slate-50/50 font-sans text-slate-600">
        
        <!-- Top Navigation -->
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <a href="{{ route('dashboard') }}" class="hover:text-primary-600 transition-colors cursor-default cursor-pointer">Dashboard</a>
                    <svg class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    <a href="{{ route('shopify_orders.index') }}" class="hover:text-primary-600 transition-colors">Shopify Orders</a>
                    <svg class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    <span class="font-medium text-slate-800">Shopify Order {{ $order->name }}</span>
                </div>
                <a href="{{ route('shopify_orders.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 hover:text-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all">
                    <svg class="mr-2 -ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    Back to List
                </a>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
            
            <!-- Hero Header -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden mb-6">
                <div class="p-6 sm:p-8 border-b border-slate-50 relative overflow-hidden">
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <span class="px-2.5 py-0.5 rounded-full bg-slate-100 border border-slate-200 text-slate-600 text-xs font-semibold">Shopify Order</span>
                                @php
                                    $financialStatus = strtolower($order->financial_status);
                                    $badgeColor = match($financialStatus) {
                                        'paid' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
                                        'pending' => 'bg-amber-100 text-amber-700 border border-amber-200',
                                        'refunded' => 'bg-rose-100 text-rose-700 border border-rose-200',
                                        'voided' => 'bg-slate-100 text-slate-700 border border-slate-200',
                                        default => 'bg-slate-100 text-slate-700 border border-slate-200'
                                    };
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider {{ $badgeColor }} shadow-sm">
                                    {{ $order->financial_status }}
                                </span>
                            </div>
                            <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-slate-900 mb-1">
                                {{ $order->name }}
                            </h1>
                            <p class="text-xs text-slate-400 font-mono mb-2">Order ID: {{ $order->order_id }}</p>
                            <div class="flex items-center gap-4 text-slate-500 text-sm">
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    {{ \Carbon\Carbon::parse($order->order_date)->format('F d, Y') }}
                                </div>
                                <div class="w-1 h-1 rounded-full bg-slate-300"></div>
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    {{ \Carbon\Carbon::parse($order->order_date)->format('h:i A') }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 min-w-[200px] text-right">
                            <p class="text-slate-500 text-xs uppercase tracking-wider font-semibold mb-1">Total Amount</p>
                            <div class="text-3xl font-bold text-slate-900 flex items-center justify-end gap-1">
                                {{ number_format((float)$order->total_price, 2) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Order Items -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                <span class="p-1.5 rounded-lg bg-primary-50 text-primary-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                </span>
                                Order Items
                            </h3>
                            <span class="text-xs font-semibold text-slate-500 bg-white px-2.5 py-1 rounded-md border border-slate-100 shadow-sm">{{ $order->products->count() }} items</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <tr>
                                        <th class="px-6 py-4">Product Details</th>
                                        <th class="px-6 py-4 text-right">Price</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($order->products as $product)
                                        <tr class="group hover:bg-slate-50/50 transition-colors duration-150">
                                            <td class="px-6 py-4">
                                                <div class="flex items-start gap-4">
                                                    <!-- <div class="h-12 w-12 rounded-lg bg-primary-50 border border-primary-100 flex items-center justify-center text-primary-600 font-bold text-lg flex-shrink-0">
                                                        {{ substr($product->name, 0, 1) }}
                                                    </div> -->
                                                    <div>
                                                        <p class="text-sm font-semibold text-slate-800 group-hover:text-primary-600 transition-colors line-clamp-2">
                                                            {{ $product->name }}
                                                        </p>
                                                        <!-- <p class="text-xs text-slate-400 mt-0.5 font-mono">ID: <span class="select-all">{{ $product->id }}</span></p> -->
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <span class="text-sm font-bold text-slate-700 font-mono">{{ number_format((float)$product->price, 2) }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="px-6 py-12 text-center text-slate-400">
                                                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                                </svg>
                                                <p class="mt-2 text-sm">No items found in this order.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="bg-slate-50/80 border-t border-slate-100">
                                    <tr>
                                        <td class="px-6 py-4 text-right text-sm font-medium text-slate-500">Order Total</td>
                                        <td class="px-6 py-4 text-right text-base font-bold text-slate-900">{{ number_format((float)$order->total_price, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Meta Info -->
                <div class="space-y-6">
                    
                    <!-- Analytics Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                            <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wide">
                                Analytics Source
                            </h3>
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">UTM Term</label>
                                <div class="mt-1 flex items-center p-2.5 bg-slate-50 rounded-lg border border-slate-100">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-slate-700 truncate" title="{{ $order->utm_term }}">
                                            {{ $order->utm_term ?: 'Direct / Not Set' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">UTM Content</label>
                                <div class="mt-1 flex items-center p-2.5 bg-slate-50 rounded-lg border border-slate-100">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-slate-700 truncate" title="{{ $order->utm_content }}">
                                            {{ $order->utm_content ?: '-' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">UTM Campaign</label>
                                <div class="mt-1 flex items-center p-2.5 bg-slate-50 rounded-lg border border-slate-100">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-slate-700 truncate" title="{{ $order->utm_campaign }}">
                                            {{ $order->utm_campaign ?: '-' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tags Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                            <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wide">
                                Tags
                            </h3>
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <div class="p-6">
                            @if(!empty($order->tags))
                                <div class="flex flex-wrap gap-2">
                                    @foreach(explode(',', $order->tags) as $tag)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-primary-50 text-primary-700 border border-primary-100 hover:bg-primary-100 transition-colors cursor-default">
                                            {{ trim($tag) }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-4 border-2 border-dashed border-slate-100 rounded-xl">
                                    <span class="text-sm text-slate-400">No tags used</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 text-center">
                 <p class="text-xs text-slate-400">
                    Order data synchronization occurred at {{ $order->updated_at->format('M d, Y h:i A') }}
                </p>
            </div>

        </main>
    </div>
</x-app-layout>
