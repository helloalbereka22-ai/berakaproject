<?php

namespace App\Livewire\Store;

use App\Models\Article;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.marketplace')]
class OrganizationsPage extends Component
{
    public function render()
    {
        $businesses = Business::active()
            ->withCount('branches')
            ->orderBy('name')
            ->get()
            ->map(function (Business $biz) {
                // Public product count — no cost/stock data exposed
                $productCount = Product::withoutGlobalScopes()
                    ->where('business_id', $biz->id)
                    ->where('status', 'active')
                    ->where('is_marketplace_visible', true)
                    ->count();

                $branches = Branch::where('business_id', $biz->id)
                    ->where('status', 'active')
                    ->orderBy('name')
                    ->get(['id','name','description']);

                // Latest related articles
                $articles = Article::published()
                    ->with(['coverImage'])
                    ->orderByDesc('published_at')
                    ->limit(2)
                    ->get();

                return [
                    'model'        => $biz,
                    'productCount' => $productCount,
                    'branches'     => $branches,
                    'articles'     => $articles,
                ];
            });

        return view('livewire.store.organizations-page', compact('businesses'))
            ->title(__('app.organizations.title') . ' — Al-Baraka');
    }
}
