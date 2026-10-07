
<?php
    $rows       = collect($reportData['rows'] ?? []);
    $totalQty   = $reportData['total_qty'] ?? 0;
    $totalCount = $reportData['total_count'] ?? 0;
?>

<div class="space-y-5 pt-4">

    
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Transfers</p>
            <p class="text-2xl font-extrabold text-indigo-400"><?php echo e($totalCount); ?></p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Qty Transferred</p>
            <p class="text-2xl font-extrabold text-cyan-400"><?php echo e(number_format($totalQty, 2)); ?></p>
        </div>
    </div>

    
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
            <h3 class="font-bold text-white text-sm">⟷ Transfer Records</h3>
            <span class="text-xs text-slate-500"><?php echo e($totalCount); ?> record(s)</span>
        </div>

        <?php if($rows->isEmpty()): ?>
            <div class="py-16 text-center">
                <div class="text-5xl mb-4">⟷</div>
                <p class="text-slate-400 font-medium text-sm">No transfer records found</p>
                <p class="text-slate-600 text-xs mt-1">No products have been transferred between branches</p>
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-950/60 border-b border-slate-800 text-[11px] uppercase tracking-widest text-slate-400">
                        <th class="text-left px-5 py-3.5 font-bold">#</th>
                        <th class="text-left px-5 py-3.5 font-bold">Category</th>
                        <th class="text-left px-5 py-3.5 font-bold">Item</th>
                        <th class="text-right px-5 py-3.5 font-bold">Qty</th>
                        <th class="text-left px-5 py-3.5 font-bold">From Branch</th>
                        <th class="text-left px-5 py-3.5 font-bold">To Branch</th>
                        <th class="text-left px-5 py-3.5 font-bold">Notes</th>
                        <th class="text-left px-5 py-3.5 font-bold">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="px-5 py-3 text-slate-500 text-xs"><?php echo e($i + 1); ?></td>
                        <td class="px-5 py-3 text-xs text-slate-400"><?php echo e($row['category']); ?></td>
                        <td class="px-5 py-3">
                            <span class="font-semibold text-white"><?php echo e($row['item']); ?></span>
                            <?php if($row['unit']): ?>
                                <span class="text-slate-500 text-[10px] ml-1"><?php echo e($row['unit']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3 text-right font-mono font-bold text-cyan-400">
                            <?php echo e(number_format($row['qty'], 2)); ?>

                        </td>
                        <td class="px-5 py-3 text-xs text-slate-300"><?php echo e($row['from_branch']); ?></td>
                        <td class="px-5 py-3 text-xs text-emerald-400 font-semibold"><?php echo e($row['to_branch']); ?></td>
                        <td class="px-5 py-3 text-xs text-slate-500 max-w-xs truncate"><?php echo e($row['notes']); ?></td>
                        <td class="px-5 py-3 text-xs text-slate-500">
                            <?php echo e($row['date'] ? \Carbon\Carbon::parse($row['date'])->format('d M Y H:i') : '—'); ?>

                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-700 bg-slate-950/70 font-bold text-xs">
                        <td class="px-5 py-3.5 text-slate-400" colspan="3">Total</td>
                        <td class="px-5 py-3.5 text-right font-mono text-cyan-400">
                            <?php echo e(number_format($totalQty, 2)); ?>

                        </td>
                        <td colspan="4" class="px-5 py-3.5"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/admin/reports/partials/transfers.blade.php ENDPATH**/ ?>