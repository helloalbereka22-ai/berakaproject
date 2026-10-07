<?php

namespace App\Livewire\Store;

use App\Models\Article;
use App\Models\Category;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.marketplace')]
class ArticleList extends Component
{
    use WithPagination;

    #[Url]
    public string $search     = '';
    #[Url]
    public string $categoryId = '';
    #[Url]
    public string $type       = ''; // article | guide

    // Derived from the route name so the same component serves /blog and /announcements
    public string $pageMode   = 'blog'; // blog | announcements

    public function mount(): void
    {
        $this->pageMode = request()->routeIs('announcements*') ? 'announcements' : 'blog';
    }

    public function updatedSearch(): void   { $this->resetPage(); }
    public function updatedCategoryId(): void { $this->resetPage(); }
    public function updatedType(): void       { $this->resetPage(); }

    public function render()
    {
        $query = Article::published()
            ->with(['category', 'author', 'coverImage'])
            ->when($this->search, function ($q) {
                $term = $this->search;
                $q->where(fn($sq) =>
                    $sq->where('title', 'like', "%{$term}%")
                       ->orWhere('body',  'like', "%{$term}%")
                       ->orWhere('excerpt','like',"%{$term}%")
                );
            })
            ->when($this->categoryId, fn($q) => $q->where('category_id', $this->categoryId))
            ->when($this->type,       fn($q) => $q->where('article_type', $this->type))
            ->orderByDesc('published_at');

        // Only show categories that have at least one published article
        $categories = Category::withoutGlobalScopes()
            ->where('is_active', true)
            ->whereIn('id',
                \App\Models\Article::published()
                    ->whereNotNull('category_id')
                    ->select('category_id')
                    ->distinct()
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        $title = $this->pageMode === 'announcements'
            ? __('app.nav_pub.announcements')
            : __('app.nav_pub.blog');

        return view('livewire.store.article-list', [
            'articles'       => $query->paginate(12),
            'categories'     => $categories,
            'pageTitle'      => $title,
            'pageMode'       => $this->pageMode,
            'articleTypes'   => [
                ''        => __('app.cms.all_categories'),
                'article' => __('app.cms.articles'),
                'guide'   => __('app.cms.guides'),
            ],
        ])->title($title . ' — Al-Baraka');
    }
}
