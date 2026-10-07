<div class="space-y-6">

    
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-indigo-500/10 border border-indigo-500/20 rounded-2xl">
                <svg class="w-6 h-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Transfer Products</h1>
                <p class="text-slate-400 text-sm mt-0.5">Move stock between branches</p>
            </div>
        </div>
        <?php if(!empty($trHistory)): ?>
        <div class="px-3 py-2 bg-indigo-500/10 border border-indigo-500/20 rounded-xl text-xs text-indigo-400 font-semibold">
            <?php echo e(count($trHistory)); ?> transfer(s) this session
        </div>
        <?php endif; ?>
    </div>

    
    <div class="bg-slate-900/80 border border-slate-700/50 rounded-2xl overflow-hidden backdrop-blur-sm">
        <div class="px-6 py-4 border-b border-slate-700/50"
             style="background: linear-gradient(90deg, rgba(99,102,241,0.07) 0%, transparent 60%);">
            <div class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-indigo-500/20 border border-indigo-500/30 text-indigo-400 text-xs font-bold flex items-center justify-center shrink-0">1</span>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Select Branches</h3>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-center">

                
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-red-400 inline-block"></span>
                            From (Source) <span class="text-red-400">*</span>
                        </span>
                    </label>
                    <div class="relative">
                        <select wire:model.live="srcBranchId"
                            class="w-full appearance-none bg-slate-800/80 border border-slate-600/70 rounded-xl px-4 py-3 pr-10 text-white text-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 outline-none transition-all">
                            <option value="">— Select Source Branch —</option>
                            <?php $__currentLoopData = $this->allBranches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $br): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($br->id); ?>"><?php echo e($br->name); ?>

                                    <?php if($br->business): ?> — <?php echo e($br->business->name); ?> <?php endif; ?>
                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                    <?php $__errorArgs = ['srcBranchId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                
                <div class="flex justify-center">
                    <div class="flex flex-col items-center gap-1">
                        <svg class="w-8 h-8 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                        <?php if($srcBranchId && $destBranchId): ?>
                        <span class="text-[10px] text-indigo-400 font-semibold uppercase tracking-wider">Transfer</span>
                        <?php endif; ?>
                    </div>
                </div>

                
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block"></span>
                            To (Destination) <span class="text-emerald-400">*</span>
                        </span>
                    </label>
                    <div class="relative">
                        <select wire:model.live="destBranchId"
                            class="w-full appearance-none bg-slate-800/80 border border-slate-600/70 rounded-xl px-4 py-3 pr-10 text-white text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none transition-all disabled:opacity-40"
                            <?php if(!$srcBranchId): echo 'disabled'; endif; ?>>
                            <option value="">— Select Destination Branch —</option>
                            <?php $__currentLoopData = $this->destBranches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $br): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($br->id); ?>"><?php echo e($br->name); ?>

                                    <?php if($br->business): ?> — <?php echo e($br->business->name); ?> <?php endif; ?>
                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                    <?php $__errorArgs = ['destBranchId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

            </div>

            
            <?php if($srcBranchId && $destBranchId): ?>
            <?php
                $srcB = $this->allBranches->firstWhere('id', $srcBranchId);
                $dstB = $this->destBranches->firstWhere('id', $destBranchId);
            ?>
            <div class="mt-4 flex items-center gap-3 px-4 py-2.5 bg-slate-800/50 border border-slate-700/40 rounded-xl">
                <span class="text-xs px-2.5 py-1 bg-red-500/10 border border-red-500/20 rounded-lg text-red-300 font-semibold">
                    <?php echo e($srcB?->name); ?>

                </span>
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                <span class="text-xs px-2.5 py-1 bg-emerald-500/10 border border-emerald-500/20 rounded-lg text-emerald-300 font-semibold">
                    <?php echo e($dstB?->name); ?>

                </span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    
    <?php if($srcBranchId): ?>
    <div class="bg-slate-900/80 border border-slate-700/50 rounded-2xl overflow-hidden backdrop-blur-sm">
        <div class="px-6 py-4 border-b border-slate-700/50"
             style="background: linear-gradient(90deg, rgba(99,102,241,0.07) 0%, transparent 60%);">
            <div class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-indigo-500/20 border border-indigo-500/30 text-indigo-400 text-xs font-bold flex items-center justify-center shrink-0">2</span>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Select Product</h3>
                <span class="text-xs text-slate-500">— from source branch stock</span>
            </div>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-5">

            
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                    Category <span class="text-indigo-400">*</span>
                </label>
                <div class="relative">
                    <select wire:model.live="trCategoryId"
                        class="w-full appearance-none bg-slate-800/80 border border-slate-600/70 rounded-xl px-4 py-3 pr-10 text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all">
                        <option value="">— Select Category —</option>
                        <?php $__empty_1 = true; $__currentLoopData = $this->categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <option value="<?php echo e($cat->id); ?>"><?php echo e($cat->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <option value="" disabled>No stock at this branch</option>
                        <?php endif; ?>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
                <?php $__errorArgs = ['trCategoryId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                    Item <span class="text-indigo-400">*</span>
                </label>
                <div class="relative">
                    <select wire:model.live="trProductId"
                        class="w-full appearance-none bg-slate-800/80 border border-slate-600/70 rounded-xl px-4 py-3 pr-10 text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all disabled:opacity-40"
                        <?php if(!$trCategoryId): echo 'disabled'; endif; ?>>
                        <option value="">— Select Item —</option>
                        <?php $__currentLoopData = $this->products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prod): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($prod->id); ?>"><?php echo e($prod->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
                <?php $__errorArgs = ['trProductId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Available at Source</label>
                    <div class="flex items-center justify-between px-4 py-3 rounded-xl border h-[46px]
                        <?php echo e($trProductId ? 'bg-slate-800/50 border-emerald-500/20' : 'bg-slate-800/30 border-slate-700/50'); ?>">
                        <?php if($trProductId): ?>
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></div>
                                <span class="text-xs text-slate-400">In stock</span>
                            </div>
                            <span class="text-base font-bold text-emerald-400 font-mono">
                                <?php echo e(number_format($this->availableQty(), 3)); ?>

                                <span class="text-xs text-slate-500 font-normal ml-1">units</span>
                            </span>
                        <?php else: ?>
                            <span class="text-sm text-slate-600">— select item —</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if($trProductId): ?>
                <?php $sp = \App\Models\Product::withoutGlobalScopes()->find($trProductId)?->selling_price ?? 0; ?>
                <div class="flex items-center justify-between px-4 py-2 bg-slate-800/30 border border-slate-700/40 rounded-xl">
                    <span class="text-xs text-slate-400">Selling Price</span>
                    <span class="text-sm font-mono font-bold text-indigo-300"><?php echo e(number_format($sp, 2)); ?> ETB</span>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
    <?php endif; ?>

    
    <?php if($trProductId && $destBranchId): ?>
    <div class="bg-slate-900/80 border border-slate-700/50 rounded-2xl overflow-hidden backdrop-blur-sm">
        <div class="px-6 py-4 border-b border-slate-700/50"
             style="background: linear-gradient(90deg, rgba(99,102,241,0.07) 0%, transparent 60%);">
            <div class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-indigo-500/20 border border-indigo-500/30 text-indigo-400 text-xs font-bold flex items-center justify-center shrink-0">3</span>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Quantity & Notes</h3>
            </div>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

            
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                    Transfer Qty <span class="text-indigo-400">*</span>
                </label>
                <div class="relative">
                    <input type="number" step="0.001" min="0.001"
                        max="<?php echo e($this->availableQty()); ?>"
                        wire:model="trQuantity"
                        class="w-full bg-slate-800/80 border border-slate-600/70 rounded-xl px-4 py-3.5 pr-16 text-white font-mono text-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all"
                        placeholder="0.000">
                    <div class="absolute inset-y-0 right-0 flex items-center px-4 bg-slate-700/50 rounded-r-xl border-l border-slate-600/70">
                        <span class="text-xs text-slate-400 font-semibold">units</span>
                    </div>
                </div>
                <?php $__errorArgs = ['trQuantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1.5"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                    Notes <span class="text-indigo-400">*</span>
                    <span class="text-slate-500 font-normal normal-case ml-1">(reason)</span>
                </label>
                <textarea wire:model="trNotes" rows="2"
                    class="w-full bg-slate-800/80 border border-slate-600/70 rounded-xl px-4 py-3 text-sm text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all resize-none"
                    placeholder="e.g. Monthly stock allocation…"></textarea>
                <?php $__errorArgs = ['trNotes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1.5"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

        </div>
    </div>

    <div class="flex justify-end">
        <button type="button" wire:click="executeTransfer" wire:loading.attr="disabled"
            class="inline-flex items-center gap-2.5 px-8 py-3.5 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-500/20 transition-all duration-200 disabled:opacity-60">
            <svg wire:loading wire:target="executeTransfer" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <svg wire:loading.remove wire:target="executeTransfer" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            <span wire:loading.remove wire:target="executeTransfer">Transfer Now</span>
            <span wire:loading wire:target="executeTransfer">Transferring…</span>
        </button>
    </div>
    <?php endif; ?>

    
    <?php if(!empty($trHistory)): ?>
    <div class="bg-slate-900/80 border border-indigo-500/20 rounded-2xl overflow-hidden backdrop-blur-sm">
        <div class="px-6 py-4 border-b border-slate-700/50 flex items-center justify-between"
             style="background: linear-gradient(90deg, rgba(99,102,241,0.07) 0%, transparent 60%);">
            <div class="flex items-center gap-3">
                <div class="w-1.5 h-6 bg-indigo-500 rounded-full"></div>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Transfer History</h3>
                <span class="text-xs text-slate-500">this session</span>
            </div>
            <span class="px-3 py-1 bg-indigo-500/10 border border-indigo-500/20 rounded-xl text-sm font-bold text-indigo-400">
                <?php echo e(count($trHistory)); ?>

            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-800/40 border-b border-slate-700/50">
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">#</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Category</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Item</th>
                        <th class="text-right px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Qty</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">From</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">To</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Notes</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/30">
                    <?php $__currentLoopData = $trHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $tr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="hover:bg-slate-800/20 transition-colors">
                        <td class="px-5 py-3.5 text-xs text-slate-500 font-mono"><?php echo e($i + 1); ?></td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2.5 py-1 bg-slate-800/60 border border-slate-700/50 rounded-lg text-xs text-slate-300">
                                <?php echo e($tr['category']); ?>

                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-sm font-semibold text-white"><?php echo e($tr['product']); ?></td>
                        <td class="px-5 py-3.5 text-right text-sm font-mono font-bold text-indigo-400">
                            <?php echo e(number_format($tr['qty'], 3)); ?>

                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2.5 py-1 bg-red-500/10 border border-red-500/20 rounded-lg text-xs text-red-300 font-medium">
                                <?php echo e($tr['from']); ?>

                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2.5 py-1 bg-emerald-500/10 border border-emerald-500/20 rounded-lg text-xs text-emerald-300 font-medium">
                                <?php echo e($tr['to']); ?>

                            </span>
                        </td>
                        <td class="px-5 py-3.5 max-w-xs text-sm text-slate-300"><?php echo e($tr['notes']); ?></td>
                        <td class="px-5 py-3.5 text-xs text-slate-400 font-mono whitespace-nowrap"><?php echo e($tr['at']); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    
    <div class="bg-slate-900/80 border border-slate-700/50 rounded-2xl overflow-hidden backdrop-blur-sm">
        <div class="px-6 py-4 border-b border-slate-700/50"
             style="background: linear-gradient(90deg, rgba(99,102,241,0.07) 0%, transparent 60%);">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-1.5 h-6 bg-purple-500 rounded-full"></div>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">📦 All Transferred Items</h3>
                    <span class="px-2.5 py-1 bg-purple-500/10 border border-purple-500/20 rounded-xl text-xs font-bold text-purple-400">
                        <?php echo e($this->transferHistory->count()); ?> records
                    </span>
                </div>
            </div>

            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Filter by Branch</label>
                    <select wire:model.live="searchBranch"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:border-purple-500 outline-none transition">
                        <option value="">All Branches</option>
                        <?php $__currentLoopData = $this->filterBranches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($name); ?>"><?php echo e($name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Filter by Category</label>
                    <select wire:model.live="searchCategory"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:border-purple-500 outline-none transition">
                        <option value="">All Categories</option>
                        <?php $__currentLoopData = $this->filterCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($name); ?>"><?php echo e($name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Filter by Item</label>
                    <select wire:model.live="searchItem"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:border-purple-500 outline-none transition">
                        <option value="">All Items</option>
                        <?php $__currentLoopData = $this->filterItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($name); ?>"><?php echo e($name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            </div>
            <?php if($searchBranch || $searchCategory || $searchItem): ?>
            <div class="mt-2">
                <button wire:click="$set('searchBranch',''); $set('searchCategory',''); $set('searchItem','')"
                    class="text-xs text-purple-400 hover:text-purple-300 underline">✕ Clear filters</button>
            </div>
            <?php endif; ?>
        </div>

        <?php if($this->transferHistory->isEmpty()): ?>
        <div class="py-16 text-center">
            <div class="w-16 h-16 bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
            <p class="text-slate-500 text-sm">No transfer records found</p>
            <?php if($searchBranch || $searchCategory || $searchItem): ?>
            <button wire:click="$set('searchBranch',''); $set('searchCategory',''); $set('searchItem','')"
                class="mt-3 text-xs text-purple-400 hover:text-purple-300 underline">Clear filters</button>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-800/40 border-b border-slate-700/50">
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">#</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Category</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Item</th>
                        <th class="text-right px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Qty</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">From Branch</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">To Branch</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Notes</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/30">
                    <?php $__currentLoopData = $this->transferHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $tr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="hover:bg-slate-800/20 transition-colors">
                        <td class="px-5 py-3 text-xs text-slate-500 font-mono"><?php echo e($i + 1); ?></td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 bg-slate-800 border border-slate-700 rounded-lg text-xs text-slate-300">
                                <?php echo e($tr->category_name); ?>

                            </span>
                        </td>
                        <td class="px-5 py-3 text-sm font-semibold text-white"><?php echo e($tr->product_name); ?></td>
                        <td class="px-5 py-3 text-right font-mono font-bold text-indigo-400 text-sm">
                            <?php echo e(number_format($tr->quantity, 2)); ?>

                        </td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 bg-red-500/10 border border-red-500/20 rounded-lg text-xs text-red-300">
                                <?php echo e($tr->from_branch); ?>

                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <div>
                                <span class="px-2 py-0.5 bg-emerald-500/10 border border-emerald-500/20 rounded-lg text-xs text-emerald-300">
                                    <?php echo e($tr->to_branch); ?>

                                </span>
                                <div class="text-[10px] text-slate-500 mt-0.5"><?php echo e($tr->to_business); ?></div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-400 max-w-[150px] truncate"><?php echo e($tr->user_notes ?: '—'); ?></td>
                        <td class="px-5 py-3 text-xs text-slate-400 font-mono whitespace-nowrap">
                            <?php echo e($tr->moved_at ? \Carbon\Carbon::parse($tr->moved_at)->format('d M Y H:i') : '—'); ?>

                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('notify', (event) => {
                if (typeof toastr !== 'undefined') {
                    const e = event[0] ?? event;
                    if (e.type === 'success') toastr.success(e.message);
                    else if (e.type === 'error') toastr.error(e.message);
                }
            });
        });
    </script>

</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/admin/purchases/product-transfer.blade.php ENDPATH**/ ?>