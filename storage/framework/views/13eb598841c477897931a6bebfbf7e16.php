<div class="space-y-6">

<?php if($sellMode): ?>

<div class="space-y-6">

    
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-white">Sell Products</h2>
            <p class="text-slate-400 text-sm mt-1">Sell manufactured products to customers</p>
        </div>
        <a href="<?php echo e(route('admin.manufacturing.products')); ?>"
            class="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-semibold text-sm transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Manufacturing
        </a>
    </div>

    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        
        <div class="bg-slate-900/80 border border-slate-700/50 rounded-2xl overflow-hidden backdrop-blur-sm">
            <div class="px-6 py-4 bg-gradient-to-r from-emerald-900/30 to-slate-900 border-b border-slate-700/50 flex items-center gap-3">
                <div class="p-2 bg-emerald-500/20 rounded-lg">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-white">Add Item to Sale</h3>
            </div>

            <div class="p-6 space-y-4">

                
                <div class="relative z-50">
                    <label class="block text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wider">Search Item Name *</label>
                    <input type="text" wire:model.live="sellItemSearch" placeholder="Type product name..."
                        class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2.5 text-white text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors">
                    
                    
                    <?php if(!empty($sellItemSearch) && count($this->filteredSellProducts) > 0): ?>
                    <div class="absolute top-full left-0 right-0 mt-1 bg-slate-800 border border-slate-700 rounded-lg shadow-lg z-50 max-h-64 overflow-y-auto">
                        <?php $__currentLoopData = $this->filteredSellProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <button type="button" 
                                wire:click="$set('sellProductId', <?php echo e($product->id); ?>); $set('sellItemSearch', '')"
                                class="w-full text-left px-3 py-2 text-white text-sm hover:bg-emerald-600/20 transition-colors border-b border-slate-700/50 last:border-b-0">
                                <div class="font-medium"><?php echo e($product->name); ?></div>
                                <div class="text-xs text-emerald-400">Available: <?php echo e(number_format($product->available_qty, 3)); ?></div>
                            </button>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <?php endif; ?>
                </div>

                
                <div class="grid grid-cols-4 gap-4">
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wider">Item *</label>
                        <select wire:model.live="sellProductId"
                            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2.5 text-white text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors">
                            <option value="">— Select Item —</option>
                            <?php $__currentLoopData = $this->allSellProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($product->id); ?>" <?php echo e($sellProductId == $product->id ? 'selected' : ''); ?>>
                                    <?php echo e($product->name); ?> (<?php echo e(number_format($product->available_qty, 3)); ?>)
                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wider">Available</label>
                        <div class="w-full bg-slate-700/40 border border-slate-600/50 rounded-lg px-3 py-2.5 text-emerald-400 font-mono text-sm min-h-10 flex items-center">
                            <?php if($sellProductId && ($selectedProduct = \App\Models\Product::find($sellProductId))): ?>
                                <?php echo e(number_format($this->calculateBranchAvailableQty($selectedProduct, auth()->user()->assignedBranchIds()), 3)); ?>

                            <?php else: ?>
                                <span class="text-slate-500">—</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wider">Sell Qty *</label>
                        <input type="number" step="0.001" min="0.001" wire:model="sellQuantity"
                            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2.5 text-white font-mono text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors"
                            placeholder="0.000">
                    </div>

                    
                    <div class="flex flex-col justify-end">
                        <label class="block text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wider">&nbsp;</label>
                        <button type="button" wire:click="addSellItem" wire:loading.attr="disabled"
                            class="w-full bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-lg font-semibold text-sm transition-colors disabled:opacity-50">
                            <span wire:loading wire:target="addSellItem">Adding...</span>
                            <span wire:loading.remove wire:target="addSellItem">Add</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="bg-slate-900/80 border border-slate-700/50 rounded-2xl overflow-hidden backdrop-blur-sm">
            
            
            

            <?php if(!empty($salesItems)): ?>
            <div class="px-6 py-4 border-b border-slate-700/50">
                <h5 class="text-sm font-semibold text-slate-300 mb-2">Sales Cart</h5>
            </div>
            <div class="p-6 space-y-4">
                
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-700">
                            <th class="text-left pb-2 text-xs text-slate-400 font-semibold">Item Name</th>
                            <th class="text-right pb-2 text-xs text-slate-400 font-semibold">Qty</th>
                            <th class="text-right pb-2 text-xs text-slate-400 font-semibold">Unit Price</th>
                            <th class="text-right pb-2 text-xs text-slate-400 font-semibold">Total Price</th>
                            <th class="text-center pb-2 text-xs text-slate-400 font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <?php $__currentLoopData = $salesItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="py-3 text-white font-medium">
                                <?php echo e($item['name']); ?>

                                <?php if(($item['sell_type'] ?? '') === 'purchase'): ?>
                                <span class="ml-1 text-[10px] font-bold text-amber-400 bg-amber-900/30 px-1.5 py-0.5 rounded">Purchase</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 text-right text-slate-300 font-mono"><?php echo e($item['quantity']); ?></td>
                            <td class="py-3 text-right text-slate-300 font-mono"><?php echo e(number_format($item['unit_price'], 2)); ?></td>
                            <td class="py-3 text-right text-emerald-400 font-mono font-semibold"><?php echo e(number_format($item['total_price'], 2)); ?></td>
                            <td class="py-3 text-center">
                                <button type="button" wire:click="removeSellItem(<?php echo e($index); ?>)"
                                    class="text-red-400 hover:text-red-300 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>

                
                <div class="border-t border-slate-700 pt-4 flex justify-between items-center">
                    <span class="text-slate-400 font-semibold">Total Amount:</span>
                    <span class="text-emerald-400 font-bold text-xl font-mono"><?php echo e(number_format($this->getSellTotal(), 2)); ?> ETB</span>
                </div>

                
                <button type="button" wire:click="completeSale" wire:loading.attr="disabled"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-bold text-sm transition-colors mt-2 disabled:opacity-50">
                    <span wire:loading wire:target="completeSale">Processing...</span>
                    <span wire:loading.remove wire:target="completeSale">Complete Sale</span>
                </button>
            </div>
            <?php else: ?>
            <div class="p-12 text-center text-slate-500 flex flex-col items-center gap-3">
                <div class="p-4 bg-slate-800/50 rounded-full">
                    <svg class="w-10 h-10 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <p class="font-semibold">No items added yet</p>
                <p class="text-sm">Select a category and product on the left to start</p>
            </div>
            <?php endif; ?>
        </div>

    </div>

    
    <?php if(!empty($soldHistory)): ?>
    <div class="bg-slate-900/80 border border-slate-700/50 rounded-2xl overflow-hidden backdrop-blur-sm">

        
        <div class="px-6 py-4 border-b border-slate-700/50 flex items-center justify-between"
             style="background: linear-gradient(90deg, rgba(16,185,129,0.06) 0%, transparent 60%);">
            <div class="flex items-center gap-3">
                <div class="w-1.5 h-6 bg-emerald-500 rounded-full"></div>
                <div>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Sold Products</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Sales completed this session</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                
                <?php $sessionTotal = array_sum(array_column($soldHistory, 'total')); ?>
                <div class="px-4 py-2 bg-emerald-500/10 border border-emerald-500/20 rounded-xl">
                    <span class="text-xs text-slate-400">Session Total: </span>
                    <span class="text-sm font-bold text-emerald-400 font-mono"><?php echo e(number_format($sessionTotal, 2)); ?> ETB</span>
                </div>
                <div class="px-3 py-2 bg-slate-800/60 border border-slate-700/50 rounded-xl">
                    <span class="text-xs text-slate-400">Sales: </span>
                    <span class="text-sm font-bold text-white"><?php echo e(count($soldHistory)); ?></span>
                </div>
            </div>
        </div>

        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-800/40 border-b border-slate-700/50">
                        <th class="text-left px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">#</th>
                        <th class="text-left px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Item Name</th>
                        <th class="text-right px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Qty</th>
                        <th class="text-right px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Unit Price</th>
                        <th class="text-right px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Total Price</th>
                        <th class="text-left px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Sold At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/30">
                    <?php $rowNum = 1; ?>
                    <?php $__currentLoopData = $soldHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sale): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $__currentLoopData = $sale['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="hover:bg-slate-800/20 transition-colors">
                            <td class="px-6 py-3.5">
                                <span class="text-xs text-slate-500 font-mono"><?php echo e($rowNum++); ?></span>
                            </td>
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center shrink-0">
                                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                    <span class="text-sm font-semibold text-white"><?php echo e($item['name']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <span class="text-sm font-mono font-semibold text-slate-200"><?php echo e(number_format($item['quantity'], 3)); ?></span>
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <span class="text-sm font-mono text-slate-300"><?php echo e(number_format($item['unit_price'], 2)); ?></span>
                                <span class="text-xs text-slate-500 ml-1">ETB</span>
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <span class="text-sm font-mono font-bold text-emerald-400"><?php echo e(number_format($item['total_price'], 2)); ?></span>
                                <span class="text-xs text-slate-500 ml-1">ETB</span>
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="text-xs text-slate-400 font-mono bg-slate-800/50 px-2 py-1 rounded-md"><?php echo e($sale['sold_at']); ?></span>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                        
                        <?php if(count($sale['items']) > 1): ?>
                        <tr class="bg-slate-800/30">
                            <td colspan="4" class="px-6 py-2 text-right">
                                <span class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Sale Subtotal</span>
                            </td>
                            <td class="px-6 py-2 text-right">
                                <span class="text-sm font-mono font-bold text-blue-400"><?php echo e(number_format($sale['total'], 2)); ?> ETB</span>
                            </td>
                            <td class="px-6 py-2"></td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
                
                <tfoot>
                    <tr class="bg-gradient-to-r from-emerald-900/20 to-slate-900 border-t-2 border-emerald-500/30">
                        <td colspan="4" class="px-6 py-4 text-right">
                            <span class="text-sm font-bold text-white uppercase tracking-wide">Grand Total</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-lg font-bold text-emerald-400 font-mono"><?php echo e(number_format($sessionTotal, 2)); ?> ETB</span>
                        </td>
                        <td class="px-6 py-4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div>
    <?php endif; ?>
    

</div>

<?php else: ?>

<div class="flex gap-6 items-start">

    
    <div class="flex-1 min-w-0 space-y-6">

        
        <div>
            <h2 class="text-2xl font-bold text-white">Manufacturer Products</h2>
            <p class="text-slate-400 text-sm mt-1">Add manufactured products and manage raw material deductions.</p>
        </div>

        
        <div class="bg-slate-900/80 border border-slate-700/50 rounded-2xl overflow-hidden backdrop-blur-sm">
            <div class="px-6 py-4 bg-gradient-to-r from-emerald-900/30 to-slate-900 border-b border-slate-700/50 flex items-center gap-3">
                <div class="p-2 bg-emerald-500/20 rounded-lg">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-white">Product Information</h3>
                    <p class="text-xs text-slate-400">Select category, item and quantity</p>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Category *</label>
                        <select wire:model.live="salesCategoryId"
                            class="w-full bg-slate-800/50 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors">
                            <option value="">— Select Category —</option>
                            <?php $__empty_1 = true; $__currentLoopData = $this->salesCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <option value="<?php echo e($cat->id); ?>"><?php echo e($cat->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <option value="" disabled>No categories available</option>
                            <?php endif; ?>
                        </select>
                        <?php $__errorArgs = ['salesCategoryId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-xs text-red-400"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Item *</label>
                        <select wire:model.live="salesProductId"
                            class="w-full bg-slate-800/50 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors"
                            <?php if(!$salesCategoryId): echo 'disabled'; endif; ?>>
                            <option value="">— Select Item —</option>
                            <?php $__currentLoopData = $this->salesProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prod): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($prod->id); ?>"><?php echo e($prod->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['salesProductId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-xs text-red-400"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Product Qty *</label>
                        <div class="relative">
                            <input wire:model.live="salesQuantity" type="number" step="0.001" placeholder="0.000"
                                class="w-full bg-slate-800/50 border border-slate-600 rounded-xl px-3 py-2.5 pr-12 text-sm text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors font-mono">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-500">pcs</span>
                        </div>
                        <?php $__errorArgs = ['salesQuantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-xs text-red-400"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            
            <div class="bg-slate-900/80 border border-slate-700/50 rounded-2xl overflow-hidden backdrop-blur-sm">
                <div class="px-5 py-3 bg-gradient-to-r from-blue-900/30 to-slate-900 border-b border-slate-700/50 flex items-center gap-2">
                    <div class="p-1.5 bg-blue-500/20 rounded-lg">
                        <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-white">Bottle Item</h3>
                </div>
                <div class="p-5">
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Select Bottle</label>
                    <select wire:model.live="bottleId"
                        class="w-full bg-slate-800/50 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors">
                        <option value="">— Select Bottle —</option>
                        <?php $__empty_1 = true; $__currentLoopData = $this->bottleProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bottle): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <option value="<?php echo e($bottle->id); ?>"><?php echo e($bottle->name); ?> (Stock: <?php echo e(number_format($bottle->available_stock, 0)); ?>)</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <option value="" disabled>No bottles available</option>
                        <?php endif; ?>
                    </select>
                    <?php $__errorArgs = ['bottleId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mt-2 text-xs font-bold text-red-400 bg-red-500/10 border border-red-500/30 rounded-lg px-3 py-2"><?php echo e($message); ?></p>
                    <?php else: ?>
                    <p class="mt-2 text-xs text-slate-500">Deduction: Equals Product Qty</p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            
            <div class="bg-slate-900/80 border border-slate-700/50 rounded-2xl overflow-hidden backdrop-blur-sm">
                <div class="px-5 py-3 bg-gradient-to-r from-amber-900/30 to-slate-900 border-b border-slate-700/50 flex items-center gap-2">
                    <div class="p-1.5 bg-amber-500/20 rounded-lg">
                        <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-white">Available Seed <span class="text-red-400">*</span></h3>
                </div>
                <div class="p-5">
                    <?php if(!$salesCategoryId): ?>
                        <p class="text-sm text-slate-400">Select a category above to view available seeds</p>
                    <?php elseif($this->seedProducts->isEmpty()): ?>
                        <p class="text-sm text-slate-400">No seeds available for this category</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <?php $__currentLoopData = $this->seedProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $seed): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <button type="button" wire:click="$set('seedId', <?php echo e($seed->id); ?>)"
                                        class="w-full text-left p-3 rounded-lg border transition-all <?php echo e($seedId === $seed->id ? 'bg-amber-500/20 border-amber-500/50' : 'bg-slate-800/50 border-slate-700/50 hover:border-amber-500/30'); ?>">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <p class="text-sm font-semibold <?php echo e($seedId === $seed->id ? 'text-amber-300' : 'text-white'); ?>"><?php echo e($seed->name); ?></p>
                                                <p class="text-xs text-slate-500 mt-0.5">Available: <?php echo e(number_format($seed->available_stock ?? 0, 3)); ?></p>
                                            </div>
                                            <?php if($seedId === $seed->id): ?>
                                            <svg class="w-5 h-5 text-amber-400 ml-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                            <?php endif; ?>
                                        </div>
                                    </button>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>

                            
                            <div class="pt-2 border-t border-slate-700/50">
                                <?php if($seedId): ?>
                                    <p class="text-xs text-amber-300 font-semibold mb-2">Selected: <?php echo e($this->seedProducts->firstWhere('id', $seedId)?->name ?? 'Unknown Seed'); ?></p>
                                <?php endif; ?>
                                <label class="block text-xs font-medium text-slate-400 mb-1.5">Deduct Seed Qty</label>
                                <div class="relative">
                                    <input wire:model.live="seedQuantity" type="number" step="0.001" placeholder="0.000"
                                        class="w-full bg-slate-800/50 border border-slate-600 rounded-xl px-3 py-2.5 pr-12 text-sm text-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-colors font-mono"
                                        <?php if(!$seedId): ?> disabled <?php endif; ?>>
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-500">kg</span>
                                </div>
                                <?php $__errorArgs = ['seedQuantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="mt-2 text-xs font-bold text-red-400 bg-red-500/10 border border-red-500/30 rounded-lg px-3 py-2"><?php echo e($message); ?></p>
                                <?php else: ?>
                                <p class="mt-2 text-xs text-slate-500"><?php echo e($seedId ? 'Enter quantity to be consumed' : 'Select a seed above first'); ?></p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="bg-slate-900/80 border border-slate-700/50 rounded-2xl overflow-hidden backdrop-blur-sm">
                <div class="px-5 py-3 bg-gradient-to-r from-violet-900/30 to-slate-900 border-b border-slate-700/50 flex items-center gap-2">
                    <div class="p-1.5 bg-violet-500/20 rounded-lg">
                        <svg class="w-4 h-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-white">Packing Material <span class="text-red-400">*</span></h3>
                </div>
                <div class="p-5">
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Select Packing Material</label>
                    <select wire:model.live="packingId"
                        class="w-full bg-slate-800/50 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors">
                        <option value="">— Select Packing Material —</option>
                        <?php $__empty_1 = true; $__currentLoopData = $this->packingProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $packing): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <option value="<?php echo e($packing->id); ?>"><?php echo e($packing->name); ?> (Stock: <?php echo e(number_format($packing->available_stock, 0)); ?>)</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <option value="" disabled>No packing materials available</option>
                        <?php endif; ?>
                    </select>
                    <?php $__errorArgs = ['packingId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mt-2 text-xs font-bold text-red-400 bg-red-500/10 border border-red-500/30 rounded-lg px-3 py-2"><?php echo e($message); ?></p>
                    <?php else: ?>
                    <p class="mt-2 text-xs text-slate-500">Deduction: Equals Product Qty</p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

        </div>

        
        <div class="flex justify-end">
            <button type="button" wire:click="addSalesItem" wire:loading.attr="disabled"
                <?php if($salesProductId && $salesQuantity && $seedId && $packingId): ?> <?php else: ?> disabled <?php endif; ?>
                class="group inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white text-sm font-bold rounded-xl shadow-lg shadow-emerald-500/25 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg wire:loading wire:target="addSalesItem" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg wire:loading.remove wire:target="addSalesItem" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span wire:loading.remove wire:target="addSalesItem">Add to List</span>
                <span wire:loading wire:target="addSalesItem">Processing...</span>
            </button>
        </div>

        
        <div class="mb-6 p-5 bg-gradient-to-r from-slate-800/40 to-slate-900/40 border border-slate-700/50 rounded-xl">
            <div class="grid grid-cols-3 gap-6 items-center">
                
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500 mb-2">From Date</p>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500/70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <input type="date" wire:model.live="filterFromDate" class="bg-slate-800/50 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors font-mono">
                    </div>
                </div>

                
                <div class="flex justify-center">
                    <div class="flex items-center gap-2">
                        <div class="h-px flex-1 bg-gradient-to-r from-transparent to-slate-600"></div>
                        <svg class="w-5 h-5 text-slate-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                        <div class="h-px flex-1 bg-gradient-to-l from-transparent to-slate-600"></div>
                    </div>
                </div>

                
                <div class="text-right">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500 mb-2">To Date</p>
                    <div class="flex items-center justify-end gap-2">
                        <input type="date" wire:model.live="filterToDate" class="bg-slate-800/50 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors font-mono text-right">
                        <svg class="w-5 h-5 text-emerald-500/70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            
            <div class="mt-4 pt-4 border-t border-slate-700/30 flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500">Manufacturing Period Summary</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-center">
                        <p class="text-xs text-slate-500 mb-1">Items Manufactured</p>
                        <p class="text-2xl font-bold text-indigo-400"><?php echo e($this->manufacturediItems->count()); ?></p>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="bg-slate-900/80 border border-slate-700/50 rounded-2xl overflow-hidden backdrop-blur-sm">
            <div class="px-6 py-4 bg-gradient-to-r from-slate-800/50 to-slate-900 border-b border-slate-700/50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-slate-700/50 rounded-lg">
                        <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-white">Added Items</h3>
                        <p class="text-xs text-slate-400">Manufactured products list</p>
                    </div>
                </div>
                <?php
                    $totalQty = $this->manufacturediItems->sum(function($item) {
                        $meta = $item->metadata ?? [];
                        return isset($meta['last_mfr_quantity']) ? (float) $meta['last_mfr_quantity'] : 0;
                    });
                ?>
                <div class="px-4 py-2 bg-slate-800/50 rounded-lg">
                    <span class="text-xs text-slate-400">Total Qty:</span>
                    <span class="ml-2 text-lg font-bold text-emerald-400 font-mono"><?php echo e(rtrim(rtrim(number_format($totalQty, 3, '.', ''), '0'), '.')); ?></span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-800/30 border-b border-slate-700/50">
                            <th class="text-left px-6 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Item Name</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Category</th>
                            <th class="text-right px-6 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">New Mfr</th>
                            <th class="text-right px-6 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Manufactured Qty</th>
                            <th class="text-right px-6 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Used Qty</th>
                            <th class="text-right px-6 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Seed Used</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Username</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Branch</th>
                            <th class="text-center px-6 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/30">
                        <?php $__empty_1 = true; $__currentLoopData = $this->manufacturediItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $meta           = $item->metadata ?? [];
                            $mfgQty         = isset($meta['manufacturing_quantity']) ? $meta['manufacturing_quantity'] : 0;
                            $mfgQtyFormatted= rtrim(rtrim(number_format($mfgQty, 3, '.', ''), '0'), '.');
                            $mfgDate        = isset($meta['manufacturing_added_at'])
                                ? \Carbon\Carbon::parse($meta['manufacturing_added_at'])->format('Y-m-d H:i')
                                : '-';
                            
                            // Get the New Mfr quantity to check if we should display this row
                            $lastMfrQty = isset($meta['last_mfr_quantity']) ? (float)$meta['last_mfr_quantity'] : 0;
                        ?>
                        
                        
                        <?php if($lastMfrQty > 0): ?>
                        
                        <?php
                            // Get seed quantity for the MOST RECENT batch of this product
                            // Query FinishedGood → ProductionBatch → BatchMaterialConsumption
                            $seedQty        = null;
                            $seedUnit       = 'kg';
                            $seedName       = null;
                            
                            $latestFinished = \App\Models\FinishedGood::where('product_id', $item->id)
                                ->with(['batch.rawMaterialConsumptions'])
                                ->orderByDesc('id')
                                ->first();
                            
                            if ($latestFinished && $latestFinished->batch) {
                                // Find seed (raw material) consumption in this batch
                                $seedConsumption = $latestFinished->batch->rawMaterialConsumptions()
                                    ->first();
                                
                                if ($seedConsumption) {
                                    $seedQty = (float) $seedConsumption->getEffectiveQuantity();
                                    $seedName = $seedConsumption->rawMaterial?->name ?? null;
                                    $seedUnit = $seedConsumption->rawMaterial?->unit ?? 'kg';
                                }
                            }
                            
                            // Fallback to metadata if batch lookup fails
                            if ($seedQty === null && isset($meta['seed_quantity_used']) && $meta['seed_quantity_used'] > 0) {
                                $seedQty = (float) $meta['seed_quantity_used'];
                                $seedUnit = $meta['seed_unit'] ?? 'kg';
                                $seedName = $meta['seed_name'] ?? null;
                            }
                        ?>
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-white text-sm"><?php echo e($item->name); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-slate-700/50 text-slate-300 border border-slate-600/50">
                                    <?php echo e($item->category?->name ?? 'Unknown'); ?>

                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <?php
                                    // New Mfr should show ONLY the LAST entered quantity in "Product Qty *" form
                                    // NOT accumulated - just the most recent entry amount
                                    $lastMfrQtyDisplay = isset($meta['last_mfr_quantity']) ? (float)$meta['last_mfr_quantity'] : 0;
                                ?>
                                <span class="font-mono text-purple-400 font-semibold"><?php echo e(rtrim(rtrim(number_format($lastMfrQtyDisplay, 3, '.', ''), '0'), '.')); ?></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-mono text-blue-400 font-semibold"><?php echo e($this->getManufacturedQtyForUser($item)); ?></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <?php
                                    // Used Qty should show the current seed deduction (Deduct Seed Qty from form)
                                    $usedSeedQty = isset($meta['current_seed_deduction']) ? (float)$meta['current_seed_deduction'] : 0;
                                    $usedSeedUnit = $meta['seed_unit'] ?? 'kg';
                                ?>
                                <span class="font-mono text-orange-400 font-semibold">
                                    <?php echo e(rtrim(rtrim(number_format($usedSeedQty, 3, '.', ''), '0'), '.')); ?>

                                    <?php if($usedSeedUnit): ?>
                                    <span class="text-slate-500 text-xs font-normal"><?php echo e($usedSeedUnit); ?></span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <?php
                                    // Get seed quantity for THIS BRANCH ONLY
                                    $mfgBranchId = isset($meta['manufactured_by_branch_id']) ? (int)$meta['manufactured_by_branch_id'] : null;
                                    $branchSeedQty = null;
                                    $seedUnit = 'kg';
                                    $seedName = null;
                                    
                                    if ($mfgBranchId && isset($meta['seed_by_branch'][$mfgBranchId])) {
                                        $branchSeedQty = (float)$meta['seed_by_branch'][$mfgBranchId];
                                    } else {
                                        // Fallback to old metadata for backward compatibility
                                        $branchSeedQty = isset($meta['seed_quantity_used']) ? (float)$meta['seed_quantity_used'] : null;
                                    }
                                    
                                    $seedUnit = $meta['seed_unit'] ?? 'kg';
                                    $seedName = $meta['seed_name'] ?? null;
                                ?>
                                <?php if($branchSeedQty !== null && $branchSeedQty > 0): ?>
                                    <div class="flex flex-col items-end">
                                        <span class="font-mono text-amber-400 font-semibold">
                                            <?php echo e(rtrim(rtrim(number_format($branchSeedQty, 3, '.', ''), '0'), '.')); ?>

                                            <span class="text-slate-500 text-xs font-normal"> <?php echo e($seedUnit); ?></span>
                                        </span>
                                        <?php if($seedName): ?>
                                        <span class="text-[10px] text-slate-500 mt-0.5"><?php echo e($seedName); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-slate-600">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                    $mfgUsername = isset($meta['manufactured_by_username']) ? $meta['manufactured_by_username'] : 'Unknown';
                                ?>
                                <span class="text-slate-300 text-sm"><?php echo e($mfgUsername); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                    $mfgBranchId = isset($meta['manufactured_by_branch_id']) ? (int)$meta['manufactured_by_branch_id'] : null;
                                    $mfgBranch = $mfgBranchId ? \App\Models\Branch::find($mfgBranchId)?->name : 'Unknown';
                                ?>
                                <span class="text-slate-300 text-sm"><?php echo e($mfgBranch ?: '—'); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button wire:click="openEditSalesItemModal(<?php echo e($item->id); ?>)"
                                        class="p-2 rounded-lg bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 transition-colors"
                                        title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    
                                    
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="9" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="p-4 bg-slate-800/50 rounded-full">
                                        <svg class="w-8 h-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                    </div>
                                    <p class="text-slate-500 text-sm">No items added yet</p>
                                    <p class="text-slate-600 text-xs">Select category and item above to add products</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>


</div>


<?php endif; ?>



<?php if($showEditSalesItemModal): ?>
<div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
        <div class="flex items-center gap-3 pb-4 border-b border-slate-700">
            <div class="p-2 bg-blue-500/20 rounded-lg">
                <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-white">Edit Item</h3>
        </div>

        <?php $editingItem = $this->manufacturediItems->firstWhere('id', $editingProductId) ?>
        <?php if($editingItem): ?>
        <div class="flex items-center gap-3 p-3 bg-slate-800/50 rounded-xl">
            <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center shrink-0">
                <span class="text-emerald-400 font-bold text-sm"><?php echo e(substr($editingItem->name, 0, 2)); ?></span>
            </div>
            <div>
                <p class="text-sm font-semibold text-white"><?php echo e($editingItem->name); ?></p>
                <p class="text-xs text-slate-400"><?php echo e($editingItem->category?->name); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <div>
            <label class="block text-xs font-medium text-slate-400 mb-1.5">Item Name *</label>
            <input wire:model="editItemName" type="text" placeholder="Item name..."
                class="w-full bg-slate-800/50 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            <?php $__errorArgs = ['editItemName'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">New Mfr *</label>
                <input wire:model="editNewMfrQty" type="number" step="0.001" placeholder="0.000"
                    class="w-full bg-slate-800/50 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 font-mono">
                <?php $__errorArgs = ['editNewMfrQty'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Used Qty *</label>
                <input wire:model="editUsedQty" type="number" step="0.001" placeholder="0.000"
                    class="w-full bg-slate-800/50 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white focus:border-orange-500 focus:ring-1 focus:ring-orange-500 font-mono">
                <?php $__errorArgs = ['editUsedQty'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-400 mb-1.5">Reason for Update *</label>
            <textarea wire:model="editReason" rows="2" placeholder="Explain the changes..."
                class="w-full bg-slate-800/50 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 resize-none"></textarea>
            <?php $__errorArgs = ['editReason'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="flex justify-end gap-3 pt-4">
            <button wire:click="closeEditSalesItemModal"
                class="px-4 py-2 rounded-xl bg-slate-700 hover:bg-slate-600 text-slate-300 text-sm font-medium transition-colors">
                Cancel
            </button>
            <button wire:click="saveSalesItem" wire:loading.attr="disabled"
                class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold transition-colors disabled:opacity-50">
                <span wire:loading wire:target="saveSalesItem">Saving...</span>
                <span wire:loading.remove wire:target="saveSalesItem">Save Changes</span>
            </button>
        </div>
    </div>
</div>
<?php endif; ?>


<?php if($showDeleteConfirmModal): ?>
<div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
        <div class="flex items-center gap-4 pb-4 border-b border-slate-700">
            <div class="p-3 rounded-full bg-red-500/20">
                <svg class="w-6 h-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-white">Delete Item?</h3>
                <p class="text-sm text-slate-400">This action cannot be undone.</p>
            </div>
        </div>

        <?php $deletingItem = $this->manufacturediItems->firstWhere('id', $deletingProductId) ?>
        <?php if($deletingItem): ?>
        <div class="flex items-center gap-3 p-3 bg-slate-800/50 rounded-xl">
            <div class="w-10 h-10 rounded-lg bg-red-500/20 flex items-center justify-center shrink-0">
                <span class="text-red-400 font-bold text-sm"><?php echo e(substr($deletingItem->name, 0, 2)); ?></span>
            </div>
            <div>
                <p class="text-sm font-semibold text-white"><?php echo e($deletingItem->name); ?></p>
                <?php
                    $meta = $deletingItem->metadata ?? [];
                    $mfgQty = isset($meta['manufacturing_quantity']) ? $meta['manufacturing_quantity'] : 0;
                ?>
                <p class="text-xs text-slate-400">Qty: <?php echo e(rtrim(rtrim(number_format($mfgQty, 3, '.', ''), '0'), '.')); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <div>
            <label class="block text-xs font-medium text-slate-400 mb-1.5">Reason for Deletion *</label>
            <textarea wire:model="deleteReason" rows="2" placeholder="Explain why this item is being deleted..."
                class="w-full bg-slate-800/50 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white focus:border-red-500 focus:ring-1 focus:ring-red-500 resize-none"></textarea>
        </div>

        <div class="flex justify-end gap-3 pt-4">
            <button wire:click="closeDeleteConfirmModal"
                class="px-4 py-2 rounded-xl bg-slate-700 hover:bg-slate-600 text-slate-300 text-sm font-medium transition-colors">
                Cancel
            </button>
            <button wire:click="deleteSalesItem" wire:loading.attr="disabled"
                class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-white text-sm font-bold transition-colors disabled:opacity-50">
                <span wire:loading wire:target="deleteSalesItem">Deleting...</span>
                <span wire:loading.remove wire:target="deleteSalesItem">Delete</span>
            </button>
        </div>
    </div>
</div>
<?php endif; ?>


<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('notify', (event) => {
            if (typeof toastr !== 'undefined') {
                if (event[0]?.type === 'success') toastr.success(event[0].message);
                else if (event[0]?.type === 'error') toastr.error(event[0].message);
            }
        });
    });
</script>

</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/admin/manufacturing/user-dashboard.blade.php ENDPATH**/ ?>