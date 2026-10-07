<div class="bg-slate-900 border border-slate-800 shadow-xl rounded-2xl h-full flex flex-col overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-800/80 flex flex-wrap justify-between items-center gap-2 bg-slate-900/60">
        <div>
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18"/></svg>
                Inventory In / Out Movement
            </h3>
            <p class="text-[11px] text-slate-400">7-Day Stock Volume Analytics</p>
        </div>
        <select class="rounded-xl border-slate-800 bg-slate-950 text-slate-300 text-xs px-3 py-1.5 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 font-medium">
            <option>Last 7 Days</option>
            <option>Last 30 Days</option>
            <option>Last 90 Days</option>
        </select>
    </div>
    
    <div class="flex-1 p-6 flex flex-col justify-between">
        <!-- CSS-only bar chart -->
        <div class="flex items-end justify-between h-48 gap-3 pt-4">
            @foreach($trendData as $day)
                @php
                    $maxVal = 200;
                    $inHeight = ($day['stock_in'] / $maxVal) * 100;
                    $outHeight = ($day['stock_out'] / $maxVal) * 100;
                @endphp
                <div class="flex-1 flex flex-col items-center gap-1 group">
                    <div class="w-full flex items-end justify-center gap-1.5" style="height: 180px;">
                        <div class="w-2.5 sm:w-4 bg-gradient-to-t from-emerald-600 to-emerald-400 rounded-t-lg transition-all duration-300 group-hover:brightness-125 shadow-lg shadow-emerald-500/20" 
                             style="height: {{ max($inHeight, 8) }}%;" 
                             title="Stock In: {{ $day['stock_in'] }}"></div>
                        <div class="w-2.5 sm:w-4 bg-gradient-to-t from-indigo-600 to-violet-400 rounded-t-lg transition-all duration-300 group-hover:brightness-125 shadow-lg shadow-indigo-500/20" 
                             style="height: {{ max($outHeight, 8) }}%;" 
                             title="Stock Out: {{ $day['stock_out'] }}"></div>
                    </div>
                    <span class="text-[11px] font-medium text-slate-400 mt-2 font-mono">{{ $day['day'] }}</span>
                </div>
            @endforeach
        </div>
        
        <!-- Legend -->
        <div class="flex justify-center space-x-6 pt-6 mt-4 border-t border-slate-800/80">
            <div class="flex items-center text-xs text-slate-300 font-medium">
                <span class="w-3 h-3 bg-gradient-to-r from-emerald-500 to-emerald-400 rounded-full mr-2 shadow-sm shadow-emerald-500/50"></span>
                <span>Stock Received (In)</span>
            </div>
            <div class="flex items-center text-xs text-slate-300 font-medium">
                <span class="w-3 h-3 bg-gradient-to-r from-indigo-500 to-violet-400 rounded-full mr-2 shadow-sm shadow-indigo-500/50"></span>
                <span>Stock Dispatched (Out)</span>
            </div>
        </div>
    </div>
</div>
