<div>


<div class="bg-gradient-to-br from-gray-900 to-indigo-950 text-white py-20">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl sm:text-5xl font-extrabold mb-5"><?php echo e(__('app.organizations.title')); ?></h1>
        <p class="text-indigo-300 text-xl max-w-2xl mx-auto"><?php echo e(__('app.organizations.subtitle')); ?></p>
    </div>
</div>

<section class="py-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <?php if(collect($businesses)->isEmpty()): ?>
    <div class="text-center py-24 text-gray-500">
        <span class="text-5xl mb-4 block">🏢</span>
        <p class="font-semibold">No organizations found.</p>
    </div>
    <?php else: ?>
    <div class="space-y-16">
        <?php $__currentLoopData = $businesses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php $biz = $item['model']; ?>
        <div class="bg-white border border-gray-200 rounded-3xl overflow-hidden hover:shadow-xl transition-shadow">

            
            <div class="h-2 bg-gradient-to-r <?php echo e($loop->odd ? 'from-amber-500 to-orange-500' : 'from-emerald-500 to-teal-500'); ?>"></div>

            <div class="p-8 md:p-10">
                <div class="flex flex-col md:flex-row md:items-start justify-between gap-6 mb-8">
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 rounded-2xl <?php echo e($loop->odd ? 'bg-amber-50 border-amber-200' : 'bg-emerald-50 border-emerald-200'); ?> border flex items-center justify-center text-3xl shrink-0">
                            <?php echo e($loop->odd ? '🫗' : '📚'); ?>

                        </div>
                        <div>
                            <h2 class="text-2xl font-extrabold text-gray-900"><?php echo e($biz->name); ?></h2>
                            <?php if($biz->code): ?>
                            <span class="inline-block mt-1 px-2.5 py-0.5 bg-gray-100 text-gray-600 text-xs font-mono rounded-lg"><?php echo e($biz->code); ?></span>
                            <?php endif; ?>
                            <span class="inline-block mt-1 ms-2 px-2.5 py-0.5 text-xs font-semibold rounded-full
                                <?php echo e($biz->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-500'); ?>">
                                <?php echo e(ucfirst($biz->status)); ?>

                            </span>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3 shrink-0">
                        
                        <?php if(strpos(trim($biz->name), 'Factory') !== false): ?>
                            <a href="<?php echo e(route('manufacturing')); ?>"
                               class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold rounded-xl transition">
                                <?php echo e(__('app.organizations.learn_more')); ?>

                            </a>
                        
                        <?php elseif(strpos(trim($biz->name), 'Book') !== false && $item['productCount'] > 0): ?>
                            <a href="<?php echo e(route('store.products', ['type' => 'book'])); ?>"
                               class="px-5 py-2.5 border border-gray-200 text-gray-700 hover:border-indigo-300 hover:text-indigo-700 text-sm font-semibold rounded-xl transition">
                                <?php echo e(__('app.organizations.view_products')); ?> (<?php echo e($item['productCount']); ?>)
                            </a>
                        <?php endif; ?>
                    </div>

                <?php if($biz->description): ?>
                <p class="text-gray-600 leading-relaxed mb-8"><?php echo e($biz->description); ?></p>
                <?php endif; ?>

                
                <div class="mb-8">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4"><?php echo e(__('app.organizations.capabilities')); ?></h3>
                    <div class="flex flex-wrap gap-2">
                        <?php $__currentLoopData = $loop->odd
                            ? ['Raw Material Processing', 'Quality Certified Production', 'Multiple Package Sizes', 'Batch Traceability']
                            : ['Wide Book Selection', 'Multi-Branch Network', 'Online Marketplace', 'Book Collections']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cap): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <span class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 border border-gray-200 text-gray-700 text-xs font-semibold rounded-full">
                            <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <?php echo e($cap); ?>

                        </span>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>

                
                <?php if($item['branches']->isNotEmpty()): ?>
                <div class="border-t border-gray-100 pt-6">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">
                        <?php echo e(__('app.organizations.our_branches')); ?> (<?php echo e($item['branches']->count()); ?>)
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                        <?php $__currentLoopData = $item['branches']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="flex items-center gap-2.5 px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl">
                            <span class="text-base shrink-0">📍</span>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-gray-800 truncate"><?php echo e($branch->name); ?></p>
                                <?php if($branch->description): ?>
                                <p class="text-[10px] text-gray-400 truncate"><?php echo e($branch->description); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
                <?php endif; ?>

                
                <?php if($item['articles']->isNotEmpty()): ?>
                <div class="border-t border-gray-100 pt-6 mt-6">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">Related Articles</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php $__currentLoopData = $item['articles']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $article): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('blog.show', $article->slug)); ?>"
                           class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 border border-gray-200 hover:border-indigo-200 hover:bg-indigo-50 transition group">
                            <div class="w-12 h-12 rounded-lg bg-indigo-100 shrink-0 overflow-hidden flex items-center justify-center">
                                <?php if($article->coverImage): ?>
                                    <img src="<?php echo e(Storage::url($article->coverImage->file_path)); ?>" alt="" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="text-lg">📰</span>
                                <?php endif; ?>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-gray-900 line-clamp-2 group-hover:text-indigo-700 transition"><?php echo e($article->getTranslatedTitle()); ?></p>
                                <p class="text-[10px] text-gray-400 mt-0.5"><?php echo e($article->published_at?->format('M d, Y')); ?></p>
                            </div>
                        </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>
</section>

</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/store/organizations-page.blade.php ENDPATH**/ ?>