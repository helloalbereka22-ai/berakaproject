<div class="space-y-6">

    
    <div class="flex items-center gap-4 bg-slate-900 border border-slate-800/90 rounded-2xl p-5 shadow-xl">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-violet-600 to-indigo-500 flex items-center justify-center shadow-lg shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <div>
            <h2 class="text-xl font-extrabold text-white">Role Management</h2>
            <p class="text-[11px] text-slate-500 mt-0.5">Create and manage roles and their permissions.</p>
        </div>
    </div>

    
    <?php if($successMessage): ?>
        <div class="flex items-center gap-2 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <?php echo e($successMessage); ?>

        </div>
    <?php endif; ?>
    <?php if($errorMessage): ?>
        <div class="flex items-center gap-2 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <?php echo e($errorMessage); ?>

        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        
        <div class="xl:col-span-1">
            <div class="bg-slate-900 border border-slate-800/90 rounded-2xl p-5 shadow-xl space-y-4">
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500">Create New Role</p>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">Role Name <span class="text-red-400">*</span></label>
                    <input wire:model="newName" type="text" placeholder="e.g. Branch Manager"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-violet-500 outline-none transition">
                    <?php $__errorArgs = ['newName'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">Description</label>
                    <textarea wire:model="newDescription" rows="2" placeholder="What does this role do?"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-violet-500 outline-none transition resize-none"></textarea>
                </div>

                
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-2">Permissions</label>
                    <div class="space-y-3 max-h-64 overflow-y-auto pr-1">
                        <?php $__currentLoopData = $permissionGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group => $perms): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-600 mb-1"><?php echo e($group ?: 'General'); ?></p>
                                <div class="space-y-1">
                                    <?php $__currentLoopData = $perms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $perm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <label class="flex items-center gap-2 cursor-pointer group">
                                            <input type="checkbox" wire:model="newPermissions" value="<?php echo e($perm->id); ?>"
                                                class="w-3.5 h-3.5 rounded border-slate-600 bg-slate-800 text-violet-500 focus:ring-violet-500 focus:ring-offset-slate-900">
                                            <span class="text-xs text-slate-400 group-hover:text-slate-200 transition"><?php echo e($perm->name); ?></span>
                                        </label>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>

                <button type="button" wire:click="createRole" wire:loading.attr="disabled"
                    class="w-full py-2.5 rounded-xl text-sm font-bold bg-violet-600 hover:bg-violet-500 text-white transition flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer shadow-lg shadow-violet-600/20">
                    <span wire:loading.remove wire:target="createRole" class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Create Role
                    </span>
                    <span wire:loading wire:target="createRole">Creating...</span>
                </button>
            </div>
        </div>

        
        <div class="xl:col-span-2">
            <div class="bg-slate-900 border border-slate-800/90 rounded-2xl shadow-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500">All Roles</p>
                    <span class="text-xs text-slate-500"><?php echo e($roles->count()); ?> role(s)</span>
                </div>
                <div class="divide-y divide-slate-800/60">
                    <?php $__empty_1 = true; $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="px-5 py-4 hover:bg-slate-800/30 transition" wire:key="role-<?php echo e($role->id); ?>">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="font-bold text-white text-sm"><?php echo e($role->name); ?></p>
                                        <?php if(in_array($role->name, ['Super Admin', 'Admin'])): ?>
                                            <span class="text-[9px] font-bold bg-violet-500/15 text-violet-400 border border-violet-500/20 px-1.5 py-0.5 rounded-full">SYSTEM</span>
                                        <?php endif; ?>
                                        <span class="text-[10px] text-slate-500"><?php echo e($role->users_count); ?> user(s)</span>
                                    </div>
                                    <?php if($role->description): ?>
                                        <p class="text-xs text-slate-500 mt-0.5"><?php echo e($role->description); ?></p>
                                    <?php endif; ?>

                                    
                                    <?php if($role->permissions->isNotEmpty()): ?>
                                        <div class="flex flex-wrap gap-1 mt-2">
                                            <?php $__currentLoopData = $role->permissions->take(6); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $perm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <span class="inline-flex text-[9px] font-semibold bg-slate-800 text-slate-400 border border-slate-700/60 px-1.5 py-0.5 rounded">
                                                    <?php echo e($perm->name); ?>

                                                </span>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php if($role->permissions->count() > 6): ?>
                                                <span class="text-[9px] text-slate-600 font-semibold self-center">+<?php echo e($role->permissions->count() - 6); ?> more</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-[10px] text-slate-700 mt-1">No permissions assigned</p>
                                    <?php endif; ?>
                                </div>

                                
                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="button" wire:click="openEdit(<?php echo e($role->id); ?>)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-indigo-950/80 hover:bg-indigo-900 text-indigo-300 border border-indigo-700/50 text-xs font-bold transition cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Edit
                                    </button>
                                    <?php if(!in_array($role->name, ['Super Admin', 'Admin'])): ?>
                                    <button type="button" wire:click="confirmDelete(<?php echo e($role->id); ?>)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-red-950/80 hover:bg-red-900 text-red-300 border border-red-700/50 text-xs font-bold transition cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Delete
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="py-12 text-center text-slate-600 text-sm">No roles defined yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    
    <?php if($showEditModal): ?>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
         wire:click.self="closeEdit">
        <div class="w-full max-w-2xl bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl max-h-[90vh] overflow-y-auto">

            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800 sticky top-0 bg-slate-900">
                <h3 class="text-base font-bold text-white">Edit Role</h3>
                <button type="button" wire:click="closeEdit" class="text-slate-400 hover:text-white transition cursor-pointer">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-6 space-y-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1">Role Name <span class="text-red-400">*</span></label>
                    <input wire:model="editName" type="text"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-violet-500 outline-none transition">
                    <?php $__errorArgs = ['editName'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1">Description</label>
                    <textarea wire:model="editDescription" rows="2"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-violet-500 outline-none transition resize-none"></textarea>
                </div>

                
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-3">Permissions</label>
                    <div class="space-y-4">
                        <?php $__currentLoopData = $permissionGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group => $perms): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="bg-slate-800/40 rounded-xl p-3">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2"><?php echo e($group ?: 'General'); ?></p>
                                <div class="grid grid-cols-2 gap-1.5">
                                    <?php $__currentLoopData = $perms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $perm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <label class="flex items-center gap-2 cursor-pointer group">
                                            <input type="checkbox" wire:model="editPermissions" value="<?php echo e($perm->id); ?>"
                                                class="w-3.5 h-3.5 rounded border-slate-600 bg-slate-800 text-violet-500 focus:ring-violet-500 focus:ring-offset-slate-900">
                                            <span class="text-xs text-slate-400 group-hover:text-slate-200 transition"><?php echo e($perm->name); ?></span>
                                        </label>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="closeEdit"
                        class="px-5 py-2 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="button" wire:click="updateRole" wire:loading.attr="disabled"
                        class="px-6 py-2 rounded-xl text-sm font-bold bg-violet-600 hover:bg-violet-500 text-white transition disabled:opacity-50 cursor-pointer">
                        <span wire:loading.remove wire:target="updateRole">Save Changes</span>
                        <span wire:loading wire:target="updateRole">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    
    <?php if($showDeleteModal): ?>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="w-full max-w-sm bg-slate-900 border border-slate-700 rounded-2xl p-6">
            <h3 class="text-base font-bold text-white mb-2">Delete Role?</h3>
            <p class="text-sm text-slate-400 mb-1">You are about to delete:</p>
            <p class="text-sm font-bold text-red-300 mb-4">"<?php echo e($deleteName); ?>"</p>
            <p class="text-xs text-slate-500 mb-6">All users assigned to this role will have it removed. This cannot be undone.</p>
            <div class="flex gap-3 justify-end">
                <button type="button" wire:click="closeDelete"
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer">
                    Cancel
                </button>
                <button type="button" wire:click="deleteRole"
                    class="px-5 py-2 rounded-xl text-sm font-bold bg-red-600 hover:bg-red-700 text-white transition cursor-pointer">
                    Delete
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/admin/roles/manager.blade.php ENDPATH**/ ?>