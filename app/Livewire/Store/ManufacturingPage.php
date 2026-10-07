<?php

namespace App\Livewire\Store;

use App\Models\Article;
use App\Models\Product;
use App\Services\StoreService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.marketplace')]
class ManufacturingPage extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void { $this->resetPage(); }

    public function render()
    {
        // Get manufactured products — only finished/bottle products, not raw materials/seeds
        $query = Product::withoutGlobalScopes()
            ->where('status', 'active')
            ->where('is_marketplace_visible', true)
            ->whereIn('type', ['finished_product', 'bottle_product'])
            ->with(['category', 'business'])
            ->select('*')
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($this->search) {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('sku', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $products = $query->paginate(24);

        // Related articles from CMS
        $articles = [];
        try {
            $articles = Article::where('status', 'published')
                ->orderByDesc('published_at')
                ->limit(3)
                ->get();
        } catch (\Exception $e) {
            // If articles table doesn't exist or has issues, just skip
            $articles = collect();
        }

        return view('livewire.store.manufacturing-page', compact('products', 'articles'))
            ->title(__('app.manufacturing.title') . ' — Al-Baraka');
    }
}
