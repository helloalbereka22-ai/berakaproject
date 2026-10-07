<div class="space-y-6">

    
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Businesses</h2>
            <p class="text-sm text-slate-400 mt-1">Manage all registered businesses on the platform.</p>
        </div>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('business.create')): ?>
        <a href="<?php echo e(route('admin.businesses.create')); ?>" wire:navigate
           class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Business
        </a>
        <?php endif; ?>
    </div>

    <?php if(session('success')): ?>
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm"><?php echo e(session('success')); ?></div>
    <?php endif; ?>

    
    <div class="flex flex-col sm:flex-row gap-3">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search name or code..."
            class="flex-1 bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:border-indigo-500 outline-none">
        <select wire:model.live="status" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>

    
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th class="text-left px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Business</th>
                        <th class="text-left px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Code</th>
                        <th class="text-center px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Branches</th>
                        <th class="text-left px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="text-left px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php $__empty_1 = true; $__currentLoopData = $businesses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $business): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white font-bold text-sm shrink-0">
                                        <?php echo e(strtoupper(substr($business->name, 0, 1))); ?>

                                    </div>
                                    <div>
                                        <p class="font-semibold text-white"><?php echo e($business->name); ?></p>
                                        <?php if($business->description): ?>
                                            <p class="text-xs text-slate-500 truncate max-w-xs"><?php echo e(Str::limit($business->description, 60)); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-mono text-xs text-indigo-300 bg-indigo-500/10 px-2 py-1 rounded-lg"><?php echo e($business->code ?? '—'); ?></span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-white font-bold"><?php echo e($business->branches_count); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('business.update')): ?>
                                <button wire:click="toggleStatus(<?php echo e($business->id); ?>)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold transition
                                    <?php echo e($business->status === 'active'
                                        ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-red-500/10 hover:text-red-400 hover:border-red-500/20'
                                        : 'bg-slate-700/50 text-slate-400 border border-slate-600/30 hover:bg-emerald-500/10 hover:text-emerald-400 hover:border-emerald-500/20'); ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?php echo e($business->status === 'active' ? 'bg-emerald-400' : 'bg-slate-500'); ?>"></span>
                                    <?php echo e(ucfirst($business->status)); ?>

                                </button>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400"><?php echo e(ucfirst($business->status)); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-slate-400 text-xs"><?php echo e($business->created_at->format('d M Y')); ?></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('business.update')): ?>
                                    <a href="<?php echo e(route('admin.businesses.edit', $business->id)); ?>" wire:navigate
                                       class="p-2 rounded-lg text-slate-500 hover:text-indigo-400 hover:bg-indigo-500/10 transition" title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('business.delete')): ?>
                                    <button wire:click="confirmDelete(<?php echo e($business->id); ?>)"
                                        class="p-2 rounded-lg text-slate-500 hover:text-red-400 hover:bg-red-500/10 transition" title="Delete">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <svg class="w-12 h-12 mx-auto mb-3 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                <p class="text-slate-500 font-medium">No businesses found.</p>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('business.create')): ?>
                                <a href="<?php echo e(route('admin.businesses.create')); ?>" wire:navigate class="mt-3 inline-block text-sm text-indigo-400 hover:text-indigo-300">Create the first business →</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-800"><?php echo e($businesses->links()); ?></div>
    </div>

    
    <?php if($showDeleteConfirm): ?>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-sm mx-4 shadow-2xl p-6">
            <h3 class="text-base font-bold text-white mb-2">Delete Business?</h3>
            <p class="text-sm text-slate-400 mb-6">This will permanently delete the business and all its data. This action cannot be undone.</p>
            <div class="flex gap-3 justify-end">
                <button wire:click="$set('showDeleteConfirm',false)" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition">Cancel</button>
                <button wire:click="delete" class="px-5 py-2 rounded-xl text-sm font-bold bg-red-600 hover:bg-red-700 text-white transition">Delete</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/admin/businesses/index.blade.php ENDPATH**/ ?>