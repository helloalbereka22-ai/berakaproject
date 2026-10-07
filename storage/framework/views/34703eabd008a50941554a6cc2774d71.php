
<div class="space-y-5 pt-4">

    <?php
        $rows        = $reportData instanceof \Illuminate\Support\Collection ? $reportData : collect($reportData);
        $totalMfgQty = $rows->sum('mfg_qty');
        $totalValue  = $rows->sum('total_value');
        $totalSeed   = $rows->sum('seed_qty_used');
    ?>

    
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Products</p>
            <p class="text-2xl font-extrabold text-indigo-400"><?php echo e($rows->count()); ?></p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Manufactured</p>
            <p class="text-2xl font-extrabold text-violet-400"><?php echo e(number_format($totalMfgQty, 2)); ?></p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Seed Used</p>
            <p class="text-2xl font-extrabold text-amber-400"><?php echo e(number_format($totalSeed, 2)); ?></p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Value (ETB)</p>
            <p class="text-2xl font-extrabold text-emerald-400"><?php echo e(number_format($totalValue, 2)); ?></p>
        </div>
    </div>

    
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-white text-sm">🏭 Manufactured Products</h3>
                <p class="text-xs text-slate-500 mt-0.5">All products manufactured — quantities from manufacturer records</p>
            </div>
            <span class="text-xs text-slate-500"><?php echo e($rows->count()); ?> product(s)</span>
        </div>

        <?php if($rows->isEmpty()): ?>
            <div class="py-16 text-center">
                <div class="text-5xl mb-4">🏭</div>
                <p class="text-slate-400 font-medium text-sm">No manufactured products found</p>
                <p class="text-slate-600 text-xs mt-1">Manufacturer must register production first</p>
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-950/60 border-b border-slate-800 text-[11px] uppercase tracking-widest text-slate-400">
                        <th class="text-left px-5 py-3.5 font-bold">#</th>
                        <th class="text-left px-5 py-3.5 font-bold">Product</th>
                        <th class="text-left px-5 py-3.5 font-bold">Category</th>
                        <th class="text-right px-5 py-3.5 font-bold">Manufactured Qty</th>
                        <th class="text-left px-5 py-3.5 font-bold">Seed Used</th>
                        <th class="text-right px-5 py-3.5 font-bold">Seed Qty</th>
                        <th class="text-right px-5 py-3.5 font-bold">Selling Price</th>
                        <th class="text-right px-5 py-3.5 font-bold">Total Value</th>
                        <th class="text-left px-5 py-3.5 font-bold">Last Manufactured</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="px-5 py-3 text-slate-500 text-xs"><?php echo e($i + 1); ?></td>
                        <td class="px-5 py-3">
                            <p class="font-semibold text-white"><?php echo e($row['item_name']); ?></p>
                            <p class="text-[10px] text-slate-500"><?php echo e($row['business_name']); ?></p>
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-400"><?php echo e($row['category_name']); ?></td>
                        <td class="px-5 py-3 text-right">
                            <span class="font-black font-mono text-violet-400 text-base">
                                <?php echo e(number_format($row['mfg_qty'], 2)); ?>

                            </span>
                            <span class="text-slate-600 text-[10px] ml-1"><?php echo e($row['unit']); ?></span>
                        </td>
                        <td class="px-5 py-3 text-sm text-amber-300 font-semibold">
                            <?php echo e($row['seed_name'] !== '—' ? $row['seed_name'] : '—'); ?>

                        </td>
                        <td class="px-5 py-3 text-right font-mono text-amber-400">
                            <?php if($row['seed_qty_used'] > 0): ?>
                                <?php echo e(number_format($row['seed_qty_used'], 2)); ?>

                                <span class="text-slate-600 text-[10px]"> <?php echo e($row['seed_unit']); ?></span>
                            <?php else: ?>
                                <span class="text-slate-600">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3 text-right font-mono text-slate-300">
                            <?php echo e(number_format($row['selling_price'], 2)); ?>

                            <span class="text-slate-600 text-[10px]"> ETB</span>
                        </td>
                        <td class="px-5 py-3 text-right font-mono font-bold text-emerald-300">
                            <?php echo e(number_format($row['total_value'], 2)); ?>

                            <span class="text-slate-600 text-[10px] font-normal"> ETB</span>
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-500">
                            <?php if($row['last_mfg_at']): ?>
                                <?php echo e(\Carbon\Carbon::parse($row['last_mfg_at'])->format('d M Y')); ?>

                                <span class="text-slate-600 block text-[10px]">
                                    <?php echo e(\Carbon\Carbon::parse($row['last_mfg_at'])->format('H:i')); ?>

                                </span>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-700 bg-slate-950/70 font-bold text-xs">
                        <td class="px-5 py-3.5 text-slate-400" colspan="3">Total</td>
                        <td class="px-5 py-3.5 text-right font-mono text-violet-400">
                            <?php echo e(number_format($totalMfgQty, 2)); ?>

                        </td>
                        <td class="px-5 py-3.5"></td>
                        <td class="px-5 py-3.5 text-right font-mono text-amber-400">
                            <?php echo e(number_format($totalSeed, 2)); ?>

                        </td>
                        <td class="px-5 py-3.5"></td>
                        <td class="px-5 py-3.5 text-right font-mono text-emerald-300">
                            <?php echo e(number_format($totalValue, 2)); ?>

                            <span class="text-slate-600 font-normal text-[10px]"> ETB</span>
                        </td>
                        <td class="px-5 py-3.5"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/admin/reports/partials/gm-manufacturing.blade.php ENDPATH**/ ?>