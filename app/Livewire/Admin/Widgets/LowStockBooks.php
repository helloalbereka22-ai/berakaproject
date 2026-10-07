<?php

namespace App\Livewire\Admin\Widgets;

use Livewire\Component;

class LowStockBooks extends Component
{
    public $books = [];

    public function mount()
    {
        // Mock low-stock book data for Book Store managers
        $this->books = [
            ['id' => 1, 'title' => 'Clean Code', 'stock' => 3, 'min_stock' => 10],
            ['id' => 2, 'title' => 'Design Patterns', 'stock' => 0, 'min_stock' => 5],
            ['id' => 3, 'title' => 'Domain-Driven Design', 'stock' => 2, 'min_stock' => 8],
        ];
    }

    public function render()
    {
        return view('livewire.admin.widgets.low-stock-books');
    }
}
