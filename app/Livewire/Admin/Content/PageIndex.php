<?php

namespace App\Livewire\Admin\Content;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.admin')]
class PageIndex extends Component
{
    public function render()
    {
        return view('livewire.admin.content.pages');
    }
}