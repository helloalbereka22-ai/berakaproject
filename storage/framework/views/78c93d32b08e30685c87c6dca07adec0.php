<div class="min-h-screen bg-gray-50">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        
        <?php if($products->isEmpty()): ?>
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <div class="w-28 h-28 bg-purple-100 rounded-full flex items-center justify-center mb-6 shadow-inner">
                <span class="text-5xl">📦</span>
            </div>
            <h3 class="text-2xl font-black text-gray-800 mb-2">No Products Yet</h3>
            <p class="text-gray-500 max-w-sm">Products will appear here once added by the administrator.</p>
        </div>
        <?php else: ?>

        
        <div class="flex items-center justify-between mb-8">
            <div>
                <p class="text-gray-500 text-sm font-medium">
                    Showing <span class="font-bold text-gray-900"><?php echo e($products->firstItem()); ?>–<?php echo e($products->lastItem()); ?></span> of <span class="font-bold text-gray-900"><?php echo e($products->total()); ?></span> products
                </p>
            </div>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/></svg>
                Sorted by name
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php echo $__env->make('livewire.store.partials.product-card', ['product' => $product], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <div class="mt-12 flex justify-center">
            <?php echo e($products->links()); ?>

        </div>
        <?php endif; ?>
    </div>

</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/store/product-list.blade.php ENDPATH**/ ?>