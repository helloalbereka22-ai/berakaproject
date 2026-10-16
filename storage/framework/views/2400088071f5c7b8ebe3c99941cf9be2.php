
<?php
    $rows   = $reportData['rows']   ?? collect();
    $totals = $reportData['totals'] ?? [];
?>

<div class="space-y-5 pt-4">

        
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Qty Sold</p>
                <p class="text-xl font-extrabold text-sky-400 font-mono"><?php echo e(number_format($totals['sold_qty'] ?? 0, 2)); ?></p>
            </div>
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Revenue</p>
                <p class="text-xl font-extrabold text-emerald-400 font-mono"><?php echo e(number_format($totals['selling_price'] ?? 0, 2)); ?> <span class="text-xs font-normal text-slate-600">ETB</span></p>
            </div>
        </div>

    
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
            <h3 class="font-bold text-slate-100 text-sm">My Sales Records</h3>
            <span class="text-xs text-slate-500"><?php echo e($rows->count()); ?> record(s)</span>
        </div>

        <?php if($rows->isEmpty()): ?>
            <div class="py-16 text-center text-slate-500 text-sm">
                <svg class="w-8 h-8 mx-auto mb-3 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                No sales data for the selected date range.
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-950/60 border-b border-slate-800 text-[11px] uppercase tracking-widest text-slate-400">
                        <th class="text-left px-5 py-3.5 font-bold">Item Name</th>
                        <th class="text-right px-5 py-3.5 font-bold">Qty</th>

                        <th class="text-right px-5 py-3.5 font-bold">Selling Price</th>
                        <th class="text-right px-5 py-3.5 font-bold">Total</th>
                        <th class="text-left px-5 py-3.5 font-bold">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="hover:bg-slate-800/30 transition">

                        
                        <td class="px-5 py-3">
                            <p class="font-semibold text-white"><?php echo e($row->item_name); ?></p>
                            <p class="text-[10px] text-slate-500 mt-0.5"><?php echo e($row->category_name); ?></p>
                        </td>

                        
                        <td class="px-5 py-3 text-right font-mono text-slate-200">
                            <?php echo e(number_format($row->sold_qty, 2)); ?>

                        </td>



                        
                        <td class="px-5 py-3 text-right font-mono text-emerald-300 font-bold">
                            <?php echo e(number_format($row->selling_price, 2)); ?>

                            <span class="text-slate-600 text-[10px]"> ETB</span>
                        </td>

                        <td class="px-5 py-3 text-right font-mono text-sky-400">
    <?php echo e(number_format($row->sold_qty * $row->selling_price, 2)); ?>

    <span class="text-slate-600 text-[10px]"> ETB</span>
</td>

                        
                        <td class="px-5 py-3 text-xs text-slate-500 whitespace-nowrap">
                            <?php echo e(\Carbon\Carbon::parse($row->sale_date)->format('d M Y H:i')); ?>

                        </td>

                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>

                
                <tfoot class="bg-slate-950/80 border-t-2 border-indigo-500/30">
                        <td class="px-5 py-4 text-sm uppercase tracking-wider">Total</td>
                        <td class="px-5 py-4 text-right font-mono text-sky-400"><?php echo e(number_format($totals['sold_qty'] ?? 0, 2)); ?></td>

                        <td class="px-5 py-4 text-right font-mono text-emerald-300"><?php echo e(number_format($totals['selling_price'] ?? 0, 2)); ?></td>
                        <td class="px-5 py-4 text-right font-mono text-sky-400"><?php echo e(number_format($rows->sum(fn($r) => $r->sold_qty * $r->selling_price), 2)); ?></td>
                        <td class="px-5 py-4"></td>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/admin/reports/partials/mfr-sales.blade.php ENDPATH**/ ?>