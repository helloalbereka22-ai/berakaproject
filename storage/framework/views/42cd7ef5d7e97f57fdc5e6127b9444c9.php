<div class="flex gap-6 items-start">
<div class="space-y-6">

    
    <?php if(session('success')): ?>
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2"
            class="flex items-center gap-2 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2"
            class="flex items-center gap-2 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>

    
    <div class="bg-slate-900 border border-slate-800/90 rounded-2xl p-5 shadow-xl space-y-5">
        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500">Record New Purchase</p>

        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-<?php echo e($this->isBookstoreBusiness() ? '3' : '4'); ?> gap-4">

            
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">
                    Business <span class="text-red-400">*</span>
                </label>
                <select wire:model.live="business_id"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-semibold text-white focus:border-indigo-500 outline-none transition">
                    <option value="">— Select Business —</option>
                    <?php $__currentLoopData = $businesses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($b['id']); ?>" <?php if($business_id == $b['id']): echo 'selected'; endif; ?>><?php echo e($b['name']); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['business_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">
                    Branch <span class="text-red-400">*</span>
                </label>
                <select wire:model.live="branch_id"
                    <?php if(empty($branches)): ?> disabled <?php endif; ?>
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-semibold text-white focus:border-indigo-500 outline-none transition disabled:opacity-40">
                    <option value=""><?php echo e(!$business_id ? '— Select business first —' : '— Select Branch —'); ?></option>
                    <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $br): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($br['id']); ?>" <?php if($branch_id == $br['id']): echo 'selected'; endif; ?>><?php echo e($br['name']); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['branch_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <?php if(!$this->isBookstoreBusiness()): ?>
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">
                    Type <span class="text-red-400">*</span>
                </label>
                <select wire:model.live="type"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none transition">
                    <option value="product">Product</option>
                    <option value="purchase">Purchase</option>
                </select>
                <?php $__errorArgs = ['type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <?php else: ?>
            
            <input type="hidden" wire:model="type" value="purchase">
            <?php endif; ?>

            
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">
                    Category <span class="text-red-400">*</span>
                </label>
                <select wire:model.live="category_id"
                    <?php if(empty($categories)): ?> disabled <?php endif; ?>
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-semibold text-white focus:border-indigo-500 outline-none transition disabled:opacity-40">
                    <option value=""><?php echo e(!$business_id ? '— Select business first —' : '— Select Category —'); ?></option>
                    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($c['id']); ?>" <?php if($category_id == $c['id']): echo 'selected'; endif; ?>><?php echo e($c['name']); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>

        
        <div class="grid grid-cols-1 gap-4">
            
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">
                    Item <span class="text-red-400">*</span>
                </label>
                <select wire:model.live="product_id"
                    <?php if(empty($products)): ?> disabled <?php endif; ?>
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-semibold text-white focus:border-indigo-500 outline-none transition disabled:opacity-40">
                    <option value=""><?php echo e(!$category_id ? '— Select category first —' : (empty($products) ? '— No items found —' : '— Select Item —')); ?></option>
                    <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($p['id']); ?>" <?php if($product_id == $p['id']): echo 'selected'; endif; ?>><?php echo e($p['name']); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['product_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>

        
        <?php if($type === 'product' && $product_id): ?>
        <div class="grid grid-cols-1 gap-4">
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">
                    Total Manufactured Qty
                    <span class="text-xs text-slate-500">(Added by Manufacturer)</span>
                </label>
                <div class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-mono text-emerald-400 border-emerald-500/30">
                    <?php echo e(number_format($this->getManufacturedQuantity(), 3)); ?> units
                </div>
                <p class="text-xs text-slate-500 mt-1">This shows the total quantity manufactured for the selected item.</p>
            </div>
        </div>
        <?php endif; ?>

        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-4 border-t border-slate-800">
            <?php if(!$this->shouldHideQuantity()): ?>
            
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">
                    Quantity <span class="text-red-400">*</span>
                </label>
                <input 
                    wire:model.blur="quantity" 
                    type="number" 
                    step="0.001" 
                    min="0" 
                    placeholder="Enter quantity" 
                    autocomplete="off"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-mono text-white focus:border-indigo-500 outline-none transition">
                
                <?php $__errorArgs = ['quantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">
                    Unit <span class="text-red-400">*</span>
                </label>
                <select wire:model="unit"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:border-indigo-500 outline-none transition">
                    <option value="">— Select Unit —</option>
                    <?php $__currentLoopData = $unitOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($u); ?>" <?php if($unit === $u): echo 'selected'; endif; ?>><?php echo e(ucfirst($u)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['unit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <?php endif; ?>

            
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">
                    Purchase Price (ETB) <span class="text-red-400">*</span>
                </label>
                <input wire:model="unit_price" type="number" step="0.01" min="0" placeholder="0.00" autocomplete="off"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-mono text-white focus:border-indigo-500 outline-none transition">
                <?php $__errorArgs = ['unit_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">
                    Selling Price (ETB)
                    <?php if($type === 'purchase' && !$this->isBookstoreBusiness()): ?>
                        <span class="text-slate-500 font-normal">(Optional)</span>
                    <?php else: ?>
                        <span class="text-red-400">*</span>
                    <?php endif; ?>
                    <?php if((float)$unit_price > 0 && $selling_price !== ''): ?>
                        <span class="text-slate-600 font-normal">≥ <?php echo e(number_format((float)$unit_price, 2)); ?></span>
                    <?php endif; ?>
                </label>
                <input wire:model="selling_price" type="number" step="0.01" min="0" autocomplete="off"
                    placeholder="<?php echo e(($type === 'purchase' && !$this->isBookstoreBusiness()) ? 'Optional' : '0.00'); ?>"
                    class="w-full bg-slate-950 border <?php echo e($sellingPriceError ? 'border-red-500' : 'border-slate-700'); ?> rounded-xl px-4 py-2.5 text-sm font-mono text-white focus:border-indigo-500 outline-none transition">
                <?php if($sellingPriceError): ?>
                    <p class="text-red-400 text-xs mt-1"><?php echo e($sellingPriceError); ?></p>
                <?php endif; ?>
                <?php $__errorArgs = ['selling_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>

        
        <div class="flex justify-end pt-4 border-t border-slate-800">
            <button type="button" wire:click="save" wire:loading.attr="disabled"
                class="px-6 py-2.5 rounded-xl text-sm font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/25 transition flex items-center gap-2 disabled:opacity-50 cursor-pointer">
                <span wire:loading.remove wire:target="save" class="flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Save Purchase
                </span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>
    </div>

    
    <div class="bg-slate-900 border border-slate-800/90 rounded-2xl p-4 shadow-xl">
        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500 mb-3">Products List</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

            
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Business</label>
                <select wire:model.live="filterBusinessId"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:border-indigo-500 outline-none transition">
                    <option value="">— All Businesses —</option>
                    <?php $__currentLoopData = $businesses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($b['id']); ?>"><?php echo e($b['name']); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Branch</label>
                <select wire:model.live="filterBranchId"
                    <?php if(empty($filterBranches)): ?> disabled <?php endif; ?>
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:border-indigo-500 outline-none transition disabled:opacity-40">
                    <option value=""><?php echo e(!$filterBusinessId ? '— Select business first —' : '— All Branches —'); ?></option>
                    <?php $__currentLoopData = $filterBranches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $br): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($br['id']); ?>"><?php echo e($br['name']); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Category</label>
                <select wire:model.live="filterCategoryId"
                    <?php if(empty($filterCategories)): ?> disabled <?php endif; ?>
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:border-indigo-500 outline-none transition disabled:opacity-40">
                    <option value=""><?php echo e(!$filterBusinessId ? '— Select business first —' : '— All Categories —'); ?></option>
                    <?php $__currentLoopData = $filterCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($c['id']); ?>"><?php echo e($c['name']); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Item</label>
                <select wire:model.live="filterProductId"
                    <?php if(empty($filterProducts)): ?> disabled <?php endif; ?>
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:border-indigo-500 outline-none transition disabled:opacity-40">
                    <option value=""><?php echo e(!$filterCategoryId ? '— Select category first —' : '— All Items —'); ?></option>
                    <?php $__currentLoopData = $filterProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($p['id']); ?>"><?php echo e($p['name']); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

        </div>
        <?php if($filterBusinessId || $filterBranchId || $filterCategoryId || $filterProductId): ?>
            <div class="mt-3 flex justify-end">
                <button type="button" wire:click="clearFilters"
                    class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold transition flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Clear filters
                </button>
            </div>
        <?php endif; ?>
    </div>

    
    <div class="bg-slate-900 border border-slate-800/90 rounded-2xl shadow-xl overflow-hidden">
        <?php if($purchases->count() > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-800 bg-slate-800/20 text-[11px] uppercase tracking-widest text-slate-400">
                            <th class="px-5 py-3 text-left font-bold">Branch</th>
                            <th class="px-5 py-3 text-left font-bold">Category</th>
                            <th class="px-5 py-3 text-left font-bold">Item Name</th>
                            <th class="px-5 py-3 text-right font-bold">Total Qty</th>
                            <th class="px-5 py-3 text-right font-bold">Unit Price</th>
                            <th class="px-5 py-3 text-right font-bold">Selling Price</th>
                            <th class="px-5 py-3 text-right font-bold">Total Price</th>
                            <th class="px-5 py-3 text-left font-bold">Date</th>
                            <th class="px-5 py-3 text-center font-bold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        <?php $__currentLoopData = $purchases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $purchase): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                // Use total_purchase_qty if available (grouped), otherwise use single purchase quantity
                                $purchaseQty = (float) ($purchase->total_purchase_qty ?? $purchase->quantity);
                                $unitPrice = (float) $purchase->unit_price;
                                $purchaseCount = (int) ($purchase->purchase_count ?? 1);
                                
                                // For product type, add manufactured quantity
                                if ($purchase->purchase_type === 'product') {
                                    $product = $purchase->product;
                                    $manufacturingQty = 0;
                                    if ($product && $product->metadata) {
                                        $manufacturingQty = (float) ($product->metadata['manufacturing_quantity'] ?? 0);
                                    }
                                    $totalQty = $purchaseQty + $manufacturingQty;
                                } else {
                                    $totalQty = $purchaseQty;
                                }
                                
                                $totalPrice = $purchaseQty * $unitPrice;
                            ?>
                            <tr wire:key="pur-<?php echo e($purchase->id); ?>" class="hover:bg-slate-800/40 transition">
                                <td class="px-5 py-3 text-slate-400 text-xs"><?php echo e($purchase->branch->name ?? '—'); ?></td>
                                <td class="px-5 py-3 text-slate-400 text-xs"><?php echo e($purchase->category->name ?? 'Unknown'); ?></td>
                                <td class="px-5 py-3">
                                    <p class="font-semibold text-white text-sm"><?php echo e($purchase->product->name ?? '—'); ?></p>
                                    
                                    <?php if($purchaseCount > 1): ?>
                                    <p class="text-[10px] text-slate-500 mt-0.5">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="px-1.5 py-0.5 bg-orange-500/20 border border-orange-500/30 rounded text-orange-400 font-semibold"><?php echo e($purchaseCount); ?>x</span>
                                            purchased
                                        </span>
                                    </p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3 text-right font-mono text-white text-xs">
                                    <?php if($purchase->purchase_type === 'product' && $manufacturingQty > 0): ?>
                                        
                                        <div class="flex flex-col items-end gap-0.5">
                                            <span class="text-emerald-400 font-bold"><?php echo e(number_format($purchaseQty, 0)); ?></span>
                                            <span class="text-[10px] text-slate-500">purchased</span>
                                        </div>
                                    <?php else: ?>
                                        
                                        <div class="flex flex-col items-end gap-0.5">
                                            <span class="text-indigo-300 font-semibold"><?php echo e(rtrim(rtrim(number_format($totalQty, 2, '.', ''), '0'), '.')); ?></span>
                                            <span class="text-[10px] text-slate-500"><?php echo e($purchase->unit); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3 text-right font-mono text-white text-xs"><?php echo e(number_format($unitPrice, 2)); ?></td>
                                <td class="px-5 py-3 text-right font-mono text-xs">
                                    <?php $sp = (float)($purchase->product->selling_price ?? 0); ?>
                                    <?php if($sp > 0): ?>
                                        <span class="text-indigo-300 font-bold"><?php echo e(number_format($sp, 2)); ?></span>
                                    <?php else: ?>
                                        <span class="text-slate-500 italic text-[10px]">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3 text-right font-mono text-emerald-400 text-xs font-bold"><?php echo e(number_format($totalPrice, 2)); ?></td>
                                <td class="px-5 py-3 text-slate-400 text-xs"><?php echo e($purchase->purchase_date->format('Y-m-d')); ?></td>
                                <td class="px-5 py-3 text-center">
                                    <?php
                                        // For oil products (purchase_type = 'product' with mfg qty), disable edit/delete
                                        $isOilProduct = $purchase->purchase_type === 'product' && $manufacturingQty > 0;
                                    ?>
                                    <div class="flex items-center justify-center gap-2">
                                        
                                        <button type="button" 
                                            wire:click="openEdit(<?php echo e($purchase->id); ?>)"
                                            wire:loading.attr="disabled"
                                            <?php if($isOilProduct): echo 'disabled'; endif; ?>
                                            class="p-1.5 rounded-lg bg-blue-600/20 hover:bg-blue-600/40 text-blue-400 transition disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-blue-600/20" 
                                            title="<?php echo e($isOilProduct ? 'Cannot edit manufactured oil products' : 'Edit Purchase'); ?>">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        
                                        
                                        <button type="button" 
                                            wire:click="confirmDelete(<?php echo e($purchase->id); ?>)"
                                            wire:loading.attr="disabled"
                                            <?php if($isOilProduct): echo 'disabled'; endif; ?>
                                            class="p-1.5 rounded-lg bg-red-600/20 hover:bg-red-600/40 text-red-400 transition disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-red-600/20" 
                                            title="<?php echo e($isOilProduct ? 'Cannot delete manufactured oil products' : 'Delete Purchase'); ?>">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-slate-800 bg-slate-950/30">
                <?php echo e($purchases->links()); ?>

            </div>
        <?php else: ?>
            <div class="py-16 text-center text-slate-500 text-sm">
                <svg class="w-10 h-10 mx-auto mb-3 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <p class="font-semibold text-slate-400">No products recorded yet.</p>
                <p class="text-xs text-slate-600 mt-1">Use the form above to record products.</p>
            </div>
        <?php endif; ?>
    </div>

    
    <?php if($showEditModal): ?>
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
             wire:click.self="closeEdit">
            <div class="w-full max-w-2xl bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] overflow-y-auto">

                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800 bg-slate-950/60 sticky top-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-indigo-600/20 text-indigo-400 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </div>
                        <h3 class="text-base font-bold text-white">Edit Purchase</h3>
                    </div>
                    <button type="button" wire:click="closeEdit" class="text-slate-400 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="updatePurchase" class="p-6 space-y-5">

                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Business <span class="text-red-400">*</span></label>
                            <select wire:model.live="editBusinessId"
                                class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none transition">
                                <option value="">— Select —</option>
                                <?php $__currentLoopData = $businesses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($b['id']); ?>"><?php echo e($b['name']); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['editBusinessId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Branch <span class="text-red-400">*</span></label>
                            <select wire:model.live="editBranchId"
                                <?php if(empty($editBranches)): ?> disabled <?php endif; ?>
                                class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none transition disabled:opacity-40">
                                <option value="">— Select —</option>
                                <?php $__currentLoopData = $editBranches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $br): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($br['id']); ?>"><?php echo e($br['name']); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['editBranchId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Category <span class="text-red-400">*</span></label>
                            <select wire:model.live="editCategoryId"
                                <?php if(empty($editCategories)): ?> disabled <?php endif; ?>
                                class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none transition disabled:opacity-40">
                                <option value="">— Select —</option>
                                <?php $__currentLoopData = $editCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($c['id']); ?>"><?php echo e($c['name']); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['editCategoryId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Item <span class="text-red-400">*</span></label>
                            <select wire:model.live="editProductId"
                                <?php if(empty($editProducts)): ?> disabled <?php endif; ?>
                                class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none transition disabled:opacity-40">
                                <option value="">— Select —</option>
                                <?php $__currentLoopData = $editProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($p['id']); ?>"><?php echo e($p['name']); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['editProductId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 border-t border-slate-800">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Quantity <span class="text-red-400">*</span></label>
                            <input wire:model="editQuantity" type="number" step="0.001" min="0.001" autocomplete="off"
                                class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm font-mono text-white focus:border-indigo-500 outline-none transition">
                            <?php $__errorArgs = ['editQuantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Unit <span class="text-red-400">*</span></label>
                            <select wire:model="editUnit"
                                class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:border-indigo-500 outline-none transition">
                                <option value="">— Select —</option>
                                <?php $__currentLoopData = $unitOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($u); ?>" <?php if($editUnit === $u): echo 'selected'; endif; ?>><?php echo e(ucfirst($u)); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['editUnit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Purchase Price (ETB) <span class="text-red-400">*</span></label>
                            <input wire:model="editUnitPrice" type="number" step="0.01" min="0" autocomplete="off"
                                class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm font-mono text-white focus:border-indigo-500 outline-none transition">
                            <?php $__errorArgs = ['editUnitPrice'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Selling Price (ETB)
                                <?php
                                    $editIsOil = $this->businessIdIsOil($editBusinessId);
                                    $editIsBookstore = $editBusinessId ? collect($this->businesses)->firstWhere('id', (int)$editBusinessId) && str_contains(strtolower(collect($this->businesses)->firstWhere('id', (int)$editBusinessId)['name'] ?? ''), 'book') || str_contains(strtolower(collect($this->businesses)->firstWhere('id', (int)$editBusinessId)['name'] ?? ''), 'albereka') : false;
                                ?>
                                <?php if($editIsOil && !$editIsBookstore): ?>
                                    <span class="text-slate-500 font-normal">(Optional)</span>
                                <?php else: ?>
                                    <span class="text-red-400">*</span>
                                <?php endif; ?>
                                <?php if((float)$editUnitPrice > 0 && $editSellingPrice !== ''): ?>
                                    <span class="text-slate-600 font-normal">≥ <?php echo e(number_format((float)$editUnitPrice, 2)); ?></span>
                                <?php endif; ?>
                            </label>
                            <input wire:model="editSellingPrice" type="number" step="0.01" min="0" autocomplete="off"
                                placeholder="<?php echo e(($editIsOil && !$editIsBookstore) ? 'Optional' : '0.00'); ?>"
                                class="w-full bg-slate-950 border <?php echo e($editSellingPriceError ? 'border-red-500' : 'border-slate-700/80'); ?> rounded-xl px-4 py-2.5 text-sm font-mono text-white focus:border-indigo-500 outline-none transition">
                            <?php if($editSellingPriceError): ?>
                                <p class="text-red-400 text-xs mt-1"><?php echo e($editSellingPriceError); ?></p>
                            <?php endif; ?>
                            <?php $__errorArgs = ['editSellingPrice'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
                        <button type="button" wire:click="closeEdit"
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 transition">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="px-5 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition disabled:opacity-50 flex items-center gap-1.5">
                            <span wire:loading.remove wire:target="updatePurchase">Save Changes</span>
                            <span wire:loading wire:target="updatePurchase">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    
    <?php if($showDeleteModal): ?>
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div class="w-full max-w-sm bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden">
                <div class="flex items-center gap-2.5 px-6 py-4 border-b border-slate-800 bg-slate-950/60">
                    <div class="w-8 h-8 rounded-lg bg-red-600/20 text-red-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-white">Delete Purchase</h3>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-sm text-slate-300">Are you sure you want to delete the purchase of <span class="font-bold text-white">"<?php echo e($deleteName); ?>"</span>?</p>
                    <p class="text-xs text-slate-500">This cannot be undone. Stock will not be automatically reversed.</p>
                    <div class="flex justify-end gap-3 pt-2 border-t border-slate-800">
                        <button type="button" wire:click="closeDelete"
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 transition">Cancel</button>
                        <button type="button" wire:click="deletePurchase" wire:loading.attr="disabled"
                            class="px-5 py-2 rounded-xl text-xs font-bold bg-red-600 hover:bg-red-500 text-white transition disabled:opacity-50">
                            <span wire:loading.remove wire:target="deletePurchase">Yes, Delete</span>
                            <span wire:loading wire:target="deletePurchase">Deleting...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/admin/purchases/create.blade.php ENDPATH**/ ?>