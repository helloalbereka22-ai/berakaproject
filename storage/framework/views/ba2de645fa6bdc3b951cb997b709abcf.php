<div class="space-y-6">

    
    <?php if(session('success')): ?>
        <div class="flex items-center gap-3 p-5 rounded-2xl bg-gradient-to-r from-emerald-500/20 to-teal-500/20 border-l-4 border-emerald-500 text-emerald-300 text-sm shadow-lg backdrop-blur-sm">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <p class="font-bold text-emerald-200">Success!</p>
                <p class="text-emerald-300"><?php echo e(session('success')); ?></p>
            </div>
        </div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="flex items-center gap-3 p-5 rounded-2xl bg-gradient-to-r from-red-500/20 to-orange-500/20 border-l-4 border-red-500 text-red-300 text-sm shadow-lg backdrop-blur-sm">
            <div class="w-10 h-10 rounded-xl bg-red-500/20 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="font-bold text-red-200">Error!</p>
                <p class="text-red-300"><?php echo e(session('error')); ?></p>
            </div>
        </div>
    <?php endif; ?>

    
    <div class="bg-gradient-to-br from-slate-900 to-slate-800 border border-slate-700/50 rounded-3xl p-8 shadow-2xl">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-5 items-end">

            
            <div class="xl:col-span-1">
                <label class="block text-xs font-bold text-slate-300 mb-2 uppercase tracking-wider">
                    Business <span class="text-red-400">*</span>
                </label>
                <select wire:model.live="businessId"
                    class="w-full bg-slate-950/80 border-2 border-slate-700/70 rounded-xl px-4 py-3 text-sm font-semibold text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all shadow-lg hover:border-slate-600">
                    <option value="">— Select Business —</option>
                    <?php $__currentLoopData = $businesses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($b['id']); ?>"><?php echo e($b['name']); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['businessId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1.5 font-semibold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <?php if($this->isOilBusiness()): ?>
            <div class="xl:col-span-1">
                <label class="block text-xs font-bold text-slate-300 mb-2 uppercase tracking-wider">
                    Type <span class="text-red-400">*</span>
                </label>
                <select wire:model.live="simpleType"
                    class="w-full bg-slate-950/80 border-2 border-slate-700/70 rounded-xl px-4 py-3 text-sm font-semibold text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all shadow-lg hover:border-slate-600">
                    <option value="product">Product</option>
                    <option value="purchase">Purchase</option>
                </select>
            </div>
            <?php endif; ?>

            
            <div class="xl:col-span-1">
                <label class="block text-xs font-bold text-slate-300 mb-2 uppercase tracking-wider">
                    Category <span class="text-red-400">*</span>
                </label>
                <select wire:model.live="categoryId"
                    <?php if(!$businessId || ($this->isOilBusiness() && count($categories) == 0)): ?> disabled <?php endif; ?>
                    class="w-full bg-slate-950/80 border-2 border-slate-700/70 rounded-xl px-4 py-3 text-sm font-semibold text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all shadow-lg hover:border-slate-600 disabled:opacity-40 disabled:cursor-not-allowed">
                    <option value="">
                        <?php if(!$businessId): ?>
                            — Select business first —
                        <?php elseif($this->isOilBusiness() && count($categories) == 0): ?>
                            — No categories —
                        <?php else: ?>
                            — Select Category —
                        <?php endif; ?>
                    </option>
                    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($c['id']); ?>"><?php echo e($c['name']); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['categoryId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1.5 font-semibold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div class="xl:col-span-2">
                <label class="block text-xs font-bold text-slate-300 mb-2 uppercase tracking-wider">
                    Item Name <span class="text-red-400">*</span>
                </label>
                <input wire:model="itemName" type="text"
                    placeholder="Enter item name..."
                    <?php if(!$categoryId): ?> disabled <?php endif; ?>
                    class="w-full bg-slate-950/80 border-2 border-slate-700/70 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all shadow-lg hover:border-slate-600 disabled:opacity-40 disabled:cursor-not-allowed font-medium">
                <?php $__errorArgs = ['itemName'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1.5 font-semibold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div class="xl:col-span-1">
                <label class="block text-xs font-bold text-slate-300 mb-2 uppercase tracking-wider">
                    Image <span class="text-slate-500">(Optional)</span>
                </label>
                <div class="relative">
                    <input wire:model="itemImage" type="file" accept="image/*" id="itemImage"
                        <?php if(!$categoryId): ?> disabled <?php endif; ?>
                        class="hidden">
                    <label for="itemImage" 
                        class="flex items-center justify-center gap-2 w-full bg-slate-950/80 border-2 border-dashed border-slate-700/70 rounded-xl px-4 py-3 text-sm font-semibold text-slate-400 hover:text-indigo-400 hover:border-indigo-500/50 focus:border-indigo-500 outline-none transition-all cursor-pointer shadow-lg disabled:opacity-40 disabled:cursor-not-allowed group">
                        <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="hidden sm:inline">Choose Image</span>
                        <span class="sm:hidden">Upload</span>
                    </label>
                </div>
                <?php $__errorArgs = ['itemImage'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1.5 font-semibold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                
                
                <?php if($itemImage): ?>
                <div class="mt-3 flex items-center gap-3 p-3 bg-slate-950/50 border border-slate-700/50 rounded-xl">
                    <img src="<?php echo e($itemImage->temporaryUrl()); ?>" alt="Preview" class="h-16 w-16 object-cover rounded-lg border-2 border-indigo-500/50 shadow-lg">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-300 truncate">Preview</p>
                        <p class="text-[10px] text-slate-500">Image ready to upload</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            
            <div class="xl:col-span-1">
                <label class="block text-xs font-bold text-transparent mb-2 uppercase tracking-wider select-none">Action</label>
                <button type="button" wire:click="addItem"
                    wire:loading.attr="disabled"
                    class="w-full h-[52px] rounded-xl text-sm font-black bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-600 hover:from-emerald-500 hover:via-teal-500 hover:to-emerald-500 text-white shadow-2xl shadow-emerald-600/30 transition-all flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer transform hover:scale-105 active:scale-95 border-2 border-emerald-400/20">
                    <span wire:loading.remove wire:target="addItem" class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        ADD ITEM
                    </span>
                    <span wire:loading wire:target="addItem" class="flex items-center gap-2">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Adding...
                    </span>
                </button>
            </div>

        </div>
    </div>

    
    <div class="bg-gradient-to-br from-slate-900 to-slate-800 border border-slate-700/50 rounded-2xl p-5 shadow-2xl">
        <div class="flex flex-col sm:flex-row gap-4">
            
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input wire:model.live="search" type="text" placeholder="Search items by name..."
                    class="w-full bg-slate-950/80 border-2 border-slate-700/70 rounded-xl pl-12 pr-4 py-3 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all shadow-lg font-medium">
            </div>
            
            
            <select wire:model.live="filterBiz"
                class="sm:w-56 bg-slate-950/80 border-2 border-slate-700/70 rounded-xl px-4 py-3 text-sm font-semibold text-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all shadow-lg hover:border-slate-600">
                <option value="">🏢 All Businesses</option>
                <?php $__currentLoopData = $businesses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($b['id']); ?>" <?php if($filterBiz == $b['id']): echo 'selected'; endif; ?>><?php echo e($b['name']); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            
            
            <?php if($filterBiz): ?>
            <select wire:model.live="filterCat"
                class="sm:w-56 bg-slate-950/80 border-2 border-slate-700/70 rounded-xl px-4 py-3 text-sm font-semibold text-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all shadow-lg hover:border-slate-600">
                <option value="">📂 All Categories</option>
                <?php $__currentLoopData = $filterCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($c['id']); ?>" <?php if($filterCat == $c['id']): echo 'selected'; endif; ?>><?php echo e($c['name']); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="bg-gradient-to-br from-slate-900 to-slate-800 border border-slate-700/50 rounded-3xl shadow-2xl overflow-hidden">
        <?php if($items->count() > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-slate-700/50 bg-gradient-to-r from-slate-950 to-slate-900 text-xs uppercase tracking-widest text-slate-400">
                            <th class="px-6 py-4 text-left font-black">Image</th>
                            <th class="px-6 py-4 text-left font-black">Business</th>
                            <th class="px-6 py-4 text-left font-black">Category</th>
                            <th class="px-6 py-4 text-left font-black">Item Name</th>
                            <th class="px-6 py-4 text-right font-black">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/30">
                        <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr wire:key="item-<?php echo e($item->id); ?>" class="hover:bg-slate-800/40 transition-all group">
                                <td class="px-6 py-4">
                                    <?php if($item->primary_image): ?>
                                        <div class="relative">
                                            <img src="<?php echo e($item->primary_image['url']); ?>" alt="<?php echo e($item->name); ?>" 
                                                class="h-14 w-14 object-cover rounded-xl border-2 border-slate-700 group-hover:border-indigo-500/50 shadow-lg transition-all group-hover:scale-110">
                                            <div class="absolute inset-0 rounded-xl bg-gradient-to-t from-black/20 to-transparent"></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="h-14 w-14 bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl border-2 border-dashed border-slate-700/50 flex items-center justify-center group-hover:border-indigo-500/30 transition-all">
                                            <svg class="w-6 h-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-bold bg-gradient-to-r from-indigo-900/40 to-purple-900/40 text-indigo-300 border border-indigo-700/30 shadow-lg">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                        <?php echo e($item->business->name ?? '—'); ?>

                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-semibold text-slate-300"><?php echo e($item->category->name ?? '—'); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-white text-base"><?php echo e($item->name); ?></p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" wire:click="openEdit(<?php echo e($item->id); ?>)"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white border border-indigo-400/20 text-xs font-black transition-all cursor-pointer shadow-lg shadow-indigo-900/30 hover:shadow-xl hover:shadow-indigo-900/50 transform hover:scale-105">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            Edit
                                        </button>
                                        <button type="button" wire:click="confirmDelete(<?php echo e($item->id); ?>)"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-gradient-to-r from-red-600 to-orange-600 hover:from-red-500 hover:to-orange-500 text-white border border-red-400/20 text-xs font-black transition-all cursor-pointer shadow-lg shadow-red-900/30 hover:shadow-xl hover:shadow-red-900/50 transform hover:scale-105">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t-2 border-slate-700/50 bg-slate-950/50">
                <?php echo e($items->links()); ?>

            </div>
        <?php else: ?>
            <div class="py-24 text-center text-slate-500 text-sm">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-slate-800 to-slate-900 border-2 border-dashed border-slate-700/50 mx-auto mb-5">
                    <svg class="w-10 h-10 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <p class="font-bold text-lg text-slate-400 mb-2">No items found</p>
                <p class="text-sm text-slate-600">Add your first item using the form above or adjust your filters</p>
            </div>
        <?php endif; ?>
    </div>

    
    <?php if($deleteId): ?>
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md animate-fadeIn">
            <div class="w-full max-w-md bg-gradient-to-br from-slate-900 to-slate-800 border-2 border-red-900/30 rounded-3xl shadow-2xl overflow-hidden transform animate-scaleIn">
                <div class="flex items-center gap-3 px-8 py-6 border-b-2 border-slate-700/50 bg-gradient-to-r from-red-950/40 to-orange-950/40">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-600 to-orange-600 text-white flex items-center justify-center shadow-xl shadow-red-900/50">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <h3 class="text-xl font-black text-white">Delete Item</h3>
                </div>
                <div class="p-8 space-y-5">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-red-500/20 flex items-center justify-center shrink-0 mt-1">
                            <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-base font-semibold text-white mb-2">Are you sure you want to delete this item?</p>
                            <p class="text-sm text-slate-300">
                                <span class="font-bold text-red-400">"<?php echo e($deleteName); ?>"</span> will be permanently removed.
                            </p>
                            <p class="text-xs text-slate-500 mt-3">⚠️ This action cannot be undone.</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t-2 border-slate-700/50">
                        <button type="button" wire:click="cancelDelete"
                            class="px-6 py-3 rounded-xl text-sm font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 transition-all transform hover:scale-105 border border-slate-700/50">
                            Cancel
                        </button>
                        <button type="button" wire:click="deleteItem" wire:loading.attr="disabled"
                            class="px-6 py-3 rounded-xl text-sm font-black bg-gradient-to-r from-red-600 to-orange-600 hover:from-red-500 hover:to-orange-500 text-white transition-all disabled:opacity-50 shadow-xl shadow-red-900/30 transform hover:scale-105 border border-red-400/20">
                            <span wire:loading.remove wire:target="deleteItem">Yes, Delete</span>
                            <span wire:loading wire:target="deleteItem" class="flex items-center gap-2">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Deleting...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    
    <?php if($showEditModal): ?>
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md animate-fadeIn"
             wire:click.self="closeEdit">
            <div class="w-full max-w-lg bg-gradient-to-br from-slate-900 to-slate-800 border-2 border-indigo-900/30 rounded-3xl shadow-2xl overflow-hidden transform animate-scaleIn">
                <div class="flex items-center justify-between px-8 py-6 border-b-2 border-slate-700/50 bg-gradient-to-r from-indigo-950/40 to-purple-950/40">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 text-white flex items-center justify-center shadow-xl shadow-indigo-900/50">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </div>
                        <h3 class="text-xl font-black text-white">Edit Item</h3>
                    </div>
                    <button type="button" wire:click="closeEdit" class="text-slate-400 hover:text-white transition-colors transform hover:scale-110">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit.prevent="updateItem" class="p-8 space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-2 uppercase tracking-wider">Business <span class="text-red-400">*</span></label>
                        <select wire:model.live="editBusinessId"
                            class="w-full bg-slate-950/80 border-2 border-slate-700/70 rounded-xl px-4 py-3 text-sm font-semibold text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all shadow-lg">
                            <option value="">— Select Business —</option>
                            <?php $__currentLoopData = $businesses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($b['id']); ?>"><?php echo e($b['name']); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['editBusinessId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1.5 font-semibold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    
                    <div class="grid grid-cols-1 <?php if($this->isEditOilBusiness()): ?> sm:grid-cols-2 <?php endif; ?> gap-5">
                        
                        <?php if($this->isEditOilBusiness()): ?>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-2 uppercase tracking-wider">Type <span class="text-red-400">*</span></label>
                            <select wire:model.live="editSimpleType"
                                class="w-full bg-slate-950/80 border-2 border-slate-700/70 rounded-xl px-4 py-3 text-sm font-semibold text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all shadow-lg">
                                <option value="product">Product</option>
                                <option value="purchase">Purchase</option>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-2 uppercase tracking-wider">Category <span class="text-red-400">*</span></label>
                            <select wire:model="editCategoryId"
                                <?php if(empty($editCategories)): ?> disabled <?php endif; ?>
                                class="w-full bg-slate-950/80 border-2 border-slate-700/70 rounded-xl px-4 py-3 text-sm font-semibold text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all shadow-lg disabled:opacity-40 disabled:cursor-not-allowed">
                                <option value="">— Select Category —</option>
                                <?php $__currentLoopData = $editCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($c['id']); ?>"><?php echo e($c['name']); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['editCategoryId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1.5 font-semibold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-2 uppercase tracking-wider">Item Name <span class="text-red-400">*</span></label>
                            <input wire:model="editItemName" type="text" placeholder="Enter item name..."
                                class="w-full bg-slate-950/80 border-2 border-slate-700/70 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all shadow-lg font-medium">
                            <?php $__errorArgs = ['editItemName'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1.5 font-semibold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-2 uppercase tracking-wider">Image <span class="text-slate-500">(Optional)</span></label>
                            <div class="relative">
                                <input wire:model="editImage" type="file" accept="image/*" id="editImage" class="hidden">
                                <label for="editImage" 
                                    class="flex items-center justify-center gap-2 w-full h-[52px] bg-slate-950/80 border-2 border-dashed border-slate-700/70 rounded-xl px-4 py-3 text-sm font-semibold text-slate-400 hover:text-indigo-400 hover:border-indigo-500/50 focus:border-indigo-500 outline-none transition-all cursor-pointer shadow-lg group">
                                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Choose Image
                                </label>
                            </div>
                            <?php $__errorArgs = ['editImage'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1.5 font-semibold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    
                    <?php if($editImage): ?>
                    <div class="flex items-center gap-3 p-4 bg-slate-950/50 border-2 border-indigo-700/50 rounded-xl">
                        <img src="<?php echo e($editImage->temporaryUrl()); ?>" alt="Preview" class="h-20 w-20 object-cover rounded-xl border-2 border-indigo-500/50 shadow-xl">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-indigo-300">New Image Selected</p>
                            <p class="text-xs text-slate-500 mt-1">This image will be added when you save</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="flex justify-end gap-3 pt-6 border-t-2 border-slate-700/50">
                        <button type="button" wire:click="closeEdit"
                            class="px-6 py-3 rounded-xl text-sm font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 transition-all transform hover:scale-105 border border-slate-700/50">
                            Cancel
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="px-6 py-3 rounded-xl text-sm font-black bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white transition-all disabled:opacity-50 flex items-center gap-2 shadow-xl shadow-indigo-900/30 transform hover:scale-105 border border-indigo-400/20">
                            <span wire:loading.remove wire:target="updateItem">Save Changes</span>
                            <span wire:loading wire:target="updateItem" class="flex items-center gap-2">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes scaleIn {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .animate-fadeIn { animation: fadeIn 0.2s ease-out; }
        .animate-scaleIn { animation: scaleIn 0.3s ease-out; }
    </style>

</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/admin/items/manager.blade.php ENDPATH**/ ?>