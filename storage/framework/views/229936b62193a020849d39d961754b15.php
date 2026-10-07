<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" dir="<?php echo e(Session::get('is_rtl') ? 'rtl' : 'ltr'); ?>" class="h-full dark">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    <title><?php echo e($title ?? 'Admin Portal'); ?> — Al-Bareka Business Management System</title>
    <meta name="description" content="<?php echo e($description ?? 'Enterprise Administration Portal'); ?>">

    <!-- Favicon -->
    <?php
        $logoPath = app(\App\Services\SettingsService::class)->get('system_logo_path', '');
    ?>
    <?php if($logoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath)): ?>
        <link rel="icon" type="image/png" href="<?php echo e(asset('storage/' . $logoPath)); ?>">
    <?php else: ?>
        <link rel="icon" type="image/svg+xml" href="<?php echo e(asset('favicon.svg')); ?>">
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <?php if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'))): ?>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php else: ?>
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: { extend: { fontFamily: { sans: ['Plus Jakarta Sans', '-apple-system', 'sans-serif'] } } }
            }
        </script>
    <?php endif; ?>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>


    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; }
        .nav-link {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 12px; border-radius: 10px;
            font-size: 12px; font-weight: 600; letter-spacing: 0.03em;
            color: #94a3b8; transition: all 0.18s ease;
            text-decoration: none;
        }
        .nav-link:hover { color: #e2e8f0; background: rgba(30,41,59,0.8); transform: translateX(2px); }
        .nav-link.active {
            background: linear-gradient(135deg, rgba(79,70,229,0.85), rgba(109,40,217,0.85));
            color: #fff; box-shadow: 0 4px 15px rgba(79,70,229,0.25);
            border: 1px solid rgba(99,102,241,0.3);
        }
        .nav-section { padding: 16px 12px 6px; font-size: 10px; font-weight: 800;
            text-transform: uppercase; letter-spacing: 0.1em; color: #475569; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.15); border-radius: 9999px; }
    </style>

    <script>
        // Prevent Livewire navigation to avoid routing errors
        window.livewireNavigateDisabled = true;
        if (window.Livewire) {
            window.Livewire.hook('navigate.back', () => false);
            window.Livewire.hook('navigate.refresh', () => false);
        }
    </script>
</head>
<body class="h-full bg-slate-950 text-slate-100 antialiased" x-data="{ sidebarOpen: false }">

<div class="min-h-screen flex w-full">

    <!-- Mobile Backdrop -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-slate-950/80 backdrop-blur-md lg:hidden"></div>

    <!-- ════════════════════════════════════════════════════════
         SIDEBAR
         ════════════════════════════════════════════════════════ -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed lg:sticky top-0 inset-y-0 left-0 z-50 w-72 h-screen bg-slate-900 border-r border-slate-800/80 transform transition-transform duration-300 flex flex-col flex-shrink-0">

        <?php
            $settingsService = app(\App\Services\SettingsService::class);
            $systemLogoPath = $settingsService->get('system_logo_path', '');
            $hasCustomLogo = !empty($systemLogoPath) && \Storage::disk('public')->exists($systemLogoPath);
        ?>

        <!-- Logo -->
        <div class="flex items-center justify-between h-18 px-5 py-4 border-b border-slate-800/60">
            <a href="<?php echo e(route('admin.dashboard')); ?>" class="flex items-center gap-3 group">
                <?php if($hasCustomLogo): ?>
                    <div class="w-9 h-9 rounded-xl overflow-hidden shadow-lg border border-indigo-400/20 group-hover:scale-105 transition">
                        <img src="<?php echo e(asset('storage/' . $systemLogoPath)); ?>" alt="Logo" class="w-full h-full object-contain">
                    </div>
                <?php else: ?>
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center shadow-lg shadow-indigo-500/30 border border-indigo-400/20 group-hover:scale-105 transition">
                        <span class="text-white font-extrabold text-base">A</span>
                    </div>
                <?php endif; ?>
                <div>
                    <span class="text-sm font-extrabold text-white">Al-Bareka <span class="text-indigo-400">Business Management</span></span>
                    <p class="text-[9px] text-slate-500 uppercase tracking-widest font-semibold"><?php echo e(__('app.ui.admin_portal')); ?></p>
                </div>
            </a>
            <button @click="sidebarOpen = false" class="lg:hidden text-slate-500 hover:text-white p-1 rounded-lg hover:bg-slate-800">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Role Badge & User -->
        <div class="px-4 pt-3 pb-2 hidden">
            <div class="p-3 rounded-xl bg-slate-800/60 border border-slate-700/50 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-500 to-violet-500 flex items-center justify-center text-white font-bold text-sm shrink-0">
                    <?php echo e(substr(auth()->user()->name ?? 'A', 0, 1)); ?>

                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-slate-100 truncate"><?php echo e(auth()->user()->name ?? 'Administrator'); ?></p>
                    <?php if(auth()->user()->isSuperAdmin()): ?>
                        <span class="inline-flex items-center gap-1 text-[9px] font-bold text-violet-300 uppercase tracking-wider">
                            <span class="w-1.5 h-1.5 rounded-full bg-violet-400 animate-pulse"></span> Super Admin
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1 text-[9px] font-bold text-indigo-300 uppercase tracking-wider">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse"></span> Admin
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Navigation -->
                <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-3 pb-4 space-y-2 mt-2">

            <?php
                $isSeller = auth()->user()->roles->whereIn('name', ['Seller','Branch Seller','Sales Staff'])->isNotEmpty();
                // Detect manufacturer by permission: has manufacturing.produce but NOT business.update
                $isManufacturingStaff = auth()->user()->can('manufacturing.produce')
                    && !auth()->user()->can('business.update')
                    && !auth()->user()->isSuperAdmin();
                // Detect general manager: has business.update (Business Manager role)
                $isGeneralManager = auth()->user()->can('business.update')
                    && !auth()->user()->isSuperAdmin();
            ?>

            <!-- Dashboard — hidden for manufacturing staff, sellers, and GM (GM has own section) -->
            <?php if (! ($isManufacturingStaff || $isSeller || $isGeneralManager)): ?>
            <a href="<?php echo e(route('admin.dashboard')); ?>" class="nav-link <?php echo e(request()->routeIs('admin.dashboard') ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span><?php echo e(__('app.ui.dashboard')); ?></span>
            </a>
            <?php endif; ?>
            
            <!-- Seller Dashboard — only for sellers -->
            <?php if($isSeller): ?>
            <!-- This section has been moved to the main seller navigation below -->
            <?php endif; ?>

            <?php if(auth()->user()->isSuperAdmin()): ?>

    

   

     



    <a href="<?php echo e(route('admin.businesses.index')); ?>" class="nav-link <?php echo e(request()->routeIs('admin.businesses.*') ? 'active' : ''); ?>">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        <span><?php echo e(__('app.ui.business_management')); ?></span>
    </a>

    <a href="<?php echo e(route('admin.branches.index')); ?>" class="nav-link <?php echo e(request()->routeIs('admin.branches.*') ? 'active' : ''); ?>">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        <span><?php echo e(__('app.ui.branch_management')); ?></span>
    </a>

    <a href="<?php echo e(route('admin.category-manager')); ?>" class="nav-link <?php echo e(request()->routeIs('admin.category-manager') ? 'active' : ''); ?>">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
        <span><?php echo e(__('app.ui.category_management')); ?></span>
    </a>

    <a href="<?php echo e(route('admin.item-manager')); ?>" class="nav-link <?php echo e(request()->routeIs('admin.item-manager') ? 'active' : ''); ?>">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        <span><?php echo e(__('app.ui.item_management')); ?></span>
    </a>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('audit-log.view')): ?>
    <a href="<?php echo e(route('admin.system.audit-logs')); ?>" class="nav-link <?php echo e(request()->routeIs('admin.system.audit-logs') ? 'active' : ''); ?>">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span><?php echo e(__('app.ui.audit_logs')); ?></span>
    </a>
    <?php endif; ?>

    <a href="<?php echo e(route('admin.zakat')); ?>" class="nav-link <?php echo e(request()->routeIs('admin.zakat') ? 'active' : ''); ?>">
        <span class="text-base shrink-0">☪</span>
        <span><?php echo e(__('app.ui.zakat')); ?></span>
    </a>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('user.view')): ?>
    <a href="<?php echo e(route('admin.users.index')); ?>" class="nav-link <?php echo e(request()->routeIs('admin.users.*') ? 'active' : ''); ?>">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        <span><?php echo e(__('app.ui.users')); ?></span>
    </a>
    <?php endif; ?>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('role.view')): ?>
    <a href="<?php echo e(route('admin.roles.index')); ?>" class="nav-link <?php echo e(request()->routeIs('admin.roles.*') ? 'active' : ''); ?>">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        <span><?php echo e(__('app.ui.roles_permissions')); ?></span>
    </a>
    <?php endif; ?>

    

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('system.settings.view')): ?>
    <a href="<?php echo e(route('admin.system.settings')); ?>" class="nav-link <?php echo e(request()->routeIs('admin.system.settings', 'admin.settings.*') ? 'active' : ''); ?>">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756.2924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span><?php echo e(__('app.ui.system_settings')); ?></span>
    </a>
    <?php endif; ?>

<?php endif; ?> 

            <?php
                $isSeller = auth()->user()->roles->whereIn('name', ['Seller','Branch Seller','Sales Staff'])->isNotEmpty();
                // Detect manufacturer by permission: has manufacturing.produce but NOT business.update
                $isManufacturingStaff = auth()->user()->can('manufacturing.produce')
                    && !auth()->user()->can('business.update')
                    && !auth()->user()->isSuperAdmin();
                // Detect general manager: has business.update (Business Manager role)
                $isGeneralManager = auth()->user()->can('business.update')
                    && !auth()->user()->isSuperAdmin();
            ?>

            <?php if(!auth()->user()->isSuperAdmin()): ?>
            <!-- PRODUCTS — hidden for sellers, general manager and manufacturing staff -->
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('product.view')): ?>
            <?php if (! ($isSeller || $isGeneralManager || $isManufacturingStaff)): ?>
            <div x-data="{ open: <?php echo e(request()->routeIs('admin.products.*', 'admin.saleable-products.*', 'admin.categories.*') ? 'true' : 'false'); ?> }">
                <button @click="open = !open" class="nav-link w-full justify-between <?php echo e(request()->routeIs('admin.products.*', 'admin.saleable-products.*', 'admin.categories.*') ? 'active' : ''); ?>">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <span><?php echo e(__('app.ui.products')); ?></span>
                    </div>
                    <svg :class="open ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" class="pl-6 pr-2 py-1 space-y-0.5 mt-0.5" x-transition>
                    <a href="<?php echo e(route('admin.products.index')); ?>" class="nav-link !text-[11px] !py-1.5 <?php echo e(request()->routeIs('admin.products.index') ? '!text-white' : ''); ?>"><?php echo e(__('app.ui.manufactured_products')); ?></a>
                    <a href="<?php echo e(route('admin.saleable-products.index')); ?>" class="nav-link !text-[11px] !py-1.5 <?php echo e(request()->routeIs('admin.saleable-products.index') ? '!text-white' : ''); ?>"><?php echo e(__('app.ui.bookstore_products')); ?></a>
                    <a href="<?php echo e(route('admin.categories.index')); ?>" class="nav-link !text-[11px] !py-1.5 <?php echo e(request()->routeIs('admin.categories.index') ? '!text-white' : ''); ?>"><?php echo e(__('app.ui.categories')); ?></a>
                </div>
            </div>
            <?php endif; ?> 
            <?php endif; ?>


            <!-- RAW MATERIALS — hidden for sellers, general manager, and manufacturing staff (mfg staff have it in their top-level menu) -->
            <?php if (! ($isSeller || $isGeneralManager || $isManufacturingStaff)): ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('product.view')): ?>
            
            <div x-data="{ open: <?php echo e(request()->routeIs('admin.materials.*', 'admin.material-categories.*') ? 'true' : 'false'); ?> }">
                <button @click="open = !open" class="nav-link w-full justify-between <?php echo e(request()->routeIs('admin.materials.*', 'admin.material-categories.*') ? 'active' : ''); ?>">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                        <span><?php echo e(__('app.ui.raw_materials')); ?></span>
                    </div>
                    <svg :class="open ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" class="pl-6 pr-2 py-1 space-y-0.5 mt-0.5" x-transition>
                    <a href="<?php echo e(route('admin.materials.raw')); ?>" class="nav-link !text-[11px] !py-1.5 <?php echo e(request()->routeIs('admin.materials.raw') ? '!text-white' : ''); ?>"><?php echo e(__('app.ui.raw_materials')); ?></a>
                    <a href="<?php echo e(route('admin.materials.packaging')); ?>" class="nav-link !text-[11px] !py-1.5 <?php echo e(request()->routeIs('admin.materials.packaging') ? '!text-white' : ''); ?>"><?php echo e(__('app.ui.packaging_materials')); ?></a>
                    <a href="<?php echo e(route('admin.material-categories.index')); ?>" class="nav-link !text-[11px] !py-1.5 <?php echo e(request()->routeIs('admin.material-categories.index') ? '!text-white' : ''); ?>"><?php echo e(__('app.ui.categories')); ?></a>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <!-- PRICE MANAGEMENT — hidden for sellers, manufacturing staff, and general manager (GM has own section) -->
            <?php if (! ($isSeller || $isManufacturingStaff || $isGeneralManager)): ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('inventory.view')): ?>
            <a href="<?php echo e(route('admin.purchases.create')); ?>" class="nav-link <?php echo e(request()->routeIs('admin.purchases.create') ? 'active' : ''); ?>">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span><?php echo e(__('app.ui.price_management')); ?></span>
                </div>
            </a>
            <a href="<?php echo e(route('admin.purchases.transfer')); ?>" class="nav-link <?php echo e(request()->routeIs('admin.purchases.transfer') ? 'active' : ''); ?>">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    <span><?php echo e(__('app.ui.transfer_products')); ?></span>
                </div>
            </a>
            <?php endif; ?>
            <?php endif; ?>
            <!-- INVENTORY — hidden for sellers and general manager -->
            <?php if (! ($isSeller || $isGeneralManager)): ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('inventory.view')): ?>
            <div x-data="{ open: <?php echo e(request()->routeIs('admin.inventory.*') ? 'true' : 'false'); ?> }">
                <button @click="open = !open" class="nav-link w-full justify-between <?php echo e(request()->routeIs('admin.inventory.*') ? 'active' : ''); ?>">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <span><?php echo e(__('app.ui.inventory')); ?></span>
                    </div>
                    <svg :class="open ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" class="pl-6 pr-2 py-1 space-y-0.5 mt-0.5" x-transition>
                    <a href="<?php echo e(route('admin.inventory.overview')); ?>" class="nav-link !text-[11px] !py-1.5 <?php echo e(request()->routeIs('admin.inventory.overview') ? '!text-white' : ''); ?>"><?php echo e(__('app.ui.stock_overview')); ?></a>
                    <a href="<?php echo e(route('admin.inventory.movements')); ?>" class="nav-link !text-[11px] !py-1.5 <?php echo e(request()->routeIs('admin.inventory.movements') ? '!text-white' : ''); ?>"><?php echo e(__('app.ui.stock_movements')); ?></a>
                    <a href="<?php echo e(route('admin.inventory.transfers')); ?>" class="nav-link !text-[11px] !py-1.5 <?php echo e(request()->routeIs('admin.inventory.transfers') ? '!text-white' : ''); ?>"><?php echo e(__('app.ui.transfers')); ?></a>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <!-- MANUFACTURING — hidden for sellers and general manager -->
            <?php if (! ($isSeller || $isGeneralManager)): ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['inventory.view', 'manufacturing.view'])): ?>

            <?php if($isManufacturingStaff): ?>
            
            <a href="<?php echo e(route('admin.manufacturing.dashboard')); ?>"
               wire:navigate
               class="nav-link <?php echo e(request()->routeIs('admin.manufacturing.dashboard') ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span><?php echo e(__('app.ui.dashboard')); ?></span>
            </a>
            <a href="<?php echo e(route('admin.manufacturing.products')); ?>"
               wire:navigate
               class="nav-link <?php echo e(request()->routeIs('admin.manufacturing.products') && request()->get('tab') !== 'sell' ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span><?php echo e(__('app.ui.manufacturer_products')); ?></span>
            </a>
            <a href="<?php echo e(route('admin.manufacturing.products')); ?>?tab=sell"
               wire:navigate
               class="nav-link <?php echo e(request()->routeIs('admin.manufacturing.products') && request()->get('tab') === 'sell' ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span><?php echo e(__('app.ui.sell')); ?></span>
            </a>
            <a href="<?php echo e(route('admin.manufacturing.transferred-products')); ?>"
               wire:navigate
               class="nav-link <?php echo e(request()->routeIs('admin.manufacturing.transferred-products') ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <span><?php echo e(__('app.ui.transferred_products')); ?></span>
            </a>
            <a href="<?php echo e(route('admin.materials.raw')); ?>"
               wire:navigate
               class="nav-link <?php echo e(request()->routeIs('admin.materials.*', 'admin.material-categories.*') ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span><?php echo e(__('app.ui.raw_materials')); ?></span>
            </a>
            <a href="<?php echo e(route('admin.reports.index')); ?>"
               wire:navigate
               class="nav-link <?php echo e(request()->routeIs('admin.reports.*') ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span><?php echo e(__('app.ui.reports')); ?></span>
            </a>

            <?php else: ?>
            
            <div x-data="{ open: <?php echo e(request()->routeIs('admin.manufacturing.*') ? 'true' : 'false'); ?> }">
                <button @click="open = !open" class="nav-link w-full justify-between <?php echo e(request()->routeIs('admin.manufacturing.*') ? 'active' : ''); ?>">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <span><?php echo e(__('app.ui.manufacturing')); ?></span>
                    </div>
                    <svg :class="open ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" class="pl-6 pr-2 py-1 space-y-0.5 mt-0.5" x-transition>
                    <a href="<?php echo e(route('admin.manufacturing.production')); ?>" class="nav-link !text-[11px] !py-1.5 <?php echo e(request()->routeIs('admin.manufacturing.production') ? '!text-white' : ''); ?>"><?php echo e(__('app.ui.production')); ?></a>
                    <a href="<?php echo e(route('admin.manufacturing.batches')); ?>" class="nav-link !text-[11px] !py-1.5 <?php echo e(request()->routeIs('admin.manufacturing.batches') ? '!text-white' : ''); ?>"><?php echo e(__('app.ui.production_batches')); ?></a>
                    <a href="<?php echo e(route('admin.manufacturing.consumption')); ?>" class="nav-link !text-[11px] !py-1.5 <?php echo e(request()->routeIs('admin.manufacturing.consumption') ? '!text-white' : ''); ?>"><?php echo e(__('app.ui.material_consumption')); ?></a>
                    <a href="<?php echo e(route('admin.manufacturing.finished')); ?>" class="nav-link !text-[11px] !py-1.5 <?php echo e(request()->routeIs('admin.manufacturing.finished') ? '!text-white' : ''); ?>"><?php echo e(__('app.ui.finished_products')); ?></a>
                    <a href="<?php echo e(route('admin.manufacturing.products')); ?>" class="nav-link !text-[11px] !py-1.5 <?php echo e(request()->routeIs('admin.manufacturing.products') && !request()->get('tab') ? '!text-white' : ''); ?>">
                        <svg class="w-3 h-3 inline mr-1 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <?php echo e(__('app.ui.manufacturer_products')); ?>

                    </a>
                    <a href="<?php echo e(route('admin.manufacturing.products')); ?>?tab=sell" class="nav-link !text-[11px] !py-1.5 <?php echo e(request()->routeIs('admin.manufacturing.products') && request()->get('tab') === 'sell' ? '!text-white' : ''); ?>">
                        <svg class="w-3 h-3 inline mr-1 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" /></svg>
                        <?php echo e(__('app.ui.sell_products')); ?>

                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php endif; ?>
            <?php endif; ?>

            <!-- SALES — simplified for sellers, hidden for general manager -->
            <?php if (! ($isGeneralManager)): ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['order.view'])): ?>
            <?php if($isSeller): ?>
            
            <a href="<?php echo e(route('admin.branch-seller.dashboard')); ?>"
               class="nav-link <?php echo e(request()->routeIs('admin.branch-seller.dashboard') ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span><?php echo e(__('app.ui.dashboard')); ?></span>
            </a>

            <a href="<?php echo e(route('admin.seller.sales')); ?>"
               class="nav-link <?php echo e(request()->routeIs('admin.seller.sales') ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span><?php echo e(__('app.ui.sales')); ?></span>
            </a>

            <a href="<?php echo e(route('admin.saleable-products.index')); ?>"
               class="nav-link <?php echo e(request()->routeIs('admin.saleable-products.index') ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <span><?php echo e(__('app.ui.bookstore_products')); ?></span>
            </a>

            <a href="<?php echo e(route('admin.inventory.transfers')); ?>"
               class="nav-link <?php echo e(request()->routeIs('admin.inventory.transfers') ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <span><?php echo e(__('app.ui.transferred_products')); ?></span>
            </a>

            <a href="<?php echo e(route('admin.reports.index')); ?>"
               class="nav-link <?php echo e(request()->routeIs('admin.reports.*') ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span><?php echo e(__('app.ui.reports')); ?></span>
            </a>
            <?php else: ?>
            
            <div x-data="{ open: <?php echo e(request()->routeIs('admin.sales.*', 'admin.orders.*', 'admin.seller.*') ? 'true' : 'false'); ?> }">
                <button @click="open = !open" class="nav-link w-full justify-between <?php echo e(request()->routeIs('admin.sales.*', 'admin.orders.*', 'admin.seller.*') ? 'active' : ''); ?>">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        <span><?php echo e(__('app.ui.sales')); ?></span>
                    </div>
                    <svg :class="open ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" class="pl-6 pr-2 py-1 space-y-0.5 mt-0.5" x-transition>
                    <a href="<?php echo e(route('admin.seller.sales')); ?>" class="nav-link !text-[11px] !py-1.5 <?php echo e(request()->routeIs('admin.seller.sales') ? '!text-white' : ''); ?>">
                        <svg class="w-3 h-3 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <?php echo e(__('app.ui.sell_products')); ?>

                    </a>
                    <a href="<?php echo e(route('admin.sales.overview')); ?>" class="nav-link !text-[11px] !py-1.5 <?php echo e(request()->routeIs('admin.sales.overview') ? '!text-white' : ''); ?>"><?php echo e(__('app.ui.sales_pos')); ?></a>
                    <a href="<?php echo e(route('admin.orders.index')); ?>" class="nav-link !text-[11px] !py-1.5 <?php echo e(request()->routeIs('admin.orders.index') ? '!text-white' : ''); ?>"><?php echo e(__('app.ui.orders')); ?></a>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            <?php endif; ?> 

            <!-- MANAGE ORDERS — only for General Manager -->
            <?php if($isGeneralManager): ?>

            
            <div class="nav-section"><?php echo e(__('app.ui.operations')); ?></div>

            <a href="<?php echo e(route('admin.dashboard')); ?>" wire:navigate
               class="nav-link <?php echo e(request()->routeIs('admin.dashboard') ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span><?php echo e(__('app.ui.dashboard')); ?></span>
            </a>

            <a href="<?php echo e(route('admin.orders.index')); ?>" wire:navigate
               class="nav-link <?php echo e(request()->routeIs('admin.orders.*') ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                <span><?php echo e(__('app.ui.manage_orders')); ?></span>
            </a>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('inventory.view')): ?>
            <a href="<?php echo e(route('admin.purchases.create')); ?>" wire:navigate
               class="nav-link <?php echo e(request()->routeIs('admin.purchases.create') ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span><?php echo e(__('app.ui.price_management')); ?></span>
            </a>
            <a href="<?php echo e(route('admin.purchases.transfer')); ?>" wire:navigate
               class="nav-link <?php echo e(request()->routeIs('admin.purchases.transfer') ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span><?php echo e(__('app.ui.transfer_products')); ?></span>
            </a>
            <?php endif; ?>

            
            <div class="nav-section"><?php echo e(__('app.ui.content_comms')); ?></div>

            <a href="<?php echo e(route('admin.news')); ?>" wire:navigate
               class="nav-link <?php echo e(request()->routeIs('admin.news') ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                </svg>
                <span><?php echo e(__('app.ui.manage_news')); ?></span>
            </a>

            <a href="<?php echo e(route('admin.messages')); ?>" wire:navigate
               class="nav-link <?php echo e(request()->routeIs('admin.messages') ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span class="flex items-center gap-1"><?php echo e(__('app.ui.customer_messages')); ?>

                    <?php $unreadCount = \DB::table('contact_messages')->where('status', 'unread')->count(); ?>
                    <?php if($unreadCount > 0): ?>
                    <span class="px-1.5 py-0.5 bg-indigo-600 text-white text-[9px] font-black rounded-full"><?php echo e($unreadCount); ?></span>
                    <?php endif; ?>
                </span>
            </a>

            <a href="<?php echo e(route('admin.page-settings')); ?>" wire:navigate
               class="nav-link <?php echo e(request()->routeIs('admin.page-settings') ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <span><?php echo e(__('app.ui.page_settings')); ?></span>
            </a>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('report.view')): ?>
            <a href="<?php echo e(route('admin.reports.index')); ?>" wire:navigate
               class="nav-link <?php echo e(request()->routeIs('admin.reports.*') ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span><?php echo e(__('app.ui.reports')); ?></span>
            </a>
            <?php endif; ?>

            <?php endif; ?>

            
<?php endif; ?> 

            <!-- REPORTS — hidden for GM (already shown in GM block) and manufacturing staff (shown in their top-level menu) -->
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('report.view')): ?>
            <?php if (! ($isGeneralManager || $isManufacturingStaff || $isSeller)): ?>
            <a href="<?php echo e(route('admin.reports.index')); ?>"
               class="nav-link <?php echo e(request()->routeIs('admin.reports.*') ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span><?php echo e(__('app.ui.reports')); ?></span>
            </a>
            <?php endif; ?>
            <?php endif; ?>

            <!-- User Management — hidden for super admin and general manager -->
            <?php if (! (auth()->user()->isSuperAdmin() || $isGeneralManager)): ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['user.view', 'role.view'])): ?>
            <div x-data="{ open: <?php echo e(request()->routeIs('admin.users.*', 'admin.roles.*') ? 'true' : 'false'); ?> }">
                <button @click="open = !open" class="nav-link w-full justify-between <?php echo e(request()->routeIs('admin.users.*', 'admin.roles.*') ? 'active' : ''); ?>">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span><?php echo e(__('app.ui.user_management')); ?></span>
                    </div>
                    <svg :class="open ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" class="pl-6 pr-2 py-1 space-y-0.5 mt-0.5" x-transition>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('user.view')): ?>
                    <a href="<?php echo e(route('admin.users.index')); ?>" class="nav-link !text-[11px] !py-1.5 <?php echo e(request()->routeIs('admin.users.*') ? '!text-white' : ''); ?>"><?php echo e(__('app.ui.users')); ?></a>
                    <?php endif; ?>
                </div>
            </div>
            
<?php endif; ?>
            <!-- System Settings -->
            
            <?php if (! (auth()->user()->isSuperAdmin())): ?>
            <?php if (! ($isGeneralManager)): ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('system.settings.view')): ?>
            <a href="<?php echo e(route('admin.system.audit-logs')); ?>" class="nav-link <?php echo e(request()->routeIs('admin.system.audit-logs') ? 'active' : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span><?php echo e(__('app.ui.audit_logs')); ?></span>
            </a>
            <?php endif; ?>
            <?php endif; ?> 
            <?php endif; ?> 
<?php endif; ?> 
        </nav>

        <!-- User Footer -->
        <div class="border-t border-slate-800/60 p-4">
            <div class="flex items-center gap-3">
                <a href="<?php echo e(route('admin.profile')); ?>" wire:navigate
                   class="w-8 h-8 rounded-lg shrink-0 hover:scale-105 transition flex-shrink-0 overflow-hidden"
                   title="My Profile">
                    <?php if(auth()->user()->profile_picture): ?>
                        <img src="<?php echo e(asset('storage/' . auth()->user()->profile_picture)); ?>" 
                             alt="<?php echo e(auth()->user()->name); ?>"
                             class="w-full h-full object-cover rounded-lg"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="w-full h-full bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white font-bold text-xs" style="display:none;">
                            <?php echo e(substr(auth()->user()->name ?? 'A', 0, 1)); ?>

                        </div>
                    <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white font-bold text-xs">
                            <?php echo e(substr(auth()->user()->name ?? 'A', 0, 1)); ?>

                        </div>
                    <?php endif; ?>
                </a>
                <div class="flex-1 min-w-0">
                    <a href="<?php echo e(route('admin.profile')); ?>" wire:navigate
                       class="text-xs font-bold text-white truncate hover:text-indigo-300 transition block">
                        <?php echo e(auth()->user()->name ?? 'Administrator'); ?>

                    </a>
                    <p class="text-[10px] text-slate-500 truncate"><?php echo e(auth()->user()->email); ?></p>
                </div>
                <form method="POST" action="<?php echo e(route('logout')); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="p-1.5 text-slate-500 hover:text-red-400 hover:bg-slate-800 rounded-lg transition" title="Logout">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- ════════════════════════════════════════════════════════
         MAIN CONTENT
         ════════════════════════════════════════════════════════ -->
    <div class="flex-1 flex flex-col min-h-screen overflow-hidden pt-16">

        <!-- Top Header -->
        <header class="bg-slate-900/90 border-b border-slate-800/80 fixed top-0 right-0 left-0 lg:left-72 z-30 h-16 flex items-center justify-between px-4 sm:px-6 backdrop-blur-md">

            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = true" class="lg:hidden text-slate-400 hover:text-white p-2 rounded-xl bg-slate-800/80 border border-slate-700/50">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div>
                    <h1 class="text-base font-bold text-white tracking-tight"><?php echo e($title ?? 'Dashboard'); ?></h1>
                    <p class="text-[10px] text-slate-500 hidden sm:block">Al-Bareka Business Management System</p>
                </div>
            </div>

            <div class="flex items-center gap-3">

                <!-- Super Admin: Global Business Context Switcher -->
                <?php if(auth()->user()->isSuperAdmin()): ?>
                <div x-data="{ open: false }" class="relative hidden" id="business-context-switcher">
                    <button @click="open = !open" class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-violet-950/60 border border-violet-500/30 text-xs text-violet-300 font-semibold hover:bg-violet-900/50 transition">
                        <span class="w-2 h-2 rounded-full bg-violet-400"></span>
                        <span>
                            <?php if(session('admin_business_context')): ?>
                                <?php echo e(\App\Models\Business::find(session('admin_business_context'))?->name ?? 'All Businesses'); ?>

                            <?php else: ?>
                                All Businesses
                            <?php endif; ?>
                        </span>
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak @click.away="open = false"
                         class="absolute right-0 mt-2 w-56 bg-slate-900 border border-slate-700/60 rounded-xl shadow-2xl z-50 overflow-hidden">
                        <div class="p-1.5">
                            <a href="<?php echo e(url()->current()); ?>" wire:navigate
                               onclick="fetch('<?php echo e(route('admin.dashboard')); ?>', {method: 'GET'})"
                               class="block px-3 py-2 text-xs text-slate-300 hover:bg-slate-800 rounded-lg font-semibold transition">
                                🌐 All Businesses
                            </a>
                            <div class="border-t border-slate-800/50 my-1"></div>
                            <?php $__currentLoopData = \App\Models\Business::orderBy('name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $biz): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a href="#" @click.prevent="open = false"
                               class="block px-3 py-2 text-xs text-slate-400 hover:bg-slate-800 rounded-lg transition">
                                <?php echo e($biz->name); ?>

                            </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Search -->
                <div class="hidden relative">
                    <input type="text" placeholder="Search..." id="admin-search-input"
                           class="w-48 pl-8 pr-3 py-1.5 border border-slate-800 rounded-xl text-xs bg-slate-950 text-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/50 transition placeholder-slate-600 outline-none">
                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <!-- Notifications -->
                <button id="notifications-btn" class="relative p-2 rounded-xl text-slate-500 hover:text-white bg-slate-800/60 border border-slate-700/50 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </button>

                <!-- Language Switcher -->
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button @click="open = !open"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold text-slate-200 bg-slate-800/80 border border-slate-700/60 hover:bg-slate-700 hover:border-slate-600 transition shadow-md">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                        </svg>
                        <span class="font-bold tracking-wide"><?php echo e(strtoupper(Session::get('locale', app()->getLocale()))); ?></span>
                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-2 z-50">
                        <div class="px-3 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800"><?php echo e(__('app.ui.select_language')); ?></div>
                        <?php $__currentLoopData = \App\Models\Language::where('is_active', true)->orderBy('code')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <button type="button" @click="window.location.href='<?php echo e(route('language.switch')); ?>?lang=<?php echo e($lang->code); ?>'"
                            class="flex items-center justify-between w-full px-4 py-2.5 text-sm text-left transition
                                   <?php echo e(Session::get('locale', app()->getLocale()) === $lang->code 
                                       ? 'text-indigo-300 bg-indigo-900/40 border-l-2 border-indigo-500' 
                                       : 'text-slate-300 hover:bg-slate-800 hover:text-white'); ?>">
                            <span class="font-semibold"><?php echo e($lang->name); ?></span>
                            <span class="text-xs opacity-50 uppercase tracking-wide"><?php echo e($lang->code); ?></span>
                        </button>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>

                <!-- Account & Logout Dropdown -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-800/70 border border-slate-700/50 hover:bg-slate-800 hover:border-slate-600 transition text-sm font-semibold text-slate-200">
                        <?php if(auth()->user()->profile_picture): ?>
                            <img src="<?php echo e(asset('storage/' . auth()->user()->profile_picture)); ?>" 
                                 alt="<?php echo e(auth()->user()->name); ?>"
                                 class="w-6 h-6 rounded-lg object-cover shrink-0"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="w-6 h-6 rounded-lg bg-gradient-to-tr from-indigo-500 to-violet-500 flex items-center justify-center text-white font-bold text-xs shrink-0" style="display:none;">
                                <?php echo e(substr(auth()->user()->name ?? 'A', 0, 1)); ?>

                            </div>
                        <?php else: ?>
                            <div class="w-6 h-6 rounded-lg bg-gradient-to-tr from-indigo-500 to-violet-500 flex items-center justify-center text-white font-bold text-xs shrink-0">
                                <?php echo e(substr(auth()->user()->name ?? 'A', 0, 1)); ?>

                            </div>
                        <?php endif; ?>
                        <span class="hidden sm:block max-w-[120px] truncate text-xs"><?php echo e(auth()->user()->name ?? 'Account'); ?></span>
                        <svg :class="open ? 'rotate-180' : ''" class="w-3 h-3 text-slate-400 transition-transform shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-cloak @click.away="open = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-60 bg-slate-900 border border-slate-700/60 rounded-2xl shadow-2xl z-50 overflow-hidden">

                        
                        <div class="px-4 py-3 border-b border-slate-800/80 flex items-center gap-3">
                            <?php if(auth()->user()->profile_picture): ?>
                                <img src="<?php echo e(asset('storage/' . auth()->user()->profile_picture)); ?>" 
                                     alt="<?php echo e(auth()->user()->name); ?>"
                                     class="w-9 h-9 rounded-xl object-cover shrink-0"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-violet-500 flex items-center justify-center text-white font-bold text-sm shrink-0" style="display:none;">
                                    <?php echo e(substr(auth()->user()->name ?? 'A', 0, 1)); ?>

                                </div>
                            <?php else: ?>
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-violet-500 flex items-center justify-center text-white font-bold text-sm shrink-0">
                                    <?php echo e(substr(auth()->user()->name ?? 'A', 0, 1)); ?>

                                </div>
                            <?php endif; ?>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-white truncate"><?php echo e(auth()->user()->name ?? 'Administrator'); ?></p>
                                <p class="text-[10px] text-slate-500 truncate"><?php echo e(auth()->user()->email ?? ''); ?></p>
                                <?php if(auth()->user()->isSuperAdmin()): ?>
                                    <span class="inline-flex items-center gap-1 text-[9px] font-bold text-violet-300 uppercase tracking-wider mt-0.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-violet-400"></span> Super Admin
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-[9px] font-bold text-indigo-300 uppercase tracking-wider mt-0.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span> Admin
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        
                        <div class="p-2 space-y-0.5">
                            <a href="<?php echo e(route('admin.profile')); ?>" wire:navigate
                               class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold
                                      <?php echo e(request()->routeIs('admin.profile') ? 'bg-indigo-500/15 text-indigo-300' : 'text-slate-300 hover:bg-slate-800 hover:text-white'); ?>

                                      transition">
                                <svg class="w-4 h-4 <?php echo e(request()->routeIs('admin.profile') ? 'text-indigo-400' : 'text-slate-500'); ?>"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                My Profile
                            </a>

                            <div class="border-t border-slate-800/60 my-1"></div>

                            
                            <form method="POST" action="<?php echo e(route('logout')); ?>">
                                <?php echo csrf_field(); ?>
                                <button type="submit"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-red-400 hover:bg-red-500/10 hover:text-red-300 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6 pb-20">
            <?php echo e($slot); ?>

        </main>

        <!-- Footer -->
        <footer class="fixed bottom-0 right-0 left-0 lg:left-72 bg-slate-900 border-t border-slate-800 px-6 py-3 text-xs text-slate-400 flex items-center justify-center gap-6 z-20">
            <span class="font-semibold">&copy; <?php echo e(date('Y')); ?> Al-Bareka Business Management System</span>
            <span class="text-slate-700">|</span>
           <span class="flex items-center gap-1.5 font-semibold">
    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>

    <a href="https://t.me/provide_services2"
       target="_blank"
       rel="noopener noreferrer"
       class="hover:text-emerald-300 transition-colors">
        OmniTech Software Development Company
    </a>

    | 0910089936 
</span>
        </footer>
    </div>
</div>

<?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>


<script>
    // Ensure Livewire navigate is disabled globally
    document.addEventListener('livewire:initialized', () => {
        if (window.Livewire && window.Livewire.navigate) {
            try {
                window.Livewire.hook = function() {}; // Disable hook registration
            } catch (e) {}
        }
    });
</script>

</body>
</html>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/components/layouts/admin.blade.php ENDPATH**/ ?>