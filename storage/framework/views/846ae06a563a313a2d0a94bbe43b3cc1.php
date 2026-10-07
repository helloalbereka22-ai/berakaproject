<?php
    $settingsService = app(\App\Services\SettingsService::class);
    $loginBgPath = $settingsService->get('login_bg_path', '');
    $hasCustomBg = !empty($loginBgPath) && \Storage::disk('public')->exists($loginBgPath);
?>

<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Al-Baraka Business Group</title>
    <meta name="description" content="Login to Albareka Enterprise Management Platform">

    <?php if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'))): ?>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php else: ?>
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: { 
                    extend: { 
                        fontFamily: { 
                            sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'] 
                        } 
                    } 
                }
            }
        </script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php endif; ?>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

</head>
<body class="text-gray-900 min-h-screen flex items-center justify-center p-4 antialiased relative overflow-hidden <?php if(!$hasCustomBg): ?> bg-gradient-to-br from-indigo-600 via-purple-600 to-blue-700 <?php endif; ?>">

    <?php if($hasCustomBg): ?>
        
        <div class="absolute inset-0 z-0">
            <img src="<?php echo e(asset('storage/' . $loginBgPath)); ?>" alt="Login Background" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-br from-black/60 via-black/40 to-black/60"></div>
        </div>
    <?php else: ?>
        
        <div class="absolute inset-0 opacity-20 pointer-events-none">
            <div class="absolute top-0 -left-4 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl animate-blob"></div>
            <div class="absolute top-0 -right-4 w-72 h-72 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl animate-blob animation-delay-2000"></div>
            <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-300 rounded-full mix-blend-multiply filter blur-xl animate-blob animation-delay-4000"></div>
        </div>
    <?php endif; ?>

    <style>
        @keyframes blob {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(20px, -50px) scale(1.1); }
            50% { transform: translate(-20px, 20px) scale(0.9); }
            75% { transform: translate(50px, 50px) scale(1.05); }
        }
        .animate-blob {
            animation: blob 7s infinite;
        }
        .animation-delay-2000 {
            animation-delay: 2s;
        }
        .animation-delay-4000 {
            animation-delay: 4s;
        }
    </style>

    <div class="w-full max-w-md relative z-10">
        <?php echo e($slot); ?>

    </div>

    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

    <?php if(!file_exists(public_path('build/manifest.json')) && !file_exists(public_path('hot'))): ?>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <?php endif; ?>
</body>
</html>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/components/layouts/guest.blade.php ENDPATH**/ ?>