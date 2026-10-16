<div class="min-h-screen bg-gray-50">

    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <?php if($products->isEmpty()): ?>
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <div class="w-28 h-28 bg-amber-100 rounded-full flex items-center justify-center mb-6">
                <span class="text-5xl">🏭</span>
            </div>
            <h3 class="text-2xl font-black text-gray-800 mb-2">No Products Yet</h3>
            <p class="text-gray-500 max-w-sm">Manufacturing products will appear here once added.</p>
        </div>
        <?php else: ?>

        
        <div class="flex items-center justify-between mb-8">
            <p class="text-gray-500 text-sm font-medium">
                Showing <span class="font-bold text-gray-900"><?php echo e($products->firstItem()); ?>–<?php echo e($products->lastItem()); ?></span>
                of <span class="font-bold text-gray-900"><?php echo e($products->total()); ?></span> manufactured
            </p>
        </div>

        
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl border border-gray-100 hover:border-amber-200 transition-all duration-300 hover:-translate-y-1 flex flex-col">

                
                <a href="<?php echo e(route('store.product', $product->id)); ?>" class="block relative overflow-hidden bg-gradient-to-br from-amber-50 to-orange-50 aspect-square">
                    <?php
                        $mfgImagePath = null;
                        if ($product->primaryImage) {
                            $mfgImagePath = is_array($product->primaryImage) 
                                ? ($product->primaryImage['path'] ?? null)
                                : ($product->primaryImage->path ?? null);
                        }
                    ?>
                    <?php if($mfgImagePath): ?>
                        <img src="<?php echo e(asset('storage/' . $mfgImagePath)); ?>"
                             alt="<?php echo e($product->name); ?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                             loading="lazy">
                    <?php else: ?>
                        <div class="w-full h-full flex flex-col items-center justify-center text-amber-200">
                            <svg class="w-16 h-16 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                            </svg>
                            <span class="text-xs font-medium text-amber-300">No Image</span>
                        </div>
                    <?php endif; ?>

                    
                    <?php if($product->package_size): ?>
                    <span class="absolute top-3 right-3 px-2.5 py-1 bg-black/60 backdrop-blur-sm text-white text-xs font-bold rounded-full">
                        <?php echo e($product->package_size); ?>

                    </span>
                    <?php endif; ?>

                    
                    <?php if($product->category): ?>
                    <span class="absolute bottom-3 left-3 px-2.5 py-1 bg-black/50 backdrop-blur-sm text-white text-xs font-semibold rounded-full">
                        <?php echo e($product->category->name); ?>

                    </span>
                    <?php endif; ?>
                </a>

                
                <div class="p-4 flex flex-col flex-1">
                    <a href="<?php echo e(route('store.product', $product->id)); ?>" class="block flex-1">
                        <h3 class="font-bold text-gray-900 text-sm leading-snug line-clamp-2 group-hover:text-amber-700 transition-colors duration-200 mb-3 min-h-[2.5rem]">
                            <?php echo e($product->name); ?>

                        </h3>
                    </a>

                    
                    <div class="flex items-end justify-between mt-auto mb-4">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-0.5">Price</p>
                            <?php if($product->selling_price > 0): ?>
                                <p class="text-xl font-black text-gray-900">
                                    <span class="text-sm font-bold text-amber-600">ETB</span>
                                    <?php echo e(number_format($product->selling_price, 2)); ?>

                                </p>
                            <?php else: ?>
                                <p class="text-sm font-bold text-gray-400 italic">Contact for price</p>
                            <?php endif; ?>
                        </div>
                        <?php if($product->unit): ?>
                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-lg font-medium">
                            / <?php echo e($product->unit); ?>

                        </span>
                        <?php endif; ?>
                    </div>

                    
                    
                    <div class="flex flex-col gap-2">
                        <a href="<?php echo e(url('/guest/cart/add')); ?>?product_id=<?php echo e($product->id); ?>&quantity=1"
                           onclick="this.innerHTML='✅ Added!'; this.style.pointerEvents='none';"
                           class="w-full py-2.5 rounded-xl text-sm font-black text-center transition-all duration-200
                                  bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600
                                  text-white shadow-sm hover:shadow-md hover:shadow-amber-200">
                            🛒 Add to Cart
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <div class="mt-12 flex justify-center">
            <?php echo e($products->links()); ?>

        </div>
        <?php endif; ?>
    </div>

    
    <div class="bg-gradient-to-r from-amber-700 to-orange-700 mt-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center text-white">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-14 h-14 bg-white/10 rounded-2xl flex items-center justify-center text-3xl">🌿</div>
                    <h3 class="text-lg font-black">100% Natural</h3>
                    <p class="text-amber-200 text-sm">Made from pure natural seeds and ingredients</p>
                </div>
                <div class="flex flex-col items-center gap-3">
                    <div class="w-14 h-14 bg-white/10 rounded-2xl flex items-center justify-center text-3xl">🔬</div>
                    <h3 class="text-lg font-black">Quality Tested</h3>
                    <p class="text-amber-200 text-sm">Every batch inspected before release</p>
                </div>
                <div class="flex flex-col items-center gap-3">
                    <div class="w-14 h-14 bg-white/10 rounded-2xl flex items-center justify-center text-3xl">🚚</div>
                    <h3 class="text-lg font-black">Fast Delivery</h3>
                    <p class="text-amber-200 text-sm">Available for individual and bulk orders</p>
                </div>
            </div>
        </div>
    </div>

</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/store/manufacturing-page.blade.php ENDPATH**/ ?>