<?php

namespace App\Livewire\Store;

use App\Models\Article;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCollection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.marketplace')]
class BookstorePage extends Component
{
    public function render()
    {
        // Featured books
        $featuredBooks = Product::withoutGlobalScopes()
            ->where('status', 'active')
            ->where('is_marketplace_visible', true)
            ->where('type', 'book')
            ->where('is_featured', true)
            ->with(['category', 'primaryImage', 'attributeValues.attribute'])
            ->orderBy('sort_order')
            ->limit(8)
            ->get();

        // New arrivals
        $newBooks = Product::withoutGlobalScopes()
            ->where('status', 'active')
            ->where('is_marketplace_visible', true)
            ->where('type', 'book')
            ->with(['category', 'primaryImage', 'attributeValues.attribute'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        // Book categories
        $bookCategories = Category::withoutGlobalScopes()
            ->where('is_active', true)
            ->whereHas('products', fn($q) =>
                $q->where('status', 'active')
                  ->where('type', 'book')
                  ->where('is_marketplace_visible', true)
            )
            ->withCount(['products as book_count' => fn($q) =>
                $q->where('status', 'active')->where('type', 'book')
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Collections (bundles)
        $collections = ProductCollection::with('products.primaryImage')
            ->orderBy('name')
            ->limit(4)
            ->get();

        // Bookstore branches
        $branches = Branch::with('business')
            ->whereHas('business', fn($q) => $q->where('status','active'))
            ->orderBy('name')
            ->get();

        return view('livewire.store.bookstore-page', compact(
            'featuredBooks', 'newBooks', 'bookCategories', 'collections', 'branches'
        ))->title(__('app.bookstore.title') . ' — Al-Baraka');
    }
}
