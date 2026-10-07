<?php

namespace App\Livewire\Store;

use App\Models\FAQ;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.marketplace')]
class FaqPage extends Component
{
    #[Url]
    public string $search   = '';
    #[Url]
    public string $category = '';

    public function render()
    {
        $faqs = FAQ::active()
            ->when($this->search, function ($q) {
                $term = $this->search;
                $q->where(fn($sq) =>
                    $sq->where('question', 'like', "%{$term}%")
                       ->orWhere('answer',   'like', "%{$term}%")
                );
            })
            ->when($this->category, fn($q) => $q->where('category', $this->category))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // Group by category for accordion display
        $grouped   = $faqs->groupBy('category');
        $categories = FAQ::active()->whereNotNull('category')->pluck('category')->unique()->sort()->values();

        return view('livewire.store.faq-page', compact('grouped', 'categories'))
            ->title(__('app.nav_pub.faqs') . ' — Al-Baraka');
    }
}
