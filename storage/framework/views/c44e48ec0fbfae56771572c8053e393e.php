<div class="space-y-6">
    
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-900 border border-slate-800/90 rounded-2xl p-6 shadow-xl">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-extrabold text-white tracking-tight">Branch Management</h2>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/10 border border-indigo-500/30 text-indigo-400">
                    🏢 Multi-Business Locations
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Manage branches, retail stores, and production facilities for Al-Baraka Lubricating Oil Factory and Al-Baraka Bookstore Network.</p>
        </div>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('branch.create')): ?>
        <a href="<?php echo e(route('admin.branches.create')); ?>" wire:navigate
           class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add New Branch
        </a>
        <?php endif; ?>
    </div>

    <?php if(session('success')): ?>
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm font-medium flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span><?php echo e(session('success')); ?></span>
        </div>
    <?php endif; ?>

    
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search branch name or code (e.g. BOOK-BR1, OIL-HQ)..."
                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:border-indigo-500 outline-none">
        </div>
        <select wire:model.live="businessId" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">All Businesses (Oil Factory & Bookstore)</option>
            <?php $__currentLoopData = $businesses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($b['id']); ?>"><?php echo e($b['name']); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <select wire:model.live="status" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">All Statuses</option>
            <option value="active">Active Only</option>
            <option value="inactive">Inactive Only</option>
        </select>
    </div>

    
    <div class="bg-slate-900 border border-slate-800/90 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-950/50">
                        <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Branch / Facility</th>
                        <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Business Entity</th>
                        <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Manager</th>
                        <th class="text-center px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Storage Locations</th>
                        <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php $__empty_1 = true; $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-6 py-4">
                                <div class="font-bold text-white"><?php echo e($branch->name); ?></div>
                                <div class="text-xs text-indigo-400 font-mono mt-0.5"><?php echo e($branch->code); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                    $isOil = str_contains(strtolower(optional($branch->business)->name ?? ''), 'oil');
                                ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold <?php echo e($isOil ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/20' : 'bg-indigo-500/10 text-indigo-300 border border-indigo-500/20'); ?>">
                                    <span><?php echo e($isOil ? '🌾' : '🛒'); ?></span>
                                    <span><?php echo e(optional($branch->business)->name); ?></span>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-300 text-sm">
                                <?php if($branch->manager): ?>
                                    <div class="font-medium text-white"><?php echo e($branch->manager->name); ?></div>
                                    <div class="text-[11px] text-slate-500"><?php echo e($branch->manager->email); ?></div>
                                <?php else: ?>
                                    <span class="text-slate-600 italic">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-800 border border-slate-700 text-slate-200">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    <?php echo e($branch->locations_count); ?> <?php echo e(Str::plural('Warehouse', $branch->locations_count)); ?>

                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('branch.update')): ?>
                                <button wire:click="toggleStatus(<?php echo e($branch->id); ?>)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold transition
                                    <?php echo e($branch->status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-red-500/10 hover:text-red-400 hover:border-red-500/20' : 'bg-slate-700/50 text-slate-400 border border-slate-600/30 hover:bg-emerald-500/10 hover:text-emerald-400'); ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?php echo e($branch->status === 'active' ? 'bg-emerald-400' : 'bg-slate-500'); ?>"></span>
                                    <?php echo e(ucfirst($branch->status)); ?>

                                </button>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400"><?php echo e(ucfirst($branch->status)); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('branch.update')): ?>
                                    <a href="<?php echo e(route('admin.branches.edit', $branch->id)); ?>" wire:navigate
                                       class="p-2 rounded-lg text-slate-400 hover:text-indigo-400 hover:bg-indigo-500/10 transition" title="Edit Branch">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('branch.delete')): ?>
                                    <button wire:click="confirmDelete(<?php echo e($branch->id); ?>)"
                                        class="p-2 rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition" title="Delete Branch">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center text-slate-500">
                                No branches registered yet. Click "Add New Branch" above to create one.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($branches->hasPages()): ?>
        <div class="px-6 py-4 border-t border-slate-800"><?php echo e($branches->links()); ?></div>
        <?php endif; ?>
    </div>

    
    <?php if($showDeleteConfirm): ?>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-sm mx-4 p-6 shadow-2xl space-y-4">
            <h3 class="text-base font-bold text-white">Delete Branch?</h3>
            <p class="text-sm text-slate-400 leading-relaxed">Are you sure you want to delete this branch? This action cannot be undone.</p>
            <div class="flex gap-3 justify-end pt-2">
                <button wire:click="$set('showDeleteConfirm',false)" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition">Cancel</button>
                <button wire:click="delete" class="px-5 py-2 rounded-xl text-xs font-bold bg-red-600 hover:bg-red-500 text-white transition">Confirm Delete</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/admin/branches/index.blade.php ENDPATH**/ ?>