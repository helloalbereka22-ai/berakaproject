<div class="bg-gray-50 min-h-screen">

    <?php $img = $article->seo_metadata['news_image'] ?? null; ?>

    
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center gap-2 text-sm text-gray-500 flex-wrap">
                <a href="<?php echo e(route('home')); ?>" class="hover:text-indigo-600 transition font-medium">Home</a>
                <span class="text-gray-300">•</span>
                <a href="<?php echo e(route('news')); ?>" class="hover:text-indigo-600 transition font-medium">News</a>
                <span class="text-gray-300">•</span>
                <span class="text-gray-700 font-semibold truncate max-w-xs"><?php echo e($article->title); ?></span>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

            
            <div class="lg:col-span-2">
                <div class="bg-white border border-gray-100 rounded-3xl overflow-hidden shadow-sm">

                    
                    <?php if($img): ?>
                    <div class="relative w-full aspect-[16/9] overflow-hidden">
                        <img src="<?php echo e(asset('storage/'.$img)); ?>" alt="<?php echo e($article->title); ?>"
                             class="w-full h-full object-cover">
                        <span class="absolute top-4 right-4 px-3 py-1.5 bg-amber-500 text-white
                                     text-xs font-extrabold rounded-lg uppercase tracking-wide shadow-lg">
                            NEWS
                        </span>
                    </div>
                    <?php endif; ?>

                    <div class="p-7 sm:p-10">
                        
                        <div class="flex flex-wrap items-center gap-3 mb-4">
                            <span class="inline-flex items-center gap-1.5 text-xs text-gray-400">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <?php echo e($article->published_at?->format('d F Y')); ?>

                            </span>
                            <span class="inline-flex items-center gap-1.5 text-xs text-gray-400">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <?php echo e(number_format($article->view_count)); ?> views
                            </span>
                            <?php if($article->author): ?>
                            <span class="inline-flex items-center gap-1.5 text-xs text-gray-400">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <?php echo e($article->author->name); ?>

                            </span>
                            <?php endif; ?>
                        </div>

                        
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mb-5">
                            <?php echo e($article->title); ?>

                        </h1>

                        
                        <?php if($article->excerpt): ?>
                        <p class="text-lg text-gray-500 leading-relaxed mb-6 pb-6 border-b border-gray-100 font-medium">
                            <?php echo e($article->excerpt); ?>

                        </p>
                        <?php endif; ?>

                        
                        <div class="prose prose-gray max-w-none text-gray-700 leading-relaxed whitespace-pre-line text-sm sm:text-base">
                            <?php echo nl2br(e($article->body)); ?>

                        </div>
                    </div>
                </div>

                
                <div class="mt-6">
                    <a href="<?php echo e(route('news')); ?>"
                       class="inline-flex items-center gap-2 text-sm font-bold text-indigo-600 hover:text-indigo-700 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Back to News
                    </a>
                </div>
            </div>

            
            <div class="space-y-6">
                <?php if($related->isNotEmpty()): ?>
                <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                    <h3 class="font-extrabold text-gray-900 text-sm mb-5 flex items-center gap-2">
                        <div class="w-1 h-4 bg-amber-500 rounded-full"></div>
                        Related News
                    </h3>
                    <div class="space-y-4">
                        <?php $__currentLoopData = $related; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $rImg = $item->seo_metadata['news_image'] ?? null; ?>
                        <a href="<?php echo e(route('news.show', $item->slug)); ?>"
                           class="flex items-start gap-3 group hover:bg-gray-50 rounded-xl p-2 -mx-2 transition">
                            <div class="w-16 h-14 rounded-xl overflow-hidden bg-gray-100 shrink-0">
                                <?php if($rImg): ?>
                                    <img src="<?php echo e(asset('storage/'.$rImg)); ?>" alt="<?php echo e($item->title); ?>"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center bg-indigo-50">
                                        <svg class="w-5 h-5 text-indigo-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-800 leading-snug line-clamp-2
                                          group-hover:text-indigo-700 transition">
                                    <?php echo e($item->title); ?>

                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    <?php echo e($item->published_at?->format('d M Y')); ?>

                                </p>
                            </div>
                        </a>
                        <?php if(!$loop->last): ?>
                        <div class="border-t border-gray-100"></div>
                        <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
                <?php endif; ?>

                
                <div class="bg-gradient-to-br from-indigo-600 to-indigo-700 rounded-2xl p-6 text-white">
                    <h4 class="font-bold text-base mb-2">More News</h4>
                    <p class="text-indigo-200 text-sm mb-4 leading-relaxed">
                        Stay up to date with the latest news from Al-Baraka.
                    </p>
                    <a href="<?php echo e(route('news')); ?>"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30
                              text-white text-sm font-bold rounded-xl transition border border-white/20">
                        View All News →
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/store/news-detail.blade.php ENDPATH**/ ?>