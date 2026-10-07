<div class="space-y-6">

    {{-- ── Success / Error toasts ── --}}
    @if($successMessage)
    <div x-data x-init="setTimeout(() => $wire.set('successMessage',''), 4000)"
         class="flex items-center gap-2 px-4 py-3 bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-sm rounded-xl">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ $successMessage }}
    </div>
    @endif
    @if($errorMessage)
    <div class="flex items-center gap-2 px-4 py-3 bg-red-500/15 border border-red-500/30 text-red-400 text-sm rounded-xl">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ $errorMessage }}
    </div>
    @endif

    @if($mode === 'list')
    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- LIST VIEW                                                --}}
    {{-- ════════════════════════════════════════════════════════ --}}

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4
                bg-gradient-to-r from-slate-900 via-slate-800/60 to-slate-900
                border border-slate-700/40 rounded-2xl px-6 py-5">
        <div>
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1">Content Management</p>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">News Management</h1>
            <p class="text-sm text-slate-400 mt-1">Manage news articles displayed on the public website</p>
        </div>
        <button wire:click="createNews"
                class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500
                       text-white text-sm font-bold rounded-xl transition shadow-lg shadow-indigo-900/30 shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add News
        </button>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        @foreach([
            ['label'=>'Total',       'value'=>$stats['total'],       'color'=>'text-white',        'dot'=>'bg-slate-400'],
            ['label'=>'Published',   'value'=>$stats['published'],   'color'=>'text-emerald-400',  'dot'=>'bg-emerald-400'],
            ['label'=>'Drafts',      'value'=>$stats['draft'],       'color'=>'text-amber-400',    'dot'=>'bg-amber-400'],
            ['label'=>'Unpublished', 'value'=>$stats['unpublished'], 'color'=>'text-red-400',      'dot'=>'bg-red-400'],
            ['label'=>'Total Views', 'value'=>number_format($stats['views']), 'color'=>'text-sky-400', 'dot'=>'bg-sky-400'],
        ] as $s)
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <div class="flex items-center gap-1.5 mb-2">
                <span class="w-1.5 h-1.5 rounded-full {{ $s['dot'] }}"></span>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ $s['label'] }}</p>
            </div>
            <p class="text-2xl font-extrabold {{ $s['color'] }}">{{ $s['value'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Search & Filter --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input wire:model.live.debounce.300ms="search" type="text"
                   placeholder="Search news by title or description..."
                   class="w-full pl-9 pr-4 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-sm
                          text-white placeholder-slate-500 focus:border-indigo-500 outline-none transition">
        </div>
        <select wire:model.live="filterStatus"
                class="px-4 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-sm text-slate-300
                       focus:border-indigo-500 outline-none transition">
            <option value="">All Statuses</option>
            <option value="published">Published</option>
            <option value="draft">Draft</option>
            <option value="unpublished">Unpublished</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-950/60 border-b border-slate-800 text-[11px] uppercase tracking-widest text-slate-400">
                        <th class="text-left px-5 py-3.5 font-bold">Image</th>
                        <th class="text-left px-5 py-3.5 font-bold">Title</th>
                        <th class="text-center px-4 py-3.5 font-bold">Status</th>
                        <th class="text-right px-4 py-3.5 font-bold">Views</th>
                        <th class="text-left px-4 py-3.5 font-bold">Published</th>
                        <th class="text-left px-4 py-3.5 font-bold">Created</th>
                        <th class="text-center px-4 py-3.5 font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    @forelse($news as $item)
                    @php
                        $imgPath = $item->seo_metadata['news_image'] ?? null;
                        $statusConfig = [
                            'published'   => 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30',
                            'draft'       => 'bg-amber-500/20 text-amber-400 border border-amber-500/30',
                            'unpublished' => 'bg-red-500/20 text-red-400 border border-red-500/30',
                        ];
                        $sc = $statusConfig[$item->status] ?? 'bg-slate-700 text-slate-400';
                    @endphp
                    <tr class="hover:bg-slate-800/30 transition">
                        {{-- Thumbnail --}}
                        <td class="px-5 py-3">
                            <div class="w-16 h-12 rounded-lg overflow-hidden bg-slate-800 border border-slate-700 shrink-0">
                                @if($imgPath)
                                    <img src="{{ asset('storage/'.$imgPath) }}" alt="{{ $item->title }}"
                                         class="w-full h-full object-cover" loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                @endif
                            </div>
                        </td>
                        {{-- Title & excerpt --}}
                        <td class="px-5 py-3 max-w-xs">
                            <p class="font-semibold text-white text-sm truncate">{{ $item->title }}</p>
                            @if($item->excerpt)
                            <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ $item->excerpt }}</p>
                            @endif
                        </td>
                        {{-- Status badge --}}
                        <td class="px-4 py-3 text-center">
                            <span class="px-2.5 py-1 text-[10px] font-bold rounded-full {{ $sc }}">
                                {{ ucfirst($item->status) }}
                            </span>
                        </td>
                        {{-- Views --}}
                        <td class="px-4 py-3 text-right font-mono text-xs text-slate-400">
                            {{ number_format($item->view_count) }}
                        </td>
                        {{-- Published date --}}
                        <td class="px-4 py-3 text-xs text-slate-500">
                            {{ $item->published_at?->format('d M Y') ?? '—' }}
                        </td>
                        {{-- Created date --}}
                        <td class="px-4 py-3 text-xs text-slate-500">
                            {{ $item->created_at->format('d M Y') }}
                        </td>
                        {{-- Actions --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1.5">
                                {{-- View public --}}
                                @if($item->status === 'published')
                                <a href="{{ route('news.show', $item->slug) }}" target="_blank"
                                   class="p-1.5 text-slate-500 hover:text-sky-400 bg-slate-800 hover:bg-sky-500/10 rounded-lg transition"
                                   title="View">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                @endif
                                {{-- Edit --}}
                                <button wire:click="editNews({{ $item->id }})"
                                        class="p-1.5 text-slate-500 hover:text-indigo-400 bg-slate-800 hover:bg-indigo-500/10 rounded-lg transition" title="Edit">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                {{-- Publish / Unpublish --}}
                                <button wire:click="toggleStatus({{ $item->id }})"
                                        class="p-1.5 rounded-lg transition
                                               {{ $item->status === 'published'
                                                   ? 'text-slate-500 hover:text-amber-400 bg-slate-800 hover:bg-amber-500/10'
                                                   : 'text-slate-500 hover:text-emerald-400 bg-slate-800 hover:bg-emerald-500/10' }}"
                                        title="{{ $item->status === 'published' ? 'Unpublish' : 'Publish' }}">
                                    @if($item->status === 'published')
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                    @else
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    @endif
                                </button>
                                {{-- Delete --}}
                                <button wire:click="confirmDeleteNews({{ $item->id }})"
                                        class="p-1.5 text-slate-500 hover:text-red-400 bg-slate-800 hover:bg-red-500/10 rounded-lg transition" title="Delete">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-16 text-center">
                            <div class="text-4xl mb-3">📰</div>
                            <p class="text-slate-400 font-medium text-sm">No news articles found</p>
                            @if($search || $filterStatus)
                            <button wire:click="$set('search',''); $set('filterStatus','')"
                                    class="mt-3 text-xs text-indigo-400 hover:text-indigo-300 transition">
                                Clear filters
                            </button>
                            @else
                            <p class="text-slate-600 text-xs mt-1">Click "Add News" to create your first article</p>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($news->hasPages())
        <div class="px-5 py-4 border-t border-slate-800">{{ $news->links() }}</div>
        @endif
    </div>

    {{-- Delete confirm modal --}}
    @if($confirmDelete)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div class="bg-slate-900 border border-red-700/40 rounded-2xl p-7 w-full max-w-sm shadow-2xl text-center">
            <div class="w-14 h-14 rounded-full bg-red-500/15 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <h3 class="font-extrabold text-white text-lg mb-2">Delete News Article?</h3>
            <p class="text-slate-400 text-sm mb-6">This will permanently delete the article and its image. This action cannot be undone.</p>
            <div class="flex gap-3">
                <button wire:click="$set('confirmDelete', false)"
                        class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-bold rounded-xl transition">
                    Cancel
                </button>
                <button wire:click="deleteNews"
                        class="flex-1 py-2.5 bg-red-600 hover:bg-red-500 text-white text-sm font-bold rounded-xl transition">
                    Delete
                </button>
            </div>
        </div>
    </div>
    @endif

    @else
    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- CREATE / EDIT FORM                                       --}}
    {{-- ════════════════════════════════════════════════════════ --}}

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <button wire:click="backToList"
                class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <div>
            <h2 class="text-2xl font-extrabold text-white tracking-tight">
                {{ $mode === 'edit' ? 'Edit News Article' : 'Add New News' }}
            </h2>
            <p class="text-sm text-slate-400 mt-0.5">
                {{ $mode === 'edit' ? 'Update the news article details' : 'Create and publish a new news article' }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ── Main form ── --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Title --}}
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                    Title <span class="text-red-400">*</span>
                </label>
                <input wire:model="title" type="text"
                       placeholder="Enter news title..."
                       class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl text-sm
                              text-white placeholder-slate-500 focus:border-indigo-500 outline-none transition">
                @error('title')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Short Description --}}
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                    Short Description <span class="text-red-400">*</span>
                    <span class="text-slate-600 font-normal normal-case ml-1">(shown in news list)</span>
                </label>
                <textarea wire:model="excerpt" rows="3"
                          placeholder="Brief summary shown on the public news page..."
                          class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl text-sm
                                 text-white placeholder-slate-500 focus:border-indigo-500 outline-none transition resize-y"></textarea>
                @error('excerpt')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Full Content --}}
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                    Full Content <span class="text-red-400">*</span>
                    <span class="text-slate-600 font-normal normal-case ml-1">(full article text)</span>
                </label>
                <textarea wire:model="body" rows="12"
                          placeholder="Write the full news article content here..."
                          class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl text-sm
                                 text-white placeholder-slate-500 focus:border-indigo-500 outline-none transition resize-y leading-relaxed font-mono"></textarea>
                @error('body')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- ── Sidebar options ── --}}
        <div class="space-y-5">

            {{-- Publish actions --}}
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-3">
                <h3 class="font-bold text-white text-sm mb-4">Publish</h3>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Status</label>
                    <select wire:model="status"
                            class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm
                                   text-slate-300 focus:border-indigo-500 outline-none transition">
                        <option value="draft">📝 Draft</option>
                        <option value="published">✅ Published</option>
                        <option value="unpublished">🚫 Unpublished</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Publish Date</label>
                    <input wire:model="publishDate" type="date"
                           class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm
                                  text-slate-300 focus:border-indigo-500 outline-none transition">
                </div>

                <div class="pt-2 space-y-2">
                    <button wire:click="savePublish"
                            class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-xl transition">
                        <span wire:loading.remove>🚀 Publish News</span>
                        <span wire:loading>Publishing…</span>
                    </button>
                    <button wire:click="saveDraft"
                            class="w-full py-2.5 bg-slate-700 hover:bg-slate-600 text-slate-300 text-sm font-bold rounded-xl transition">
                        <span wire:loading.remove>💾 Save as Draft</span>
                        <span wire:loading>Saving…</span>
                    </button>
                    <button wire:click="backToList" type="button"
                            class="w-full py-2 text-slate-500 hover:text-slate-300 text-sm font-semibold transition">
                        Cancel
                    </button>
                </div>
            </div>

            {{-- Image upload --}}
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                <h3 class="font-bold text-white text-sm mb-4">
                    News Image {{ $mode === 'create' ? '*' : '' }}
                </h3>

                {{-- Current image (edit mode) --}}
                @if($mode === 'edit' && $currentImage)
                <div class="mb-4">
                    <p class="text-xs text-slate-500 mb-2">Current Image:</p>
                    <div class="w-full aspect-video rounded-xl overflow-hidden bg-slate-800">
                        <img src="{{ asset('storage/'.$currentImage) }}" alt="Current"
                             class="w-full h-full object-cover">
                    </div>
                </div>
                @endif

                {{-- Preview new image --}}
                @if($image)
                <div class="mb-4">
                    <p class="text-xs text-slate-500 mb-2">New Image Preview:</p>
                    <div class="w-full aspect-video rounded-xl overflow-hidden bg-slate-800">
                        <img src="{{ $image->temporaryUrl() }}" alt="Preview"
                             class="w-full h-full object-cover">
                    </div>
                </div>
                @elseif(!$currentImage)
                <div class="w-full aspect-video rounded-xl bg-slate-800 border-2 border-dashed border-slate-700
                            flex flex-col items-center justify-center mb-4">
                    <svg class="w-10 h-10 text-slate-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-xs text-slate-600">No image selected</p>
                </div>
                @endif

                <input wire:model="image" type="file" accept="image/jpg,image/jpeg,image/png,image/webp"
                       id="news-img" class="hidden">
                <label for="news-img"
                       class="w-full flex items-center justify-center gap-2 px-4 py-2.5
                              bg-slate-800 hover:bg-slate-700 border border-slate-600
                              text-slate-300 hover:text-white text-sm font-semibold
                              rounded-xl transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    {{ $image ? 'Change Image' : 'Upload Image' }}
                </label>

                <p class="text-[10px] text-slate-600 mt-2 text-center">JPG, JPEG, PNG, WEBP · Max 5MB</p>
                @error('image')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror

                <div wire:loading wire:target="image"
                     class="mt-2 text-xs text-indigo-400 flex items-center justify-center gap-1">
                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Processing image…
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
