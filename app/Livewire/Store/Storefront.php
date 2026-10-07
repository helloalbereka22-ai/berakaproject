<?php

namespace App\Livewire\Store;

use App\Models\Article;
use App\Services\StoreService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.marketplace')]
class Storefront extends Component
{
    public function render()
    {
        $store = app(StoreService::class);

        // Latest published articles (both blog + announcements)
        $latestArticles = Article::published()
            ->with(['category', 'author', 'coverImage'])
            ->orderByDesc('published_at')
            ->limit(4)
            ->get();

        // Latest announcements = articles with type guide OR status published + category "announcement"
        $announcements = Article::published()
            ->with(['coverImage'])
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return view('livewire.store.storefront', [
            'featured'      => $store->getFeatured(8),
            'newArrivals'   => $store->getNewArrivals(8),
            'bestSellers'   => $store->getBestSellers(6),
            'categories'    => $store->getMarketplaceCategories(),
            'latestArticles'=> $latestArticles,
            'announcements' => $announcements,
        ])->title('Al-Baraka Business Group — ' . __('app.hero.subheadline'));
    }
}
