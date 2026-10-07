<?php

namespace App\Livewire\Store;

use App\Models\Article;
use App\Models\FAQ;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.marketplace')]
class PublicSearch extends Component
{
    #[Url]
    public string $q = '';

    public string $activeTab = 'all';

    public function updatedQ(): void
    {
        $this->activeTab = 'all';
    }

    public function render()
    {
        $query   = trim($this->q);
        $results = ['products' => collect(), 'articles' => collect(), 'faqs' => collect()];

        if (strlen($query) >= 2) {
            // Products — public fields only, no cost/stock exposed
            $results['products'] = Product::withoutGlobalScopes()
                ->where('status', 'active')
                ->where('is_marketplace_visible', true)
                ->whereIn('type', \App\Models\Product::SELLABLE_TYPES)
                ->with(['category', 'primaryImage'])
                ->where(fn($q) =>
                    $q->where('name',        'like', "%{$query}%")
                      ->orWhere('description','like', "%{$query}%")
                      ->orWhere('sku',        'like', "%{$query}%")
                )
                ->orderBy('sort_order')
                ->limit(12)
                ->get();

            // Articles
            $results['articles'] = Article::published()
                ->with(['category', 'coverImage'])
                ->where(fn($q) =>
                    $q->where('title',   'like', "%{$query}%")
                      ->orWhere('body',   'like', "%{$query}%")
                      ->orWhere('excerpt','like', "%{$query}%")
                )
                ->orderByDesc('published_at')
                ->limit(6)
                ->get();

            // FAQs
            $results['faqs'] = FAQ::active()
                ->where(fn($q) =>
                    $q->where('question','like', "%{$query}%")
                      ->orWhere('answer', 'like', "%{$query}%")
                )
                ->orderBy('sort_order')
                ->limit(6)
                ->get();
        }

        $totalCount = $results['products']->count()
                    + $results['articles']->count()
                    + $results['faqs']->count();

        return view('livewire.store.public-search', compact('results', 'totalCount', 'query'))
            ->title(($query ? __('app.search_pub.results_for') . ' "' . $query . '"' : __('app.search_pub.title')) . ' — Al-Baraka');
    }
}
