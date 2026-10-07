<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Users</h2>
            <p class="text-sm text-slate-400 mt-1">Manage platform users and their role assignments.</p>
        </div>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('user.create')): ?>
        <a href="<?php echo e(route('admin.users.create')); ?>" wire:navigate
           class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New User
        </a>
        <?php endif; ?>
    </div>

    <?php if(session('success')): ?><div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm"><?php echo e(session('success')); ?></div><?php endif; ?>
    <?php if(session('error')): ?><div class="p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm"><?php echo e(session('error')); ?></div><?php endif; ?>

    <div class="flex flex-col sm:flex-row gap-3">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search name or email..."
            class="flex-1 bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:border-indigo-500 outline-none">
        <select wire:model.live="businessId" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">All Businesses</option>
            <?php $__currentLoopData = $businesses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($b['id']); ?>"><?php echo e($b['name']); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
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
                    <tr class="border-b border-slate-800 bg-slate-950/50 text-[11px] uppercase tracking-widest text-slate-400">
                        <th class="text-left px-5 py-3.5 font-bold">Full Name</th>
                        <th class="text-left px-5 py-3.5 font-bold">Email</th>
                        <th class="text-left px-5 py-3.5 font-bold">Roles</th>
                        <th class="text-left px-5 py-3.5 font-bold">Business</th>
                        <th class="text-left px-5 py-3.5 font-bold">Branch</th>
                        <th class="text-left px-5 py-3.5 font-bold">Status</th>
                        <th class="px-5 py-3.5 text-right font-bold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-slate-800/30 transition" wire:key="user-<?php echo e($user->id); ?>">

                            
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white font-bold text-xs shrink-0">
                                        <?php echo e(strtoupper(substr($user->name, 0, 1))); ?>

                                    </div>
                                    <p class="font-semibold text-white text-sm"><?php echo e($user->name); ?></p>
                                </div>
                            </td>

                            
                            <td class="px-5 py-3.5 text-xs text-slate-400"><?php echo e($user->email); ?></td>

                            
                            <td class="px-5 py-3.5">
                                <div class="flex flex-wrap gap-1">
                                    <?php $__currentLoopData = $user->roles->unique('id')->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
                                            <?php echo e($role->name === 'Super Admin' ? 'bg-violet-500/20 text-violet-300 border border-violet-500/20' : 'bg-indigo-500/15 text-indigo-300 border border-indigo-500/15'); ?>">
                                            <?php echo e($role->name); ?>

                                        </span>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php if($user->roles->unique('id')->count() > 3): ?>
                                        <span class="text-[10px] text-slate-500 font-semibold">+<?php echo e($user->roles->unique('id')->count() - 3); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            
                            <td class="px-5 py-3.5">
                                <?php
                                    $userBusinessIds = $user->roles->pluck('pivot.business_id')->filter()->unique();
                                ?>
                                <?php if($userBusinessIds->isEmpty()): ?>
                                    <span class="text-slate-600 text-xs">—</span>
                                <?php else: ?>
                                    <div class="flex flex-wrap gap-1">
                                        <?php $__currentLoopData = $userBusinessIds->take(2); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bizId): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-slate-800 text-slate-300 border border-slate-700/60">
                                                <?php echo e($businessMap[$bizId] ?? '—'); ?>

                                            </span>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($userBusinessIds->count() > 2): ?>
                                            <span class="text-[10px] text-slate-500">+<?php echo e($userBusinessIds->count() - 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            
                            <td class="px-5 py-3.5">
                                <?php
                                    $userBranchIds = $user->roles->pluck('pivot.branch_id')->filter()->unique();
                                ?>
                                <?php if($userBranchIds->isEmpty()): ?>
                                    <span class="text-slate-600 text-xs">—</span>
                                <?php else: ?>
                                    <div class="flex flex-wrap gap-1">
                                        <?php $__currentLoopData = $userBranchIds->take(2); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $brId): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-sky-500/10 text-sky-300 border border-sky-500/20">
                                                <?php echo e($branchMap[$brId] ?? '—'); ?>

                                            </span>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($userBranchIds->count() > 2): ?>
                                            <span class="text-[10px] text-slate-500">+<?php echo e($userBranchIds->count() - 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            
                            <td class="px-5 py-3.5">
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('user.update')): ?>
                                <?php if (! ($user->isSuperAdmin() && auth()->id() !== $user->id)): ?>
                                <button wire:click="toggleStatus(<?php echo e($user->id); ?>)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer
                                    <?php echo e(($user->status ?? 'active') === 'active'
                                        ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-red-500/10 hover:text-red-400 hover:border-red-500/20'
                                        : 'bg-slate-700/50 text-slate-400 border border-slate-600/30 hover:bg-emerald-500/10 hover:text-emerald-400 hover:border-emerald-500/20'); ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?php echo e(($user->status ?? 'active') === 'active' ? 'bg-emerald-400' : 'bg-slate-500'); ?>"></span>
                                    <?php echo e(ucfirst($user->status ?? 'active')); ?>

                                </button>
                                <?php endif; ?>
                                <?php endif; ?>
                            </td>

                            
                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-end gap-2">
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('user.update')): ?>
                                    <a href="<?php echo e(route('admin.users.edit', $user->id)); ?>" wire:navigate
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-indigo-950/80 hover:bg-indigo-900 text-indigo-300 border border-indigo-700/50 text-xs font-bold transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Edit
                                    </a>
                                    <?php endif; ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('user.delete')): ?>
                                    <?php if (! ($user->isSuperAdmin() || $user->hasRole('Admin'))): ?>
                                    <button wire:click="confirmDelete(<?php echo e($user->id); ?>)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-red-950/80 hover:bg-red-900 text-red-300 border border-red-700/50 text-xs font-bold transition cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Delete
                                    </button>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>

                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="7" class="px-6 py-16 text-center text-slate-600 text-sm">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-slate-800 bg-slate-950/30"><?php echo e($users->links()); ?></div>
    </div>

    <?php if($showDeleteConfirm): ?>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-sm mx-4 p-6">
            <h3 class="text-base font-bold text-white mb-2">Delete User?</h3>
            <p class="text-sm text-slate-400 mb-6">This will permanently delete the user account.</p>
            <div class="flex gap-3 justify-end">
                <button wire:click="$set('showDeleteConfirm',false)" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition">Cancel</button>
                <button wire:click="delete" class="px-5 py-2 rounded-xl text-sm font-bold bg-red-600 hover:bg-red-700 text-white transition">Delete</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/admin/users/index.blade.php ENDPATH**/ ?>