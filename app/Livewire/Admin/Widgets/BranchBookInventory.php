<?php

namespace App\Livewire\Admin\Widgets;

use Livewire\Component;

class BranchBookInventory extends Component
{
    public $books = [];

    public function mount()
    {
        // Fetch mock data for the dashboard widget
        // In reality, this would query Inventory joined with Products where business_type = 'book_store'
        // filtered by the branch the user manages.
        
        $this->books = [
            ['id' => 101, 'title' => 'The Pragmatic Programmer', 'author' => 'Andrew Hunt', 'stock' => 15, 'status' => 'In Stock'],
            ['id' => 102, 'title' => 'Clean Code', 'author' => 'Robert C. Martin', 'stock' => 3, 'status' => 'Low Stock'],
            ['id' => 103, 'title' => 'Design Patterns', 'author' => 'Erich Gamma', 'stock' => 0, 'status' => 'Out of Stock'],
            ['id' => 104, 'title' => 'Refactoring', 'author' => 'Martin Fowler', 'stock' => 22, 'status' => 'In Stock'],
        ];
    }

    public function render()
    {
        return view('livewire.admin.widgets.branch-book-inventory');
    }
}
