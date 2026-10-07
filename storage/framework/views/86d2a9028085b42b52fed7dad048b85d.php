<div class="space-y-6">

    
    <div class="flex items-center justify-between bg-slate-900 border border-slate-800/90 rounded-2xl p-5 shadow-xl">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-violet-600 to-indigo-500 flex items-center justify-center shadow-lg shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            </div>
            <div>
                <h2 class="text-xl font-extrabold text-white"><?php echo e(__('app.ui.category_management')); ?></h2>
                <p class="text-[11px] text-slate-400 mt-0.5">Add and organise categories per business. For manufacturing businesses, choose Product or Purchase type.</p>
            </div>
        </div>
    </div>

    
    <?php if(session('success')): ?>
        <div class="flex items-center gap-2 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="flex items-center gap-2 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>

    
    <div class="bg-slate-900 border border-slate-800/90 rounded-2xl p-5 shadow-xl space-y-4">
        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500"><?php echo e(__('app.ui.add_category')); ?></p>

        <form wire:submit.prevent="addCategory">
            <div class="grid grid-cols-1 sm:grid-cols-2 <?php if($this->isOilBusiness()): ?> lg:grid-cols-4 <?php else: ?> lg:grid-cols-3 <?php endif; ?> gap-4 items-end">

                
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1"><?php echo e(__('app.ui.select_business')); ?> <span class="text-red-400">*</span></label>
                    <select wire:model.live="businessId"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white focus:border-indigo-500 outline-none transition">
                        <option value="">— <?php echo e(__('app.ui.select_business')); ?> —</option>
                        <?php $__currentLoopData = $businesses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($b['id']); ?>"><?php echo e($b['name']); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['businessId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                
                <?php if($this->isOilBusiness()): ?>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">Type <span class="text-red-400">*</span></label>
                    <select wire:model="simpleType"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white focus:border-indigo-500 outline-none transition">
                        <option value="product">Product</option>
                        <option value="purchase">Purchase</option>
                    </select>
                </div>
                <?php endif; ?>

                
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1"><?php echo e(__('app.ui.category_name')); ?> <span class="text-red-400">*</span></label>
                    <input wire:model="categoryName" type="text" placeholder="<?php echo e(__('app.ui.category_name')); ?>..."
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 outline-none transition">
                    <?php $__errorArgs = ['categoryName'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                
                <div>
                    <button type="submit" wire:loading.attr="disabled"
                        class="w-full py-2.5 rounded-xl text-sm font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer shadow-lg shadow-indigo-600/20">
                        <span wire:loading.remove wire:target="addCategory" class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <?php echo e(__('app.ui.add_category')); ?>

                        </span>
                        <span wire:loading wire:target="addCategory"><?php echo e(__('app.ui.saving')); ?></span>
                    </button>
                </div>

            </div>
        </form>
    </div>

    
    <div class="bg-slate-900 border border-slate-800/90 rounded-2xl p-4 shadow-xl flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input wire:model.live.debounce.400ms="search" type="text" placeholder="<?php echo e(__('app.ui.search_categories')); ?>"
                class="w-full bg-slate-950 border border-slate-700 rounded-xl pl-10 pr-4 py-2 text-sm text-white placeholder-slate-600 focus:border-indigo-500 outline-none transition">
        </div>
        <div class="sm:w-56">
            <select wire:model.live="filterBiz"
                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2 text-sm text-white focus:border-indigo-500 outline-none transition">
                <option value=""><?php echo e(__('app.ui.all_businesses')); ?></option>
                <?php $__currentLoopData = $businesses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($b['id']); ?>"><?php echo e($b['name']); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
    </div>

    
    <div class="bg-slate-900 border border-slate-800/90 rounded-2xl shadow-xl overflow-hidden">
        <?php if($categories->count() > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-800 bg-slate-950/50 text-[11px] uppercase tracking-widest text-slate-400">
                            <th class="px-5 py-3.5 text-left font-bold"><?php echo e(__('app.ui.select_business')); ?></th>
                            <th class="px-5 py-3.5 text-left font-bold"><?php echo e(__('app.ui.category_name')); ?></th>
                            <th class="px-5 py-3.5 text-left font-bold">Type</th>
                            <th class="px-5 py-3.5 text-right font-bold"><?php echo e(__('app.ui.actions')); ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/50">
                        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr wire:key="cat-<?php echo e($cat->id); ?>" class="hover:bg-slate-800/40 transition">

                                
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-slate-800 text-slate-300 border border-slate-700/60">
                                        <?php echo e($cat->business->name ?? '—'); ?>

                                    </span>
                                </td>

                                
                                <td class="px-5 py-3.5">
                                    <p class="font-bold text-white"><?php echo e($cat->name); ?></p>
                                </td>

                                
                                <td class="px-5 py-3.5">
                                    <?php
                                        $isPurchase = in_array($cat->type, ['raw_material', 'packing_material']);
                                        $isProduct  = in_array($cat->type, ['finished_product', 'oil', 'manufactured_product']);
                                    ?>
                                    <?php if($isPurchase): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/15 text-amber-300 border border-amber-500/20">
                                            Purchase
                                        </span>
                                    <?php elseif($isProduct): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 text-emerald-300 border border-emerald-500/20">
                                            Product
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-500/15 text-slate-400 border border-slate-500/20">
                                            General
                                        </span>
                                    <?php endif; ?>
                                </td>

                                
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" wire:click="openEditModal(<?php echo e($cat->id); ?>)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-indigo-950/80 hover:bg-indigo-900 text-indigo-300 border border-indigo-700/50 text-xs font-bold transition cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            <?php echo e(__('app.ui.edit')); ?>

                                        </button>
                                        <button type="button" wire:click="confirmDelete(<?php echo e($cat->id); ?>)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-red-950/80 hover:bg-red-900 text-red-300 border border-red-700/50 text-xs font-bold transition cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            <?php echo e(__('app.ui.delete')); ?>

                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
            <?php if($categories->hasPages()): ?>
                <div class="px-5 py-3 border-t border-slate-800"><?php echo e($categories->links()); ?></div>
            <?php endif; ?>
        <?php else: ?>
            <div class="py-16 text-center text-slate-500">
                <svg class="w-10 h-10 mx-auto mb-3 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                <p class="font-semibold text-slate-400"><?php echo e(__('app.ui.no_results')); ?></p>
                <?php if($search || $filterBiz): ?>
                    <button wire:click="$set('search',''); $set('filterBiz','')"
                        class="mt-3 px-4 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition">
                        <?php echo e(__('app.ui.reset')); ?>

                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    
    <?php if($showEditModal): ?>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
         wire:click.self="closeEditModal">
        <div class="w-full max-w-lg bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden">

            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800 bg-slate-950/60">
                <h3 class="text-base font-bold text-white"><?php echo e(__('app.ui.edit_category')); ?></h3>
                <button type="button" wire:click="closeEditModal" class="text-slate-400 hover:text-white transition cursor-pointer">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit.prevent="updateCategory" class="p-6 space-y-4">

                
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1"><?php echo e(__('app.ui.select_business')); ?> <span class="text-red-400">*</span></label>
                    <select wire:model.live="editBusinessId"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none transition">
                        <option value="">— <?php echo e(__('app.ui.select_business')); ?> —</option>
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

                
                <?php if($this->isEditOilBusiness()): ?>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1">Type <span class="text-red-400">*</span></label>
                    <select wire:model="editSimpleType"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none transition">
                        <option value="product">Product</option>
                        <option value="purchase">Purchase</option>
                    </select>
                </div>
                <?php endif; ?>

                
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1"><?php echo e(__('app.ui.category_name')); ?> <span class="text-red-400">*</span></label>
                    <input wire:model="editCategoryName" type="text"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none transition">
                    <?php $__errorArgs = ['editCategoryName'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="flex justify-end gap-3 pt-2 border-t border-slate-800">
                    <button type="button" wire:click="closeEditModal"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 transition cursor-pointer">
                        <?php echo e(__('app.ui.cancel')); ?>

                    </button>
                    <button type="submit" wire:loading.attr="disabled"
                        class="px-5 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition disabled:opacity-50 cursor-pointer">
                        <span wire:loading.remove wire:target="updateCategory"><?php echo e(__('app.ui.save')); ?></span>
                        <span wire:loading wire:target="updateCategory"><?php echo e(__('app.ui.saving')); ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    
    <?php if($showDeleteModal): ?>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="w-full max-w-sm bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h3 class="text-base font-bold text-white mb-2"><?php echo e(__('app.ui.delete')); ?>?</h3>
            <p class="text-sm text-slate-400 mb-1">You are about to delete:</p>
            <p class="font-bold text-red-300 mb-4">"<?php echo e($deleteCategoryName); ?>"</p>
            <p class="text-xs text-slate-500 mb-6">Categories with products cannot be deleted.</p>
            <div class="flex justify-end gap-3">
                <button type="button" wire:click="closeDeleteModal"
                    class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 transition cursor-pointer">
                    <?php echo e(__('app.ui.cancel')); ?>

                </button>
                <button type="button" wire:click="deleteCategory"
                    class="px-5 py-2 rounded-xl text-xs font-bold bg-red-600 hover:bg-red-500 text-white transition cursor-pointer">
                    <?php echo e(__('app.ui.delete')); ?>

                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/admin/categories/manager.blade.php ENDPATH**/ ?>