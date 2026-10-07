<?php

namespace App\Livewire\Business;

use Livewire\Component;
use App\Models\ProductAttribute;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;

class AttributeManager extends Component
{
    public $categories;
    public $selectedCategoryId = null;
    public $category_attributes = [];

    public $name = '';
    public $type = 'text'; // text, select, boolean

    protected $rules = [
        'selectedCategoryId' => 'required|exists:categories,id',
        'name' => 'required|string|max:255',
        'type' => 'required|in:text,select,boolean',
    ];

    public function mount()
    {
        $this->categories = Category::all();
    }

    public function updatedSelectedCategoryId($categoryId)
    {
        $this->loadAttributes();
    }

    public function loadAttributes()
    {
        if ($this->selectedCategoryId) {
            $this->category_attributes = ProductAttribute::where('category_id', $this->selectedCategoryId)->get();
        } else {
            $this->category_attributes = [];
        }
    }

    public function saveAttribute()
    {
        $this->validate();

        ProductAttribute::create([
            'category_id' => $this->selectedCategoryId,
            'name' => $this->name,
            'type' => $this->type,
        ]);

        $this->reset(['name', 'type']);
        $this->type = 'text'; // default
        $this->loadAttributes();
        
        session()->flash('success', 'Attribute created successfully.');
    }

    public function deleteAttribute($id)
    {
        ProductAttribute::find($id)?->delete();
        $this->loadAttributes();
        session()->flash('success', 'Attribute deleted.');
    }

    public function render()
    {
        return view('livewire.business.attribute-manager');
    }
}

