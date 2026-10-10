

<div class="space-y-6">
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <div class="bg-gradient-to-br from-blue-900/40 to-blue-800/20 border border-blue-700/50 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-xs font-semibold uppercase tracking-wide">Total Stock Changes</p>
                    <p class="text-3xl font-bold text-blue-300 mt-2"><?php echo e(number_format($reportData['totalRecords'] ?? 0)); ?></p>
                    <p class="text-slate-500 text-xs mt-1">Logs recorded</p>
                </div>
                <div class="text-5xl opacity-20 text-blue-400">📊</div>
            </div>
        </div>

        
        <div class="bg-gradient-to-br from-emerald-900/40 to-emerald-800/20 border border-emerald-700/50 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-xs font-semibold uppercase tracking-wide">Items Added / Received</p>
                    <p class="text-3xl font-bold text-emerald-300 mt-2">+<?php echo e(number_format($reportData['inboundQty'] ?? 0, 0)); ?></p>
                    <p class="text-slate-500 text-xs mt-1">Total inbound qty</p>
                </div>
                <div class="text-5xl opacity-20 text-emerald-400">📈</div>
            </div>
        </div>

        
        <div class="bg-gradient-to-br from-red-900/40 to-red-800/20 border border-red-700/50 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-xs font-semibold uppercase tracking-wide">Items Deducted / Deleted</p>
                    <p class="text-3xl font-bold text-red-300 mt-2">-<?php echo e(number_format($reportData['outboundQty'] ?? 0, 0)); ?></p>
                    <p class="text-slate-500 text-xs mt-1">Total outbound / deleted qty</p>
                </div>
                <div class="text-5xl opacity-20 text-red-400">📉</div>
            </div>
        </div>

        
        <div class="bg-gradient-to-br from-purple-900/40 to-purple-800/20 border border-purple-700/50 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-xs font-semibold uppercase tracking-wide">Active Staff / Users</p>
                    <p class="text-3xl font-bold text-purple-300 mt-2"><?php echo e($reportData['activeStaffCount'] ?? 0); ?></p>
                    <p class="text-slate-500 text-xs mt-1">Users affecting stock</p>
                </div>
                <div class="text-5xl opacity-20 text-purple-400">👥</div>
            </div>
        </div>
    </div>

    
    <div class="bg-slate-900/50 border border-slate-800/60 rounded-2xl overflow-hidden">
        <div class="p-4 border-b border-slate-800/50">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <div>
                    <h3 class="text-slate-100 font-bold">Stock Changes & Audit Feed</h3>
                    <p class="text-slate-500 text-xs mt-0.5">Real-time log of all stock additions, deletions, updates, and transfers across all branches</p>
                </div>
            </div>
        </div>

        
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-800/50 border-b border-slate-700/50 sticky top-0">
                    <tr>
                        <th class="px-4 py-3 text-left text-slate-400 font-semibold text-xs uppercase tracking-wide">#</th>
                        <th class="px-4 py-3 text-left text-slate-400 font-semibold text-xs uppercase tracking-wide">User</th>
                        <th class="px-4 py-3 text-left text-slate-400 font-semibold text-xs uppercase tracking-wide">Branch</th>
                        <th class="px-4 py-3 text-left text-slate-400 font-semibold text-xs uppercase tracking-wide">Item / Subject</th>
                        <th class="px-4 py-3 text-left text-slate-400 font-semibold text-xs uppercase tracking-wide">Action / Change</th>
                        <th class="px-4 py-3 text-right text-slate-400 font-semibold text-xs uppercase tracking-wide">Qty Change</th>
                        <th class="px-4 py-3 text-left text-slate-400 font-semibold text-xs uppercase tracking-wide">Notes / Reference</th>
                        
                        
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    <?php $__empty_1 = true; $__currentLoopData = $reportData['movements'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $movement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $isInbound = $movement->direction === 'in';
                            $qtyDisplay = $isInbound ? '+' : '-';
                            $qtyColor = $isInbound ? 'text-emerald-400' : 'text-red-400';
                            $userName = $movement->user_name ?? 'System';
                            // Handle empty/null user names
                            if (empty($userName) || $userName === 'Unknown') {
                                $userName = 'System';
                            }
                            $userInitials = strtoupper(substr($userName, 0, 1) . (strpos($userName, ' ') !== false ? substr($userName, strpos($userName, ' ') + 1, 1) : ''));
                            
                            // Determine action label from movement type
                            $actionLabel = match($movement->movement_type) {
                                'purchase' => 'Purchased / Added',
                                'sale' => 'Sold / Deducted',
                                'transfer_in' => 'Transfer Received',
                                'transfer_out' => 'Transfer Sent',
                                'production_output' => 'Manufactured / Added',
                                'production_consumption' => 'Consumed in Production',
                                'packing_consumption' => 'Packing Consumed',
                                'adjustment_increase' => 'Stock Adjusted (Increase)',
                                'adjustment_decrease' => 'Stock Adjusted (Decrease)',
                                'purchase_return' => 'Purchase Return',
                                'sale_return' => 'Sale Return',
                                'distribution' => 'Distribution',
                                'manufactured_quantity_adjusted' => 'Manufactured Qty Edited',
                                'manufactured_quantity_deleted' => 'Manufactured Qty Deleted',
                                'seed_used_adjusted' => 'Seed Used Edited',
                                'seed_used_deleted' => 'Seed Used Deleted',
                                default => ucwords(str_replace('_', ' ', $movement->movement_type)),
                            };
                        ?>
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-4 py-3 text-slate-400 text-xs font-medium"><?php echo e($index + 1); ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-indigo-600 flex items-center justify-center">
                                        <span class="text-xs font-bold text-white"><?php echo e($userInitials); ?></span>
                                    </div>
                                    <div class="text-xs">
                                        <p class="text-slate-300 font-medium"><?php echo e($userName); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-lg">🏢</span>
                                    <span class="text-slate-300 text-xs"><?php echo e($movement->branch_name ?? 'All Branches'); ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-300 font-medium text-xs">
                                <?php echo e($movement->item_name ?? '[Prod ID: ' . ($movement->item_id ?? 'NULL') . ']'); ?>

                            </td>
                            <td class="px-4 py-3 text-slate-300 text-xs">
                                <?php echo e($actionLabel); ?>

                            </td>
                            <td class="px-4 py-3 text-right font-bold text-xs <?php echo e($qtyColor); ?>">
                                <?php echo e($qtyDisplay); ?><?php echo e(number_format(abs($movement->quantity ?? 0), 0)); ?>

                            </td>
                            <td class="px-4 py-3 text-slate-400 text-xs max-w-xs truncate" title="<?php echo e($movement->notes ?? '—'); ?>">
                                <?php echo e($movement->notes ?? '—'); ?>

                            </td>
                            
                            
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                                <p class="text-sm">No stock changes found for the selected period.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        
        <div class="px-4 py-3 bg-slate-800/30 border-t border-slate-800/50 text-xs text-slate-400 space-y-2">
            <div><?php echo e(number_format($reportData['totalRecords'] ?? 0)); ?> record(s)</div>
            <?php
                $movements = collect($reportData['movements'] ?? []);
                $prodOutput = $movements->where('movement_type', 'production_output')->count();
                $prodConsumption = $movements->where('movement_type', 'production_consumption')->count();
                $sales = $movements->where('movement_type', 'sale')->count();
            ?>
            <?php if($prodOutput > 0 || $prodConsumption > 0 || $sales > 0): ?>
                <div class="text-xs text-slate-500">
                    <?php if($prodOutput > 0): ?> <span class="text-emerald-400">📦 Production Output: <?php echo e($prodOutput); ?></span> <?php endif; ?>
                    <?php if($prodConsumption > 0): ?> <span class="text-amber-400">⚙️ Production Consumption: <?php echo e($prodConsumption); ?></span> <?php endif; ?>
                    <?php if($sales > 0): ?> <span class="text-blue-400">🛒 Sales: <?php echo e($sales); ?></span> <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="text-slate-500 italic">No production or sales movements in date range</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/admin/reports/partials/stock-changes.blade.php ENDPATH**/ ?>