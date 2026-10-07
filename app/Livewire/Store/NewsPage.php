<?php

namespace App\Livewire\Store;

use App\Models\Article;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.marketplace')]
class NewsPage extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void { $this->resetPage(); }

    public function render()
    {
        $baseQuery = Article::where('article_type', 'news')
            ->where('status', 'published')
            ->where(fn($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));

        // Featured = latest published news
        $featured = (clone $baseQuery)->orderByDesc('published_at')->first();

        // Search
        if ($this->search) {
            $term = $this->search;
            $baseQuery->where(fn($q) =>
                $q->where('title',   'like', "%{$term}%")
                  ->orWhere('excerpt','like', "%{$term}%")
                  ->orWhere('body',   'like', "%{$term}%")
            );
        }

        // Recent (sidebar) — latest 6, excluding featured
        $recent = Article::where('article_type', 'news')
            ->where('status', 'published')
            ->where(fn($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->when($featured, fn($q) => $q->where('id', '!=', $featured->id))
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        // All published (for search results / more articles)
        $allNews = (clone $baseQuery)
            ->when($featured && !$this->search, fn($q) => $q->where('id', '!=', $featured->id))
            ->orderByDesc('published_at')
            ->paginate(6);

        return view('livewire.store.news-page', compact('featured', 'recent', 'allNews'))
            ->title('News — Al-Baraka');
    }
}
