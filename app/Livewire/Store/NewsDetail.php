<?php

namespace App\Livewire\Store;

use App\Models\Article;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.marketplace')]
class NewsDetail extends Component
{
    public string $slug = '';

    public function mount(string $slug): void
    {
        $this->slug = $slug;
        // Increment view count
        Article::where('article_type', 'news')
            ->where('status', 'published')
            ->where('slug', $slug)
            ->increment('view_count');
    }

    public function render()
    {
        $article = Article::where('article_type', 'news')
            ->where('status', 'published')
            ->where(fn($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where('slug', $this->slug)
            ->firstOrFail();

        $related = Article::where('article_type', 'news')
            ->where('status', 'published')
            ->where('id', '!=', $article->id)
            ->orderByDesc('published_at')
            ->limit(4)
            ->get();

        return view('livewire.store.news-detail', compact('article', 'related'))
            ->title($article->title . ' — Al-Baraka News');
    }
}
