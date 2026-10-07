<?php

namespace App\Livewire\Store;

use App\Models\Article;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.marketplace')]
class ArticleDetail extends Component
{
    public string $slug = '';

    public function mount(string $slug): void
    {
        $this->slug = $slug;

        // Increment view count
        Article::published()->where('slug', $slug)->increment('view_count');
    }

    public function render()
    {
        $article = Article::published()
            ->with(['category', 'author', 'coverImage', 'tags'])
            ->where('slug', $this->slug)
            ->firstOrFail();

        // Related: same category, excluding current
        $related = Article::published()
            ->with(['coverImage'])
            ->where('category_id', $article->category_id)
            ->where('id', '!=', $article->id)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        $isRtl = Session()->get('is_rtl', false);

        return view('livewire.store.article-detail', compact('article', 'related', 'isRtl'))
            ->title($article->getTranslatedTitle() . ' — Al-Baraka');
    }
}
