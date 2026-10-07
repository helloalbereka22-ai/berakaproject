<div class="space-y-5">

    
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-white">Seller Dashboard</h2>
            <p class="text-slate-400 text-sm mt-1">Your branch performance overview.</p>
        </div>
        <a href="<?php echo e(route('admin.seller.sales')); ?>" wire:navigate
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold transition shadow-lg shadow-emerald-600/20">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Sell Products
        </a>
    </div>

    
    <?php if(session('order_success')): ?>
        <div class="flex items-center gap-2 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <?php echo e(session('order_success')); ?>

        </div>
    <?php endif; ?>
    <?php if($successMessage): ?>
        <div class="flex items-center gap-2 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <?php echo e($successMessage); ?>

        </div>
    <?php endif; ?>
    <?php if($errorMessage): ?>
        <div class="flex items-center gap-2 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <?php echo e($errorMessage); ?>

        </div>
    <?php endif; ?>

    <?php $k = $this->kpis; ?>

    
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-3">Total Products</p>
                    <p class="text-5xl font-extrabold text-indigo-400 font-mono"><?php echo e(number_format($k['total_categories'])); ?></p>
                    <p class="text-xs text-slate-500 mt-3">Products in stock</p>
                </div>
                <div class="p-3 bg-indigo-500/10 rounded-xl border border-indigo-500/20">
                    <svg class="w-6 h-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-indigo-500/50 to-transparent"></div>
        </div>

        
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-3">Total Item Quantity</p>
                    <p class="text-5xl font-extrabold text-emerald-400 font-mono"><?php echo e(number_format($k['total_item_quantity'], 2)); ?></p>
                    <p class="text-xs text-slate-500 mt-3">Units in stock</p>
                </div>
                <div class="p-3 bg-emerald-500/10 rounded-xl border border-emerald-500/20">
                    <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-emerald-500/50 to-transparent"></div>
        </div>

        
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-3">Transferred Products</p>
                    <p class="text-5xl font-extrabold text-sky-400 font-mono"><?php echo e(number_format($k['total_transferred_products'])); ?></p>
                    <p class="text-xs text-slate-500 mt-3">Products transferred in</p>
                </div>
                <div class="p-3 bg-sky-500/10 rounded-xl border border-sky-500/20">
                    <svg class="w-6 h-6 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-sky-500/50 to-transparent"></div>
        </div>

        
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-3">Total Daily Sales</p>
                    <p class="text-5xl font-extrabold text-amber-400 font-mono"><?php echo e(number_format($k['total_daily_sales'], 2)); ?></p>
                    <p class="text-xs text-slate-500 mt-3">ETB today</p>
                </div>
                <div class="p-3 bg-amber-500/10 rounded-xl border border-amber-500/20">
                    <svg class="w-6 h-6 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-amber-500/50 to-transparent"></div>
        </div>
    </div>

</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/admin/dashboard/branch-seller-dashboard.blade.php ENDPATH**/ ?>