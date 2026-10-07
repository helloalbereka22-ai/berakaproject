<?php

namespace App\Livewire\Business;

use Livewire\Component;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoryManager extends Component
{
    public $categories;
    public $name;
    public $description;
    public $parent_id;

    public function mount()
    {
        $this->loadCategories();
    }

    public function loadCategories()
    {
        // Load only root categories, their children will be loaded recursively in the view or here
        $this->categories = Category::with('children')->whereNull('parent_id')->get();
    }

    public function saveCategory()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $slug = Str::slug($this->name);
        
        // Ensure unique slug
        $count = Category::where('slug', 'LIKE', $slug . '%')->count();
        if ($count > 0) {
            $slug = $slug . '-' . time();
        }

        Category::create([
            'name' => $this->name,
            'slug' => $slug,
            'description' => $this->description,
            'parent_id' => $this->parent_id ?: null,
        ]);

        session()->flash('success', 'Category created successfully.');
        $this->reset(['name', 'description', 'parent_id']);
        $this->loadCategories();
    }

    public function render()
    {
        return view('livewire.business.category-manager');
    }
}

