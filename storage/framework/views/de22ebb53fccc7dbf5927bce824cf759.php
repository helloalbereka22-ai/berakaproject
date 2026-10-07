<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Bookstore Products</h2>
            <p class="text-sm text-slate-400 mt-1">Shared catalog — oil products, books, raw materials, packing materials.</p>
        </div>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('product.create')): ?>
        <a href="<?php echo e(route('admin.saleable-products.create')); ?>" wire:navigate
           class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Product
        </a>
        <?php endif; ?>
    </div>

    <?php if(session('success')): ?><div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm"><?php echo e(session('success')); ?></div><?php endif; ?>
    <?php if(session('error')): ?><div class="p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm"><?php echo e(session('error')); ?></div><?php endif; ?>

    
    <?php $isSeller = auth()->user()->roles->whereIn('name', ['Seller','Branch Seller','Sales Staff'])->isNotEmpty(); ?>
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search name, SKU..."
            class="col-span-2 <?php echo e($isSeller ? 'lg:col-span-4' : 'lg:col-span-2'); ?> bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:border-indigo-500 outline-none">
        <?php if (! ($isSeller)): ?>
        <select wire:model.live="businessId" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">All Businesses</option>
            <?php $__currentLoopData = $businesses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($b['id']); ?>"><?php echo e($b['name']); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <select wire:model.live="type" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">All Types</option>
            <?php $__currentLoopData = $productTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($key); ?>"><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <select wire:model.live="unit" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">All Units</option>
            <?php $__currentLoopData = $units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($u); ?>"><?php echo e(strtoupper($u)); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <?php endif; ?>
        <select wire:model.live="status" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">All Statuses</option>
            <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($key); ?>"><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-950/50 text-[11px] uppercase tracking-widest text-slate-400">
                        <th class="text-left px-5 py-3.5 font-bold">Product Name</th>
                        <th class="text-left px-5 py-3.5 font-bold">Category</th>
                        <th class="text-right px-5 py-3.5 font-bold">Qty</th>
                        <th class="text-right px-5 py-3.5 font-bold">Selling Price</th>
                        <th class="text-right px-5 py-3.5 font-bold">Total Price</th>
                        <?php if (! ($isSeller)): ?>
                        <th class="text-center px-5 py-3.5 font-bold">Status</th>
                        <th class="text-center px-5 py-3.5 font-bold">Marketplace</th>
                        <?php endif; ?>
                        <th class="px-5 py-3.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-slate-800/30 transition" wire:key="prod-<?php echo e($product->id); ?>">

                            
                            <td class="px-5 py-3.5">
                                <p class="font-semibold text-white text-sm"><?php echo e($product->name); ?></p>
                                <?php
                                    // Check if product has transfer movements
                                    $hasTransfer = $product->relationLoaded('stockMovements') 
                                        ? $product->stockMovements->contains(fn($m) => in_array($m->reference_type, ['transfer_in', 'transfer']))
                                        : false;
                                ?>
                                <?php if (! ($isSeller)): ?>
                                <p class="text-xs text-slate-500 font-mono"><?php echo e($product->sku ?? '—'); ?></p>
                                <?php endif; ?>
                                <?php if($hasTransfer): ?>
                                <p class="text-xs text-amber-400 font-semibold">transferred</p>
                                <?php endif; ?>
                            </td>

                            
                            <td class="px-5 py-3.5 text-xs text-slate-300"><?php echo e(optional($product->category)->name ?? '—'); ?></td>

                            
                            <td class="px-5 py-3.5 text-right">
                                <?php
                                    // Use branch-filtered stock quantity from controller's $stockData
                                    $stockQty = isset($stockData) && $stockData->has($product->id) 
                                        ? (float)$stockData->get($product->id)->total_qty 
                                        : (float)$product->current_stock;
                                    $isLow    = $product->reorder_point > 0 && $stockQty <= $product->reorder_point;
                                ?>
                                <span class="font-mono text-sm font-bold <?php echo e($isLow ? 'text-amber-400' : 'text-emerald-400'); ?>">
                                    <?php echo e(number_format($stockQty, 2)); ?>

                                </span>
                                <span class="text-xs text-slate-500 ml-0.5"><?php echo e(strtoupper($product->unit ?? '')); ?></span>
                                <?php if($isLow): ?>
                                    <span class="block text-[9px] font-bold text-amber-400 mt-0.5">LOW</span>
                                <?php endif; ?>
                            </td>

                            
                            <td class="px-5 py-3.5 text-right font-mono font-bold text-white text-sm">
                                <?php echo e(number_format($product->selling_price, 2)); ?> <span class="text-slate-500 font-normal text-xs">ETB</span>
                            </td>

                            
                            <td class="px-5 py-3.5 text-right font-mono text-sm">
                                <?php
                                    $productTotalPrice = $stockQty * $product->selling_price;
                                ?>
                                <span class="text-blue-400 font-semibold"><?php echo e(number_format($productTotalPrice, 2)); ?></span>
                                <span class="text-slate-500 text-xs"> ETB</span>
                            </td>

                            
                            <?php if (! ($isSeller)): ?>
                            <td class="px-5 py-3.5 text-center">
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('product.update')): ?>
                                <button wire:click="toggleStatus(<?php echo e($product->id); ?>)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold transition
                                    <?php echo e($product->status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:opacity-70' : 'bg-slate-700/50 text-slate-400 border border-slate-600/30 hover:opacity-70'); ?>">
                                    <?php echo e(ucfirst($product->status)); ?>

                                </button>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400"><?php echo e(ucfirst($product->status)); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <?php if($product->isSellable()): ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('product.update')): ?>
                                    <button wire:click="toggleMarketplace(<?php echo e($product->id); ?>)"
                                        class="p-1.5 rounded-lg transition <?php echo e($product->is_marketplace_visible ? 'text-emerald-400 hover:text-emerald-300' : 'text-slate-600 hover:text-slate-400'); ?>">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-xs text-slate-700">N/A</span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>

                            
                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-end gap-1">
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('product.update')): ?>
                                    <a href="<?php echo e(route('admin.saleable-products.edit', $product->id)); ?>" wire:navigate
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-indigo-950/80 hover:bg-indigo-900 text-indigo-300 border border-indigo-700/50 text-xs font-bold transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Edit
                                    </a>
                                    <?php endif; ?>
                                    <?php if (! ($isSeller)): ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('product.delete')): ?>
                                    <button wire:click="confirmDelete(<?php echo e($product->id); ?>)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-red-950/80 hover:bg-red-900 text-red-300 border border-red-700/50 text-xs font-bold transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Delete
                                    </button>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="<?php echo e($isSeller ? 6 : 8); ?>" class="px-5 py-16 text-center">
                            <svg class="w-12 h-12 mx-auto mb-3 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <p class="text-slate-500">No products found.</p>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
                
                <?php if($totalPrice > 0): ?>
                <tfoot>
                    <tr class="border-t-2 border-slate-700 bg-slate-950/70 font-bold">
                        <td colspan="4" class="px-5 py-4 text-right text-slate-400 uppercase tracking-wider text-xs">
                            Total Price of All Products (<?php echo e($totalProductCount); ?> products):
                        </td>
                        <td class="px-5 py-4 text-right font-mono text-lg">
                            <span class="text-blue-300"><?php echo e(number_format($totalPrice, 2)); ?></span>
                            <span class="text-slate-500 text-sm"> ETB</span>
                        </td>
                        <?php if (! ($isSeller)): ?>
                        <td colspan="2"></td>
                        <?php endif; ?>
                        <td></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-slate-800"><?php echo e($products->links()); ?></div>
    </div>

    <?php if($showDeleteConfirm): ?>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-sm mx-4 p-6">
            <h3 class="text-base font-bold text-white mb-2">Delete Product?</h3>
            <p class="text-sm text-slate-400 mb-6">The product will be soft-deleted and removed from all listings.</p>
            <div class="flex gap-3 justify-end">
                <button wire:click="$set('showDeleteConfirm',false)" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition">Cancel</button>
                <button wire:click="delete" class="px-5 py-2 rounded-xl text-sm font-bold bg-red-600 hover:bg-red-700 text-white transition">Delete</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/admin/saleable-products/index.blade.php ENDPATH**/ ?>