<div class="space-y-6 max-w-3xl mx-auto">

    <?php use Illuminate\Support\Facades\Storage; ?>

    
    <div class="flex items-center gap-4">
        <a href="<?php echo e(url()->previous()); ?>"
           class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h2 class="text-2xl font-extrabold text-white tracking-tight">My Profile</h2>
            <p class="text-sm text-slate-400 mt-0.5">Manage your account details and password</p>
        </div>
    </div>

    
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 flex flex-col sm:flex-row items-center sm:items-start gap-5">

        
        <div class="relative group">
            <?php if($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)): ?>
                <img src="<?php echo e(Storage::url($user->profile_picture)); ?>" 
                     alt="<?php echo e($user->name); ?>"
                     class="w-20 h-20 rounded-2xl object-cover shadow-lg shadow-indigo-900/40 shrink-0">
            <?php else: ?>
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500
                            flex items-center justify-center text-white font-extrabold text-3xl
                            shadow-lg shadow-indigo-900/40 shrink-0 select-none">
                    <?php echo e(strtoupper(substr($user->name, 0, 1))); ?>

                </div>
            <?php endif; ?>

            
            <label class="absolute inset-0 rounded-2xl bg-black/40 flex items-center justify-center 
                          opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                <div class="text-center">
                    <svg class="w-5 h-5 text-white mx-auto mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 4v16m8-8H4"/>
                    </svg>
                    <span class="text-xs text-white font-bold"><?php echo e(__('app.profile.upload_picture')); ?></span>
                </div>
                <input wire:model="profilePicture" type="file" accept="image/jpeg,image/png,image/gif" 
                       class="hidden">
            </label>
        </div>

        <div class="flex-1 min-w-0 text-center sm:text-left">
            <p class="text-xl font-bold text-white"><?php echo e($user->name); ?></p>
            <p class="text-sm text-slate-400 mt-0.5"><?php echo e($user->email); ?></p>

            
            <div class="flex flex-wrap gap-2 mt-3 justify-center sm:justify-start">
                <?php $__empty_1 = true; $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold
                             bg-indigo-500/15 text-indigo-300 border border-indigo-500/25">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse"></span>
                    <?php echo e($role['name']); ?>

                    <?php if($role['branch']): ?>
                        <span class="text-indigo-500">— <?php echo e($role['branch']); ?></span>
                    <?php elseif($role['business']): ?>
                        <span class="text-indigo-500">— <?php echo e($role['business']); ?></span>
                    <?php endif; ?>
                </span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <span class="text-xs text-slate-500">No roles assigned</span>
                <?php endif; ?>
            </div>

            
            <div class="mt-3 flex flex-wrap gap-4 justify-center sm:justify-start">
                <span class="text-xs text-slate-500 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Joined <?php echo e($user->created_at->format('d M Y')); ?>

                </span>
                <span class="text-xs flex items-center gap-1
                    <?php echo e($user->status === 'active' ? 'text-emerald-400' : 'text-red-400'); ?>">
                    <span class="w-1.5 h-1.5 rounded-full
                        <?php echo e($user->status === 'active' ? 'bg-emerald-400' : 'bg-red-400'); ?>"></span>
                    <?php echo e(ucfirst($user->status ?? 'active')); ?>

                </span>
            </div>
        </div>
    </div>

    
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center gap-2">
            <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <h3 class="font-bold text-white text-sm">Account Information</h3>
        </div>

        <form wire:submit="saveProfile" class="p-6 space-y-5">

            
            <div x-data="{ show: false }"
                 x-on:profile-saved.window="show = true; setTimeout(() => show = false, 3000)"
                 x-show="show" x-transition
                 class="flex items-center gap-2 px-4 py-2.5 bg-emerald-500/15 border border-emerald-500/30
                        text-emerald-400 text-sm rounded-xl">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Profile updated successfully.
            </div>

            
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                    Full Name
                </label>
                <input wire:model="name" type="text"
                       class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm
                              text-white placeholder-slate-500 focus:border-indigo-500 outline-none transition">
                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="text-xs text-red-400 mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                    Email Address
                </label>
                <input wire:model="email" type="email"
                       class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm
                              text-white placeholder-slate-500 focus:border-indigo-500 outline-none transition">
                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="text-xs text-red-400 mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="pt-1">
                <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold
                               rounded-xl transition shadow-lg shadow-indigo-900/30">
                    <span wire:loading.remove wire:target="saveProfile">Save Changes</span>
                    <span wire:loading wire:target="saveProfile">Saving…</span>
                </button>
            </div>

        </form>
    </div>

    
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center gap-2">
            <svg class="w-4 h-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <h3 class="font-bold text-white text-sm"><?php echo e(__('app.profile.upload_picture')); ?></h3>
        </div>

        <div class="p-6 space-y-5">

            
            <div x-data="{ show: false }"
                 x-on:picture-saved.window="show = true; setTimeout(() => show = false, 3000)"
                 x-show="show" x-transition
                 class="flex items-center gap-2 px-4 py-2.5 bg-emerald-500/15 border border-emerald-500/30
                        text-emerald-400 text-sm rounded-xl">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <?php echo e(__('app.profile.upload_success')); ?>

            </div>

            
            <div x-data="{ show: false }"
                 x-on:picture-removed.window="show = true; setTimeout(() => show = false, 3000)"
                 x-show="show" x-transition
                 class="flex items-center gap-2 px-4 py-2.5 bg-amber-500/15 border border-amber-500/30
                        text-amber-400 text-sm rounded-xl">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Profile picture removed.
            </div>

            
            <?php if($pictureError): ?>
            <div class="flex items-center gap-2 px-4 py-2.5 bg-red-500/15 border border-red-500/30
                        text-red-400 text-sm rounded-xl">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <?php echo e($pictureError); ?>

            </div>
            <?php endif; ?>

            
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                    <?php echo e(__('app.profile.select_file')); ?>

                </label>
                <div class="relative">
                    <input wire:model="profilePicture" type="file" accept="image/jpeg,image/png,image/gif"
                           class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm
                                  text-white placeholder-slate-500 focus:border-violet-500 outline-none transition
                                  file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 
                                  file:text-sm file:font-semibold file:bg-violet-600 file:text-white
                                  hover:file:bg-violet-500">
                </div>
                <p class="text-xs text-slate-500 mt-2">
                    <?php echo e(__('app.profile.valid_format')); ?> • <?php echo e(__('app.profile.max_size')); ?>

                </p>
                <?php $__errorArgs = ['profilePicture'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="text-xs text-red-400 mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <?php if($profilePicture): ?>
            <div class="flex items-center gap-3 p-3 bg-slate-800 rounded-xl border border-slate-700">
                <img src="<?php echo e($profilePicture->temporaryUrl()); ?>" 
                     alt="Preview"
                     class="w-12 h-12 rounded object-cover">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white truncate"><?php echo e($profilePicture->getClientOriginalName()); ?></p>
                    <p class="text-xs text-slate-400"><?php echo e(number_format($profilePicture->getSize() / 1024, 1)); ?> KB</p>
                </div>
            </div>
            <?php endif; ?>

            
            <div class="flex gap-3 pt-1">
                <?php if($profilePicture): ?>
                <button wire:click="uploadProfilePicture"
                        class="flex-1 px-5 py-2.5 bg-violet-600 hover:bg-violet-500 text-white text-sm font-bold
                               rounded-xl transition shadow-lg shadow-violet-900/30">
                    <span wire:loading.remove wire:target="uploadProfilePicture"><?php echo e(__('app.profile.upload_picture')); ?></span>
                    <span wire:loading wire:target="uploadProfilePicture">Uploading…</span>
                </button>
                <?php endif; ?>
                
                <?php if($user->profile_picture): ?>
                <button wire:click="removeProfilePicture" wire:confirm="Remove profile picture?"
                        class="px-5 py-2.5 bg-red-600/20 hover:bg-red-600/30 text-red-400 text-sm font-bold
                               rounded-xl transition border border-red-600/30 hover:border-red-600/50">
                    <?php echo e(__('app.profile.remove_picture')); ?>

                </button>
                <?php endif; ?>
            </div>

        </div>
    </div>
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center gap-2">
            <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <h3 class="font-bold text-white text-sm">Change Password</h3>
        </div>

        <form wire:submit="changePassword" class="p-6 space-y-5">

            
            <div x-data="{ show: false }"
                 x-on:password-saved.window="show = true; setTimeout(() => show = false, 3000)"
                 x-show="show" x-transition
                 class="flex items-center gap-2 px-4 py-2.5 bg-emerald-500/15 border border-emerald-500/30
                        text-emerald-400 text-sm rounded-xl">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Password changed successfully.
            </div>

            
            <?php if($passwordError): ?>
            <div class="flex items-center gap-2 px-4 py-2.5 bg-red-500/15 border border-red-500/30
                        text-red-400 text-sm rounded-xl">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <?php echo e($passwordError); ?>

            </div>
            <?php endif; ?>

            
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                    Current Password
                </label>
                <input wire:model="current_password" type="password"
                       class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm
                              text-white placeholder-slate-500 focus:border-indigo-500 outline-none transition">
                <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="text-xs text-red-400 mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                    New Password
                    <span class="normal-case font-normal text-slate-600 ml-1">(min. 8 characters)</span>
                </label>
                <input wire:model="new_password" type="password"
                       class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm
                              text-white placeholder-slate-500 focus:border-indigo-500 outline-none transition">
                <?php $__errorArgs = ['new_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="text-xs text-red-400 mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                    Confirm New Password
                </label>
                <input wire:model="new_password_confirmation" type="password"
                       class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm
                              text-white placeholder-slate-500 focus:border-indigo-500 outline-none transition">
            </div>

            <div class="pt-1">
                <button type="submit"
                        class="px-5 py-2.5 bg-amber-600 hover:bg-amber-500 text-white text-sm font-bold
                               rounded-xl transition shadow-lg shadow-amber-900/30">
                    <span wire:loading.remove wire:target="changePassword">Update Password</span>
                    <span wire:loading wire:target="changePassword">Updating…</span>
                </button>
            </div>

        </form>
    </div>

</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/admin/profile/user-profile.blade.php ENDPATH**/ ?>