<div class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl border border-gray-100 hover:border-purple-200 transition-all duration-300 hover:-translate-y-1 flex flex-col">

    
    <a href="<?php echo e(route('store.product', $product->id)); ?>" class="block relative overflow-hidden bg-gradient-to-br from-gray-50 to-gray-100 aspect-square">
        <?php if($product->primaryImage): ?>
            <img src="<?php echo e(asset('storage/' . $product->primaryImage['path'])); ?>"
                 alt="<?php echo e($product->name); ?>"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                 loading="lazy">
        <?php else: ?>
            <div class="w-full h-full flex flex-col items-center justify-center text-gray-300">
                <svg class="w-14 h-14 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="text-xs text-gray-400">No Image</span>
            </div>
        <?php endif; ?>

        <?php if($product->is_featured): ?>
        <span class="absolute top-3 left-3 px-2.5 py-1 bg-gradient-to-r from-yellow-400 to-orange-400 text-white text-xs font-black rounded-full shadow-md">
            ⭐ Featured
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
            <h3 class="font-bold text-gray-900 text-sm leading-snug line-clamp-2 group-hover:text-purple-700 transition-colors duration-200 mb-3 min-h-[2.5rem]">
                <?php echo e($product->name); ?>

            </h3>
        </a>

        
        <div class="mb-4">
            <?php if($product->selling_price > 0): ?>
                <p class="text-xl font-black text-gray-900">
                    <span class="text-sm font-bold text-purple-600">ETB</span>
                    <?php echo e(number_format($product->selling_price, 2)); ?>

                </p>
            <?php else: ?>
                <p class="text-sm font-bold text-gray-400 italic">Contact for price</p>
            <?php endif; ?>
        </div>

        
        <div class="flex flex-col gap-2">
            
            <a href="<?php echo e(url('/guest/cart/add')); ?>?product_id=<?php echo e($product->id); ?>&quantity=1"
               onclick="
                   console.log('Adding product:', <?php echo e($product->id); ?>);
                   this.innerHTML='✅ Added!'; 
                   this.style.pointerEvents='none'; 
                   setTimeout(() => window.location.href=this.href, 600); 
                   return false;
               "
               class="w-full py-2.5 rounded-xl text-sm font-black transition-all duration-200 text-center
                      bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700
                      text-white shadow-sm hover:shadow-md hover:shadow-purple-200">
                🛒 Add to Cart
            </a>
        </div>
    </div>
</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/store/partials/product-card.blade.php ENDPATH**/ ?>