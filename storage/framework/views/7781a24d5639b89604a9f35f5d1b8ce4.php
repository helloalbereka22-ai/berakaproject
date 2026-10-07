
<?php
    $rows   = collect($reportData['branchSaleRows'] ?? []);
    $totals = $reportData['branchSaleTotals'] ?? [];
?>

<div class="space-y-5 pt-4">

    
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Qty Sold</p>
            <p class="text-2xl font-extrabold text-sky-400"><?php echo e(number_format($totals['qty'] ?? 0, 2)); ?></p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Cost</p>
            <p class="text-2xl font-extrabold text-red-400"><?php echo e(number_format($totals['total_cost'] ?? 0, 2)); ?></p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Revenue</p>
            <p class="text-2xl font-extrabold text-emerald-400"><?php echo e(number_format($totals['revenue'] ?? 0, 2)); ?></p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Profit</p>
            <p class="text-2xl font-extrabold <?php echo e(($totals['profit'] ?? 0) >= 0 ? 'text-blue-400' : 'text-red-400'); ?>">
                <?php echo e(number_format($totals['profit'] ?? 0, 2)); ?>

            </p>
        </div>
    </div>

    
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-white text-sm">📈 Profit by Branch & Item</h3>
                <p class="text-xs text-slate-500 mt-0.5">All branch sales with cost and profit breakdown</p>
            </div>
            <span class="text-xs text-slate-500"><?php echo e($rows->count()); ?> record(s)</span>
        </div>

        <?php if($rows->isEmpty()): ?>
            <div class="py-16 text-center">
                <div class="text-5xl mb-4">📈</div>
                <p class="text-slate-400 font-medium text-sm">No sales records found</p>
                <p class="text-slate-600 text-xs mt-1">Record branch sales to see profit data here</p>
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-950/60 border-b border-slate-800 text-[11px] uppercase tracking-widest text-slate-400">
                        <th class="text-left px-5 py-3.5 font-bold">#</th>
                        <th class="text-left px-5 py-3.5 font-bold">Branch</th>
                        <th class="text-left px-5 py-3.5 font-bold">Item Name</th>
                        <th class="text-right px-5 py-3.5 font-bold">Qty</th>
                        <th class="text-right px-5 py-3.5 font-bold">Purchase Price</th>
                        <th class="text-right px-5 py-3.5 font-bold">Selling Price</th>
                        <th class="text-right px-5 py-3.5 font-bold">Total Cost</th>
                        <th class="text-right px-5 py-3.5 font-bold">Profit</th>
                        <th class="text-left px-5 py-3.5 font-bold">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $profit = (float)$row->profit; ?>
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="px-5 py-3 text-slate-500 text-xs"><?php echo e($i + 1); ?></td>
                        <td class="px-5 py-3 text-xs text-slate-300 font-semibold"><?php echo e($row->branch_name); ?></td>
                        <td class="px-5 py-3">
                            <p class="font-semibold text-white"><?php echo e($row->item_name); ?></p>
                            <p class="text-[10px] text-slate-500"><?php echo e($row->category_name); ?></p>
                        </td>
                        <td class="px-5 py-3 text-right font-mono text-sky-300">
                            <?php echo e(number_format($row->quantity, 2)); ?>

                            <span class="text-slate-600 text-[10px]"> <?php echo e($row->unit); ?></span>
                        </td>
                        <td class="px-5 py-3 text-right font-mono text-slate-300">
                            <?php echo e(number_format($row->purchase_price, 2)); ?>

                        </td>
                        <td class="px-5 py-3 text-right font-mono text-emerald-300">
                            <?php echo e(number_format($row->selling_price, 2)); ?>

                        </td>
                        <td class="px-5 py-3 text-right font-mono text-red-400">
                            <?php echo e(number_format($row->total_cost, 2)); ?>

                        </td>
                        <td class="px-5 py-3 text-right font-mono font-bold <?php echo e($profit >= 0 ? 'text-blue-400' : 'text-red-400'); ?>">
                            <?php echo e(number_format($profit, 2)); ?>

                        </td>
                        <td class="px-5 py-3 text-xs text-slate-500">
                            <?php echo e($row->sale_date ? \Carbon\Carbon::parse($row->sale_date)->format('d M Y') : '—'); ?>

                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-700 bg-slate-950/70 font-bold text-xs">
                        <td class="px-5 py-3.5 text-slate-400" colspan="3">Total</td>
                        <td class="px-5 py-3.5 text-right font-mono text-sky-300">
                            <?php echo e(number_format($totals['qty'] ?? 0, 2)); ?>

                        </td>
                        <td class="px-5 py-3.5"></td>
                        <td class="px-5 py-3.5"></td>
                        <td class="px-5 py-3.5 text-right font-mono text-red-400">
                            <?php echo e(number_format($totals['total_cost'] ?? 0, 2)); ?>

                        </td>
                        <td class="px-5 py-3.5 text-right font-mono text-blue-400">
                            <?php echo e(number_format($totals['profit'] ?? 0, 2)); ?>

                        </td>
                        <td class="px-5 py-3.5"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/admin/reports/partials/profit.blade.php ENDPATH**/ ?>