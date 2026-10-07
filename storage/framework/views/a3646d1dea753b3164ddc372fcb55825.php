<div class="space-y-8" x-data="{ showStart: $wire.entangle('showStartForm'), showPay: $wire.entangle('showPayForm') }"
     wire:poll-60000="autoRefresh">

    
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4
                bg-gradient-to-r from-slate-900 via-slate-800/60 to-slate-900
                border border-slate-700/40 rounded-2xl px-6 py-5">
        <div>
            <p class="text-xs font-bold text-emerald-500 uppercase tracking-widest mb-1">☪ Islamic Finance</p>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Zakat al-Mal</h1>
            <p class="text-sm text-slate-400 mt-1">Annual obligatory alms on business wealth</p>
        </div>
        <div class="flex gap-3 shrink-0">
            <?php if($summary['has_cycle']): ?>
            <button wire:click="refreshWealth"
                    class="flex items-center gap-2 px-4 py-2.5 bg-slate-700 hover:bg-slate-600 text-white text-sm font-bold rounded-xl transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Refresh Wealth
            </button>
            <?php endif; ?>
            <button wire:click="$set('showStartForm', true)"
                    class="flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold rounded-xl transition shadow-lg shadow-emerald-900/30">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?php echo e($summary['has_cycle'] ? 'New Cycle' : 'Start Cycle'); ?>

            </button>
        </div>
    </div>

    
    <?php if($successMsg): ?>
    <div x-data x-init="setTimeout(() => $wire.set('successMsg', false), 4000)"
         class="flex items-center gap-2 px-4 py-3 bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-sm rounded-xl">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        Action completed successfully.
    </div>
    <?php endif; ?>

    
    <?php if($summary['has_cycle']): ?>
    <?php
        $cycle  = $summary['cycle'];
        $status = $summary['status'];
        $days   = $summary['days_until_due'];
        $statusConfig = [
            'active'   => ['bg'=>'bg-emerald-950/40','border'=>'border-emerald-700/40','badge'=>'bg-emerald-500/20 text-emerald-400','dot'=>'bg-emerald-400','label'=>'Active'],
            'due'      => ['bg'=>'bg-amber-950/40',  'border'=>'border-amber-700/40',  'badge'=>'bg-amber-500/20 text-amber-400',  'dot'=>'bg-amber-400 animate-pulse','label'=>'Due'],
            'overdue'  => ['bg'=>'bg-red-950/40',    'border'=>'border-red-700/40',    'badge'=>'bg-red-500/20 text-red-400',    'dot'=>'bg-red-500 animate-pulse',  'label'=>'Overdue'],
            'paid'     => ['bg'=>'bg-slate-900',     'border'=>'border-slate-700',     'badge'=>'bg-slate-700 text-slate-300',   'dot'=>'bg-slate-400',              'label'=>'Paid'],
        ];
        $sc = $statusConfig[$status] ?? $statusConfig['active'];
    ?>
    <div class="<?php echo e($sc['bg']); ?> border <?php echo e($sc['border']); ?> rounded-2xl p-7">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6">
            <div>
                <h2 class="font-extrabold text-white text-lg">Current Zakat Cycle</h2>
                <p class="text-slate-400 text-sm mt-1">
                    Started: <?php echo e($cycle->start_date->format('d M Y')); ?>

                    <?php if($cycle->start_date_hijri): ?>
                        <span class="text-slate-500">(<?php echo e($cycle->start_date_hijri); ?>)</span>
                    <?php endif; ?>
                </p>
            </div>
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold <?php echo e($sc['badge']); ?>">
                <span class="w-2 h-2 rounded-full <?php echo e($sc['dot']); ?>"></span>
                <?php echo e($sc['label']); ?>

            </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-black/20 rounded-xl p-4">
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1">Zakatable Wealth</p>
                <p class="text-xl font-black text-white break-all"><?php echo e(number_format($summary['wealth'], 2)); ?></p>
                <p class="text-xs text-slate-600 mt-0.5">ETB (Total Selling + Raw Material Cost)</p>
            </div>
            <div class="bg-black/20 rounded-xl p-4">
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1">Nisab Threshold</p>
                <p class="text-xl font-black text-white break-all"><?php echo e(number_format($summary['nisab'], 2)); ?></p>
                <p class="text-xs text-slate-600 mt-0.5">ETB · <?php echo e($cycle->zakat_rate); ?>%</p>
            </div>
            <div class="bg-black/20 rounded-xl p-4">
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1">Zakat Due</p>
                <p class="text-xl font-black <?php echo e($summary['zakat_due'] > 0 ? 'text-amber-400' : 'text-slate-400'); ?> break-all">
                    <?php echo e(number_format($summary['zakat_due'], 2)); ?>

                </p>
                <p class="text-xs text-slate-600 mt-0.5">ETB</p>
            </div>
            <div class="bg-black/20 rounded-xl p-4">
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1">Due Date</p>
                <p class="text-sm font-black text-white"><?php echo e($cycle->due_date->format('d M Y')); ?></p>
                <?php if($cycle->due_date_hijri): ?>
                <p class="text-[10px] text-slate-500 mt-0.5"><?php echo e($cycle->due_date_hijri); ?></p>
                <?php endif; ?>
                <?php if($days !== null): ?>
                <p class="text-xs mt-1 <?php echo e($days < 0 ? 'text-red-400' : ($days <= 30 ? 'text-amber-400' : 'text-emerald-400')); ?>">
                    <?php echo e($days < 0 ? abs($days).' days overdue' : ($days == 0 ? 'Due today' : $days.' days remaining')); ?>

                </p>
                <?php endif; ?>
            </div>
        </div>

        
        <?php if($status === 'due' || $status === 'overdue'): ?>
        <div class="flex items-start justify-between gap-4 p-4 rounded-xl
                    <?php echo e($status === 'overdue' ? 'bg-red-500/15 border border-red-500/30' : 'bg-amber-500/15 border border-amber-500/30'); ?>">
            <div class="flex items-start gap-3">
                <span class="text-2xl"><?php echo e($status === 'overdue' ? '🔴' : '🟡'); ?></span>
                <div>
                    <p class="font-bold text-white text-sm">
                        <?php echo e($status === 'overdue' ? 'Zakat is Overdue!' : 'Zakat is Due'); ?>

                    </p>
                    <p class="text-slate-400 text-xs mt-0.5">
                        Your annual Zakat amount is <strong class="text-white"><?php echo e(number_format($summary['zakat_due'], 2)); ?> ETB</strong>.
                        <?php echo e($status === 'overdue' ? 'Please pay as soon as possible.' : 'It is time to pay your Zakat.'); ?>

                    </p>
                </div>
            </div>
            <button wire:click="openPayForm"
                    class="shrink-0 px-4 py-2 bg-white text-slate-900 text-xs font-extrabold rounded-xl hover:bg-gray-100 transition whitespace-nowrap">
                Pay Zakat
            </button>
        </div>
        <?php elseif($status === 'active' && $summary['zakat_due'] > 0): ?>
        <div class="flex items-center justify-between gap-4 p-4 rounded-xl bg-slate-800/60 border border-slate-700">
            <div class="flex items-center gap-3">
                <span class="text-xl">🟢</span>
                <p class="text-slate-300 text-sm">
                    Wealth above Nisab — Zakat will be due in
                    <strong class="text-white"><?php echo e($days); ?> days</strong>
                    (<?php echo e($cycle->due_date->format('d M Y')); ?>).
                </p>
            </div>
            <button wire:click="openPayForm"
                    class="shrink-0 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl transition">
                Pay Early
            </button>
        </div>
        <?php elseif($summary['wealth'] < $summary['nisab']): ?>
        <div class="flex items-center gap-3 p-4 rounded-xl bg-slate-800/60 border border-slate-700">
            <span class="text-xl">ℹ️</span>
            <p class="text-slate-400 text-sm">
                Current wealth (<?php echo e(number_format($summary['wealth'], 2)); ?> ETB) is below Nisab
                (<?php echo e(number_format($summary['nisab'], 2)); ?> ETB). No Zakat is due yet.
            </p>
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-10 text-center">
        <div class="text-5xl mb-4">☪</div>
        <p class="text-white font-bold text-lg mb-2">No Active Zakat Cycle</p>
        <p class="text-slate-400 text-sm mb-6">Start your first Zakat cycle to begin tracking your annual obligation.</p>
        <button wire:click="$set('showStartForm', true)"
                class="px-6 py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl transition">
            Start First Cycle
        </button>
    </div>
    <?php endif; ?>

    
    <div x-show="showStart" x-cloak x-transition
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div @click.away="showStart = false"
             class="bg-slate-900 border border-slate-700 rounded-2xl p-7 w-full max-w-md shadow-2xl">
            <h3 class="font-extrabold text-white text-lg mb-5">
                <?php echo e($summary['has_cycle'] ? 'Start New Cycle' : 'Start Zakat Cycle'); ?>

            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Nisab Amount (ETB)</label>
                    <input wire:model="nisabAmount" type="number" step="0.01" min="0"
                           placeholder="e.g. 500000"
                           class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white
                                  placeholder-slate-500 focus:border-emerald-500 outline-none transition">
                    <?php $__errorArgs = ['nisabAmount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs text-red-400 mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    <p class="text-xs text-slate-600 mt-1">Set according to the Nisab method you follow</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Zakat Rate (%)</label>
                    <input wire:model="zakatRate" type="number" step="0.01" min="0.1" max="100"
                           class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white
                                  focus:border-emerald-500 outline-none transition">
                    <?php $__errorArgs = ['zakatRate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs text-red-400 mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Cycle Start Date</label>
                    <input wire:model="startDate" type="date"
                           class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white
                                  focus:border-emerald-500 outline-none transition">
                    <?php $__errorArgs = ['startDate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs text-red-400 mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Hijri Start Date <span class="font-normal text-slate-600">(optional, for display)</span></label>
                    <input wire:model="startHijri" type="text"
                           placeholder="e.g. 17 Safar 1448 AH"
                           class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white
                                  placeholder-slate-500 focus:border-emerald-500 outline-none transition">
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button @click="showStart = false"
                        class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-bold rounded-xl transition">
                    Cancel
                </button>
                <button wire:click="startCycle"
                        class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold rounded-xl transition">
                    <span wire:loading.remove wire:target="startCycle">Start Cycle</span>
                    <span wire:loading wire:target="startCycle">Starting…</span>
                </button>
            </div>
        </div>
    </div>

    
    <div x-show="showPay" x-cloak x-transition
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div @click.away="showPay = false"
             class="bg-slate-900 border border-slate-700 rounded-2xl p-7 w-full max-w-md shadow-2xl">
            <h3 class="font-extrabold text-white text-lg mb-1">Pay Zakat al-Mal</h3>
            <p class="text-slate-400 text-sm mb-5">Record your Zakat payment. A new cycle will start automatically.</p>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Amount Paid (ETB)</label>
                    <input wire:model="paymentAmount" type="number" step="0.01" min="0.01"
                           class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white
                                  focus:border-amber-500 outline-none transition">
                    <?php $__errorArgs = ['paymentAmount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs text-red-400 mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Payment Reference / Receipt</label>
                    <input wire:model="paymentReference" type="text"
                           placeholder="e.g. receipt number, bank ref..."
                           class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white
                                  placeholder-slate-500 focus:border-amber-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Notes <span class="font-normal text-slate-600">(optional)</span></label>
                    <textarea wire:model="paymentNotes" rows="2"
                              placeholder="Where was it paid, to whom..."
                              class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white
                                     placeholder-slate-500 focus:border-amber-500 outline-none transition resize-none"></textarea>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button @click="showPay = false"
                        class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-bold rounded-xl transition">
                    Cancel
                </button>
                <button wire:click="recordPayment"
                        class="flex-1 py-2.5 bg-amber-600 hover:bg-amber-500 text-white text-sm font-bold rounded-xl transition">
                    <span wire:loading.remove wire:target="recordPayment">✅ Confirm Payment</span>
                    <span wire:loading wire:target="recordPayment">Saving…</span>
                </button>
            </div>
        </div>
    </div>

    
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <h3 class="font-bold text-white text-sm">☪ Zakat History</h3>
            <span class="text-xs text-slate-500"><?php echo e($history->count()); ?> cycle(s)</span>
        </div>
        <?php if($history->isEmpty()): ?>
        <div class="py-12 text-center text-slate-500 text-sm">No Zakat cycles recorded yet.</div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-950/60 border-b border-slate-800 text-[11px] uppercase tracking-widest text-slate-400">
                        <th class="text-left px-5 py-3 font-bold">Year</th>
                        <th class="text-left px-5 py-3 font-bold">Start</th>
                        <th class="text-left px-5 py-3 font-bold">Due Date</th>
                        <th class="text-right px-5 py-3 font-bold">Wealth (ETB)</th>
                        <th class="text-right px-5 py-3 font-bold">Nisab (ETB)</th>
                        <th class="text-right px-5 py-3 font-bold">Zakat Due</th>
                        <th class="text-right px-5 py-3 font-bold">Paid</th>
                        <th class="text-center px-5 py-3 font-bold">Status</th>
                        <th class="text-left px-5 py-3 font-bold">Paid Date</th>
                        <th class="text-left px-5 py-3 font-bold">Reference</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    <?php $__currentLoopData = $history; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $badges = [
                            'active'  => 'bg-emerald-500/20 text-emerald-400',
                            'due'     => 'bg-amber-500/20 text-amber-400',
                            'overdue' => 'bg-red-500/20 text-red-400',
                            'paid'    => 'bg-slate-700 text-slate-300',
                        ];
                        $badge = $badges[$row->status] ?? 'bg-slate-700 text-slate-300';
                    ?>
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="px-5 py-3 font-mono text-xs text-indigo-400">
                            <?php echo e($row->start_date_hijri ? substr($row->start_date_hijri, -7) : $row->start_date->format('Y')); ?>

                        </td>
                        <td class="px-5 py-3 text-xs text-slate-400"><?php echo e($row->start_date->format('d M Y')); ?></td>
                        <td class="px-5 py-3 text-xs text-slate-400"><?php echo e($row->due_date->format('d M Y')); ?></td>
                        <td class="px-5 py-3 text-right font-mono text-slate-300 text-xs"><?php echo e(number_format($row->zakatable_amount, 2)); ?></td>
                        <td class="px-5 py-3 text-right font-mono text-slate-400 text-xs"><?php echo e(number_format($row->nisab_amount, 2)); ?></td>
                        <td class="px-5 py-3 text-right font-mono font-bold text-amber-400 text-xs"><?php echo e(number_format($row->zakat_due, 2)); ?></td>
                        <td class="px-5 py-3 text-right font-mono text-emerald-400 text-xs"><?php echo e(number_format($row->amount_paid, 2)); ?></td>
                        <td class="px-5 py-3 text-center">
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full <?php echo e($badge); ?>">
                                <?php echo e(ucfirst($row->status)); ?>

                            </span>
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-500"><?php echo e($row->paid_date?->format('d M Y') ?? '—'); ?></td>
                        <td class="px-5 py-3 text-xs text-slate-500 max-w-xs truncate"><?php echo e($row->payment_reference ?: '—'); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/admin/zakat/zakat-manager.blade.php ENDPATH**/ ?>