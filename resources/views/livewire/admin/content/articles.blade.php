<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-100">{{ __('app.cms.articles') }}</h1>
            <p class="text-slate-400 text-sm mt-0.5">Manage blog posts, product guides, and announcements.</p>
        </div>
        @can('content.create')
        <button wire:click="openCreate"
            class="flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Article
        </button>
        @endcan
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="px-4 py-3 bg-emerald-900/40 border border-emerald-700/50 text-emerald-300 rounded-xl text-sm flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search articles..."
                class="w-full pl-9 pr-4 py-2 bg-slate-800 border border-slate-700 text-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500">
        </div>
        <select wire:model.live="filterStatus"
            class="bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-xl px-3 py-2 focus:outline-none focus:border-indigo-500">
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="published">Published</option>
            <option value="archived">Archived</option>
        </select>
        <select wire:model.live="filterType"
            class="bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-xl px-3 py-2 focus:outline-none focus:border-indigo-500">
            <option value="">All Types</option>
            <option value="article">Article</option>
            <option value="guide">Product Guide</option>
        </select>
    </div>

    {{-- Articles table --}}
    <div class="bg-slate-900/60 border border-slate-800/60 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-[10px] font-bold uppercase text-slate-500 tracking-wider border-b border-slate-800/60">
                        <th class="px-5 py-3 text-left">Title</th>
                        <th class="px-5 py-3 text-left hidden md:table-cell">Category</th>
                        <th class="px-5 py-3 text-center">Type</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-left hidden lg:table-cell">Published</th>
                        <th class="px-5 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    @forelse($articles as $article)
                    <tr class="hover:bg-slate-800/20 transition {{ $article->trashed() ? 'opacity-50' : '' }}">
                        <td class="px-5 py-3.5">
                            <div class="flex items-start gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-200 truncate max-w-xs">{{ $article->title }}</p>
                                    <p class="text-slate-500 text-xs font-mono mt-0.5 truncate max-w-xs">{{ $article->slug }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 hidden md:table-cell">
                            <span class="text-slate-400 text-xs">{{ optional($article->category)->name ?? '—' }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold
                                {{ $article->article_type === 'guide' ? 'bg-amber-900/40 text-amber-400' : 'bg-slate-700 text-slate-300' }}">
                                {{ $article->article_type === 'guide' ? 'Guide' : 'Article' }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            @can('content.update')
                            <button wire:click="toggleStatus({{ $article->id }})"
                                wire:confirm="Toggle this article's status?"
                                class="px-2.5 py-1 text-[10px] font-bold rounded-full cursor-pointer transition
                                    {{ $article->status === 'published'
                                        ? 'bg-emerald-900/40 text-emerald-400 hover:bg-emerald-900/60'
                                        : ($article->status === 'archived'
                                            ? 'bg-slate-700 text-slate-400'
                                            : 'bg-amber-900/40 text-amber-400 hover:bg-amber-900/60') }}">
                                {{ ucfirst($article->status) }}
                            </button>
                            @else
                            <span class="px-2.5 py-1 text-[10px] font-bold rounded-full
                                {{ $article->status === 'published' ? 'bg-emerald-900/40 text-emerald-400' : 'bg-amber-900/40 text-amber-400' }}">
                                {{ ucfirst($article->status) }}
                            </span>
                            @endcan
                        </td>
                        <td class="px-5 py-3.5 text-slate-400 text-xs hidden lg:table-cell">
                            {{ $article->published_at?->format('M d, Y H:i') ?? '—' }}
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center justify-center gap-2">
                                {{-- Preview --}}
                                @if($article->status === 'published')
                                <a href="{{ route('blog.show', $article->slug) }}" target="_blank"
                                   class="p-1.5 text-slate-500 hover:text-indigo-400 transition rounded-lg hover:bg-slate-800">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                @endif
                                {{-- Edit --}}
                                @can('content.update')
                                @unless($article->trashed())
                                <button wire:click="openEdit({{ $article->id }})"
                                    class="p-1.5 text-slate-500 hover:text-emerald-400 transition rounded-lg hover:bg-slate-800">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                @endunless
                                @endcan
                                {{-- Delete --}}
                                @can('content.delete')
                                @unless($article->trashed())
                                <button wire:click="delete({{ $article->id }})"
                                    wire:confirm="Delete this article?"
                                    class="p-1.5 text-slate-500 hover:text-red-400 transition rounded-lg hover:bg-slate-800">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                                @endunless
                                @if($article->trashed())
                                <span class="text-xs text-slate-600 italic">Deleted</span>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-14 text-center text-slate-500">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="w-10 h-10 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                </svg>
                                <span>{{ __('app.cms.no_articles') }}</span>
                                @can('content.create')
                                <button wire:click="openCreate" class="text-indigo-400 text-sm font-semibold hover:underline">
                                    Create the first article →
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($articles->hasPages())
        <div class="px-5 py-4 border-t border-slate-800/60">
            {{ $articles->links() }}
        </div>
        @endif
    </div>

    {{-- ══ ARTICLE FORM MODAL ════════════════════════════════════════════ --}}
    @if($showForm)
    <div class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-start justify-center p-4 pt-16 overflow-y-auto">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-4xl shadow-2xl" @click.stop>
            {{-- Modal header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
                <h3 class="font-bold text-slate-100">{{ $editId ? 'Edit Article' : 'New Article' }}</h3>
                <button wire:click="$set('showForm', false)" class="p-1.5 text-slate-500 hover:text-slate-300 transition rounded-lg hover:bg-slate-800">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Form body --}}
            <form wire:submit="save" class="p-6 space-y-5">
                {{-- Title + slug --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1.5">Title <span class="text-red-500">*</span></label>
                        <input wire:model.live="title" type="text" required
                            class="w-full bg-slate-800 border border-slate-700 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 transition
                                   @error('title') border-red-500 @enderror">
                        @error('title')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1.5">Slug <span class="text-red-500">*</span></label>
                        <input wire:model="slug" type="text" required
                            class="w-full bg-slate-800 border border-slate-700 text-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:border-indigo-500 transition
                                   @error('slug') border-red-500 @enderror">
                        @error('slug')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Excerpt --}}
                <div>
                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1.5">Excerpt <span class="text-slate-600 normal-case font-normal">(optional short summary)</span></label>
                    <textarea wire:model="excerpt" rows="2"
                        class="w-full bg-slate-800 border border-slate-700 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 transition resize-none
                               @error('excerpt') border-red-500 @enderror"
                        placeholder="A brief summary shown in article cards and search results..."></textarea>
                    @error('excerpt')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Body --}}
                <div>
                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1.5">Body <span class="text-red-500">*</span></label>
                    <textarea wire:model="body" rows="12" required
                        class="w-full bg-slate-800 border border-slate-700 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 transition resize-y font-mono
                               @error('body') border-red-500 @enderror"
                        placeholder="Write your article content here. Basic HTML tags are supported (headings, paragraphs, lists, links, images)."></textarea>
                    @error('body')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                    <p class="text-[10px] text-slate-600 mt-1">Supported HTML: &lt;p&gt; &lt;h2&gt; &lt;h3&gt; &lt;ul&gt; &lt;ol&gt; &lt;li&gt; &lt;a&gt; &lt;strong&gt; &lt;em&gt; &lt;img&gt; &lt;blockquote&gt;</p>
                </div>

                {{-- Meta row: category, type, status, published_at --}}
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1.5">Category</label>
                        <select wire:model="categoryId"
                            class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-xl px-3 py-2.5 focus:outline-none focus:border-indigo-500">
                            <option value="">No category</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1.5">Type</label>
                        <select wire:model="articleType"
                            class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-xl px-3 py-2.5 focus:outline-none focus:border-indigo-500">
                            <option value="article">Article</option>
                            <option value="guide">Product Guide</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1.5">Status <span class="text-red-500">*</span></label>
                        <select wire:model="status"
                            class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-xl px-3 py-2.5 focus:outline-none focus:border-indigo-500
                                   @error('status') border-red-500 @enderror">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1.5">Publish Date</label>
                        <input wire:model="publishedAt" type="datetime-local"
                            class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-xl px-3 py-2.5 focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-between pt-2 border-t border-slate-800">
                    <button type="button" wire:click="$set('showForm', false)"
                        class="px-5 py-2.5 text-sm text-slate-400 hover:text-slate-200 border border-slate-700 rounded-xl transition hover:border-slate-600">
                        Cancel
                    </button>
                    <button type="submit"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-60 cursor-not-allowed"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition flex items-center gap-2">
                        <svg wire:loading class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span wire:loading.remove>{{ $editId ? 'Update Article' : 'Publish Article' }}</span>
                        <span wire:loading>Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
