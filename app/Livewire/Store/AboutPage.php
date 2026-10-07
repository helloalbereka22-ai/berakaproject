<?php

namespace App\Livewire\Store;

use App\Models\Business;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.marketplace')]
class AboutPage extends Component
{
    public function render()
    {
        $businesses = Business::active()->with('branches')->orderBy('name')->get();

        return view('livewire.store.about-page', compact('businesses'))
            ->title(__('app.about.title') . ' — Al-Baraka');
    }
}
