<?php

namespace App\Livewire\Store;

use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.marketplace')]
class PublicPageViewer extends Component
{
    public string $slug = '';

    public function mount(string $slug): void
    {
        $this->slug = $slug;
    }

    public function render()
    {
        $page = Page::published()->where('slug', $this->slug)->first();

        if (!$page) {
            abort(404);
        }

        return view('livewire.store.public-page-viewer', compact('page'))
            ->title($page->getTranslatedTitle() . ' — Al-Baraka');
    }
}
