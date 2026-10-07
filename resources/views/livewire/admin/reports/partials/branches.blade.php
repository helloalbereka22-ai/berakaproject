{{-- ══ BRANCH PERFORMANCE REPORT ════════════════════════════════════════════ --}}

<div class="space-y-6 pt-4">

    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800">
            <h3 class="font-bold text-slate-100 text-sm">Branch Performance</h3>
            <p class="text-xs text-slate-500 mt-0.5">Online orders + POS sales combined</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-[10px] font-bold uppercase text-slate-500">
                    <th class="px-5 py-3 text-left">Branch</th>
                    <th class="px-5 py-3 text-left">Business</th>
                    <th class="px-5 py-3 text-right">Order Revenue</th>
                    <th class="px-5 py-3 text-right">POS Revenue</th>
                    <th class="px-5 py-3 text-right">Total Revenue</th>
                    <th class="px-5 py-3 text-right">Orders</th>
                    <th class="px-5 py-3 text-right">POS Sales</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-800/40">
                    @forelse($reportData['branches'] ?? [] as $branch)
                    <tr class="hover:bg-slate-800/20">
                        <td class="px-5 py-3 font-semibold text-slate-200">{{ $branch['name'] }}</td>
                        <td class="px-5 py-3 text-slate-400 text-xs">{{ $branch['business'] }}</td>
                        <td class="px-5 py-3 text-right text-slate-300">{{ number_format($branch['order_revenue'], 2) }}</td>
                        <td class="px-5 py-3 text-right text-slate-300">{{ number_format($branch['pos_revenue'], 2) }}</td>
                        <td class="px-5 py-3 text-right font-bold text-emerald-400">{{ number_format($branch['total_revenue'], 2) }}</td>
                        <td class="px-5 py-3 text-right text-slate-400">{{ $branch['order_count'] }}</td>
                        <td class="px-5 py-3 text-right text-slate-400">{{ $branch['pos_transactions'] }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-5 py-8 text-center text-slate-500">No branch data found for this filter.</td></tr>
                    @endforelse
                </tbody>
                @if(count($reportData['branches'] ?? []) > 0)
                <tfoot>
                    <tr class="border-t-2 border-slate-700">
                        <td colspan="4" class="px-5 py-3 font-bold text-slate-300 text-sm">Totals</td>
                        <td class="px-5 py-3 text-right font-extrabold text-emerald-400">
                            {{ number_format(collect($reportData['branches'])->sum('total_revenue'), 2) }}
                        </td>
                        <td class="px-5 py-3 text-right font-bold text-slate-300">
                            {{ collect($reportData['branches'])->sum('order_count') }}
                        </td>
                        <td class="px-5 py-3 text-right font-bold text-slate-300">
                            {{ collect($reportData['branches'])->sum('pos_transactions') }}
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Visual breakdown --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach(collect($reportData['branches'] ?? [])->take(6) as $branch)
        @php
            $totalAll = collect($reportData['branches'])->sum('total_revenue');
            $pct = $totalAll > 0 ? round(($branch['total_revenue'] / $totalAll) * 100, 1) : 0;
        @endphp
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <p class="font-bold text-slate-200 text-sm">{{ $branch['name'] }}</p>
                    <p class="text-xs text-slate-500">{{ $branch['business'] }}</p>
                </div>
                <span class="text-xs font-bold text-indigo-400">{{ $pct }}%</span>
            </div>
            <p class="text-2xl font-extrabold text-emerald-400">{{ number_format($branch['total_revenue'], 2) }}</p>
            <div class="mt-3 h-1.5 bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-indigo-600 to-emerald-500 rounded-full transition-all"
                     style="width: {{ $pct }}%"></div>
            </div>
            <div class="mt-2 flex items-center gap-3 text-xs text-slate-500">
                <span>{{ $branch['order_count'] }} orders</span>
                <span>·</span>
                <span>{{ $branch['pos_transactions'] }} POS</span>
            </div>
        </div>
        @endforeach
    </div>

</div>
