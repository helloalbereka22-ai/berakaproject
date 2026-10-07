<?php

namespace App\Livewire\Store;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.marketplace')]
class ContactPage extends Component
{
    public function render()
    {
        return view('livewire.store.contact-page');
    }
}