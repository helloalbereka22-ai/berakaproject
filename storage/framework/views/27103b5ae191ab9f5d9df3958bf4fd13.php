<div x-data="{
    deleteModal: false, deleteId: null, deleteName: '',
    branchModal: false,
    orderId: null,
    orderNumber: '',
    branchId: '',
    stockInfo: [],
    loading: false,
    openConfirm(id, number) {
        this.orderId = id;
        this.orderNumber = number;
        this.branchId = '';
        this.stockInfo = [];
        this.branchModal = true;
    },
    checkStock() {
        if (!this.branchId) return;
        this.loading = true;
        fetch('<?php echo e(url('/admin/orders/guest/stock-check')); ?>?branch_id=' + this.branchId + '&order_id=' + this.orderId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => { this.stockInfo = data; this.loading = false; });
    },
    hasInsufficient() { return this.stockInfo.some(s => s.insufficient); },
    hasSufficient()   { return this.stockInfo.some(s => !s.insufficient); },
    allInsufficient() { return this.stockInfo.length > 0 && this.stockInfo.every(s => s.insufficient); }
}">

    
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-white">🛒 View Orders</h2>
            <p class="text-sm text-slate-400 mt-1">Manage customer online orders.</p>
        </div>
    </div>

    
    <?php if($successMessage): ?>
    <div class="mb-4 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <?php echo e($successMessage); ?>

    </div>
    <?php endif; ?>
    <?php if($errorMessage): ?>
    <div class="mb-4 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?php echo e($errorMessage); ?>

    </div>
    <?php endif; ?>

    <?php
        $guestOrders  = \Illuminate\Support\Facades\Schema::hasTable('guest_orders')
            ? \Illuminate\Support\Facades\DB::table('guest_orders')->orderByDesc('created_at')->get()
            : collect();
        $pendingOrders   = $guestOrders->where('status', 'pending')->values();
        $confirmedOrders = $guestOrders->where('status', 'confirmed')->values();
    ?>

    
    <div class="bg-slate-900 border-2 border-amber-500/40 rounded-2xl mb-6">
        <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between bg-amber-500/5">
            <div class="flex items-center gap-3">
                <span class="w-2.5 h-2.5 bg-amber-400 rounded-full animate-pulse"></span>
                <h3 class="font-bold text-amber-300">⏳ Pending Orders</h3>
            </div>
            <span class="px-2.5 py-1 bg-amber-500/20 text-amber-300 text-xs font-bold rounded-full"><?php echo e($pendingOrders->count()); ?></span>
        </div>

        <?php if($pendingOrders->isEmpty()): ?>
        <div class="px-5 py-10 text-center text-slate-500 text-sm">No pending orders.</div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[700px]">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-800/20 text-[11px] uppercase tracking-wider text-slate-500">
                        <th class="text-left px-4 py-3">Order</th>
                        <th class="text-left px-4 py-3">Customer</th>
                        <th class="text-left px-4 py-3">Items</th>
                        <th class="text-right px-4 py-3">Total</th>
                        <th class="text-left px-4 py-3">Address</th>
                        <th class="text-left px-4 py-3">Date</th>
                        <th class="text-center px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php $__currentLoopData = $pendingOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $go): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $items = json_decode($go->items, true) ?? []; ?>
                    <tr class="hover:bg-amber-500/5 transition">
                        <td class="px-4 py-3">
                            <span class="font-mono font-bold text-amber-300 text-xs"><?php echo e($go->order_number); ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-200 text-xs"><?php echo e($go->customer_name); ?></p>
                            <p class="text-slate-500 text-[10px]"><?php echo e($go->customer_phone); ?></p>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-0.5">
                                <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="text-[10px] text-slate-300">
                                    <span class="font-semibold"><?php echo e($item['name'] ?? ''); ?></span>
                                    <span class="text-slate-500"> ×<?php echo e($item['quantity'] ?? 1); ?></span>
                                    <span class="text-emerald-400 ml-1">ETB <?php echo e(number_format(($item['quantity'] ?? 1) * ($item['price'] ?? 0), 2)); ?></span>
                                </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="font-bold font-mono text-white text-xs"><?php echo e(number_format($go->total_amount, 2)); ?></span>
                            <span class="text-slate-500 text-[10px]"> ETB</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-400"><?php echo e(\Illuminate\Support\Str::limit($go->address, 20)); ?></td>
                        <td class="px-4 py-3 text-slate-500 text-[10px] whitespace-nowrap">
                            <?php echo e(\Carbon\Carbon::parse($go->created_at)->format('d M Y H:i')); ?>

                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-1.5 items-center">
                                <button type="button" @click="openConfirm(<?php echo e($go->id); ?>, '<?php echo e($go->order_number); ?>')"
                                    class="w-full px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-bold rounded-lg transition">
                                    ✅ Confirm
                                </button>
                                <form method="POST" action="<?php echo e(route('admin.orders.guest.order.reject', $go->id)); ?>"
                                      onsubmit="return confirm('Reject this order?')">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="w-full px-3 py-1.5 bg-red-600/80 hover:bg-red-500 text-white text-[10px] font-bold rounded-lg transition">
                                        ❌ Reject
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    
    <div class="bg-slate-900 border-2 border-emerald-500/30 rounded-2xl">
        <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between bg-emerald-500/5">
            <div class="flex items-center gap-3">
                <span class="w-2.5 h-2.5 bg-emerald-400 rounded-full"></span>
                <h3 class="font-bold text-emerald-300">✅ Confirmed Orders</h3>
            </div>
            <span class="px-2.5 py-1 bg-emerald-500/20 text-emerald-300 text-xs font-bold rounded-full"><?php echo e($confirmedOrders->count()); ?></span>
        </div>

        <?php if($confirmedOrders->isEmpty()): ?>
        <div class="px-5 py-10 text-center text-slate-500 text-sm">No confirmed orders yet.</div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[700px]">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-800/20 text-[11px] uppercase tracking-wider text-slate-500">
                        <th class="text-left px-4 py-3">Order</th>
                        <th class="text-left px-4 py-3">Customer</th>
                        <th class="text-left px-4 py-3">Items & Price</th>
                        <th class="text-right px-4 py-3">Total</th>
                        <th class="text-left px-4 py-3">Address</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-left px-4 py-3">Date</th>
                        <th class="text-center px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php $__currentLoopData = $confirmedOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $go): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $items = json_decode($go->items, true) ?? []; ?>
                    <tr class="hover:bg-emerald-500/5 transition">
                        <td class="px-4 py-3">
                            <span class="font-mono font-bold text-emerald-300 text-xs"><?php echo e($go->order_number); ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-200 text-xs"><?php echo e($go->customer_name); ?></p>
                            <p class="text-slate-500 text-[10px]"><?php echo e($go->customer_phone); ?></p>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-0.5">
                                <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="text-[10px] whitespace-nowrap">
                                    <span class="text-slate-200 font-semibold"><?php echo e($item['name'] ?? ''); ?></span>
                                    <span class="text-slate-500 font-mono ml-1">
                                        <?php echo e($item['quantity'] ?? 1); ?>×<?php echo e(number_format($item['price'] ?? 0, 2)); ?>

                                        = <span class="text-emerald-300 font-bold"><?php echo e(number_format(($item['quantity'] ?? 1) * ($item['price'] ?? 0), 2)); ?></span>
                                    </span>
                                </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="font-bold font-mono text-emerald-300 text-xs"><?php echo e(number_format($go->total_amount, 2)); ?></span>
                            <span class="text-slate-500 text-[10px]"> ETB</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-400"><?php echo e(\Illuminate\Support\Str::limit($go->address, 20)); ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-full text-[10px] font-bold">✅ Confirmed</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-[10px] whitespace-nowrap">
                            <?php echo e(\Carbon\Carbon::parse($go->created_at)->format('d M Y H:i')); ?>

                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-1.5 items-center">
                                <form method="POST" action="<?php echo e(route('admin.orders.guest.order.pending', $go->id)); ?>"
                                      onsubmit="return confirm('Return to Pending?')">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="w-full px-3 py-1.5 bg-amber-600/80 hover:bg-amber-500 text-white text-[10px] font-bold rounded-lg transition">
                                        ↩ Pending
                                    </button>
                                </form>
                                <button @click="deleteModal=true; deleteId=<?php echo e($go->id); ?>; deleteName='<?php echo e(addslashes($go->order_number)); ?>'"
                                    class="w-full px-3 py-1.5 bg-red-600/80 hover:bg-red-500 text-white text-[10px] font-bold rounded-lg transition">
                                    🗑️ Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    
    <div x-show="deleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center" @click.stop>
            <div class="w-14 h-14 bg-red-500/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <h3 class="font-bold text-white text-lg mb-2">Delete Order?</h3>
            <p class="text-slate-400 text-sm mb-6">Order <span class="text-white font-bold" x-text="deleteName"></span> will be permanently deleted.</p>
            <form method="POST" :action="'<?php echo e(url('/admin/orders/guest/')); ?>/' + deleteId + '/delete'">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 py-2.5 bg-red-600 hover:bg-red-500 text-white text-sm font-bold rounded-xl transition">🗑️ Delete</button>
                    <button type="button" @click="deleteModal=false" class="flex-1 py-2.5 bg-slate-700 hover:bg-slate-600 text-white text-sm font-bold rounded-xl transition">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    
    <div x-show="branchModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800 sticky top-0 bg-slate-900">
                <div>
                    <h3 class="font-bold text-white">✅ Confirm Order</h3>
                    <p class="text-xs text-slate-400 mt-0.5" x-text="'Order #' + orderNumber"></p>
                </div>
                <button @click="branchModal=false" class="text-slate-500 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Select Branch *</label>
                    <select x-model="branchId" @change="checkStock()"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm text-white focus:border-emerald-500 outline-none transition">
                        <option value="">— Select Branch —</option>
                        <?php $__currentLoopData = \App\Models\Branch::with('business')->orderBy('name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($branch->id); ?>"><?php echo e($branch->name); ?> — <?php echo e($branch->business?->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div x-show="loading" class="text-center py-4 text-slate-400 text-sm">Checking stock...</div>

                <div x-show="branchId && !loading && stockInfo.length > 0">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Stock Availability</p>
                    <template x-for="item in stockInfo" :key="item.name">
                        <div class="flex items-center justify-between px-4 py-2.5 rounded-xl mb-2"
                             :class="item.insufficient ? 'bg-red-500/10 border border-red-500/30' : 'bg-emerald-500/10 border border-emerald-500/30'">
                            <div>
                                <p class="text-sm font-bold text-white" x-text="item.name"></p>
                                <p class="text-xs text-slate-400">
                                    Ordered: <span class="font-bold text-white" x-text="item.ordered"></span>
                                    | Available: <span class="font-bold" :class="item.insufficient ? 'text-red-400' : 'text-emerald-400'" x-text="item.available"></span>
                                </p>
                            </div>
                            <template x-if="item.insufficient">
                                <span class="px-2 py-1 bg-red-500/20 text-red-400 text-[10px] font-bold rounded-full">⚠️ LOW</span>
                            </template>
                            <template x-if="!item.insufficient">
                                <span class="px-2 py-1 bg-emerald-500/20 text-emerald-400 text-[10px] font-bold rounded-full">✅ OK</span>
                            </template>
                        </div>
                    </template>
                </div>

                <div x-show="stockInfo.length > 0 && hasInsufficient()"
                     class="p-3 bg-amber-500/10 border border-amber-500/30 rounded-xl text-amber-300 text-xs space-y-1">
                    <p class="font-bold">⚠️ Some items have insufficient stock.</p>
                    <p>• Select a different branch, or</p>
                    <p>• <a href="<?php echo e(route('admin.purchases.transfer')); ?>" class="text-indigo-400 underline">Transfer stock</a> to this branch first, or</p>
                    <p x-show="hasSufficient()">• Confirm only available items (insufficient stay Pending)</p>
                </div>

                <form method="POST" :action="'<?php echo e(url('/admin/orders/guest/')); ?>/' + orderId + '/confirm-branch'">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="branch_id" :value="branchId">
                    <div class="flex flex-col gap-2">
                        <button type="submit" name="partial" value="0"
                            x-show="!hasInsufficient()"
                            :disabled="!branchId"
                            class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-40 text-white font-black rounded-xl transition">
                            ✅ Confirm Order
                        </button>
                        <button type="submit" name="partial" value="1"
                            x-show="hasInsufficient() && hasSufficient()"
                            :disabled="!branchId"
                            class="w-full py-3 bg-blue-600 hover:bg-blue-500 disabled:opacity-40 text-white font-black rounded-xl transition">
                            ✅ Confirm Available Items Only
                        </button>
                        <div x-show="allInsufficient() && branchId"
                             class="w-full py-3 bg-red-500/10 border border-red-500/30 text-red-400 text-xs font-bold rounded-xl text-center px-4">
                            ❌ No items available at this branch.
                        </div>
                        <button type="button" @click="branchModal=false"
                            class="w-full py-2.5 bg-slate-700 hover:bg-slate-600 text-white font-bold rounded-xl transition text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/admin/orders/index.blade.php ENDPATH**/ ?>