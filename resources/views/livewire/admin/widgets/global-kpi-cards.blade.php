<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    
    <!-- Business Node Metric -->
    <div class="relative overflow-hidden rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-xl hover:border-slate-700 transition group">
        <div class="flex items-center justify-between">
            <div class="w-12 h-12 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center border border-indigo-500/20 group-hover:scale-110 transition duration-300">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-400 bg-emerald-500/10 px-2.5 py-1 rounded-full border border-emerald-500/20">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                +14.2%
            </span>
        </div>
        <div class="mt-4">
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Businesses</h4>
            <div class="mt-1 flex items-baseline justify-between">
                <p class="text-3xl font-extrabold text-white">{{ number_format($kpis['total_businesses'] ?? 12) }}</p>
                <span class="text-[11px] text-slate-500 font-mono">Active Orgs</span>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
            <span class="text-slate-400">Multi-tenant Accounts</span>
            <a href="#" class="text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1">Manage <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>
        </div>
    </div>

    <!-- Catalog Product Metric -->
    <div class="relative overflow-hidden rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-xl hover:border-slate-700 transition group">
        <div class="flex items-center justify-between">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center border border-emerald-500/20 group-hover:scale-110 transition duration-300">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
            </div>
            <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-400 bg-emerald-500/10 px-2.5 py-1 rounded-full border border-emerald-500/20">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                +8.5%
            </span>
        </div>
        <div class="mt-4">
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Products</h4>
            <div class="mt-1 flex items-baseline justify-between">
                <p class="text-3xl font-extrabold text-white">{{ number_format($kpis['total_products'] ?? 1420) }}</p>
                <span class="text-[11px] text-slate-500 font-mono">Stocked SKUs</span>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
            <span class="text-slate-400">Inventory Status</span>
            <a href="#" class="text-emerald-400 hover:text-emerald-300 font-semibold flex items-center gap-1">View Catalog <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>
        </div>
    </div>

    <!-- Approvals Queue Metric -->
    <div class="relative overflow-hidden rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-xl hover:border-slate-700 transition group">
        <div class="flex items-center justify-between">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center border border-amber-500/20 group-hover:scale-110 transition duration-300">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <span class="inline-flex items-center gap-1 text-xs font-bold text-amber-400 bg-amber-500/10 px-2.5 py-1 rounded-full border border-amber-500/20">
                Action Req.
            </span>
        </div>
        <div class="mt-4">
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pending Approvals</h4>
            <div class="mt-1 flex items-baseline justify-between">
                <p class="text-3xl font-extrabold text-white">{{ number_format($kpis['pending_approvals'] ?? 7) }}</p>
                <span class="text-[11px] text-slate-500 font-mono">In Queue</span>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
            <span class="text-slate-400">Workflow Requests</span>
            <a href="#" class="text-amber-400 hover:text-amber-300 font-semibold flex items-center gap-1">Review Queue <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>
        </div>
    </div>

</div>
