<?php

namespace App\Livewire\Business;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Support\Facades\Storage;

class ProductMediaManager extends Component
{
    use WithFileUploads;

    public $product;
    public $photos = [];

    public function mount(Product $product)
    {
        $this->product = $product;
    }

    public function uploadImages()
    {
        $this->validate([
            'photos.*' => 'image|max:5120', // 5MB Max
        ]);

        $maxSort = $this->product->media()->max('sort_order') ?? 0;

        foreach ($this->photos as $photo) {
            $path = $photo->store('product-images', 'public');
            $isPrimary = $this->product->media()->count() === 0;

            ProductMedia::create([
                'product_id' => $this->product->id,
                'file_path' => $path,
                'is_primary' => $isPrimary,
                'sort_order' => ++$maxSort,
            ]);
        }

        $this->photos = [];
        $this->product->refresh();
        session()->flash('success', 'Images uploaded successfully.');
    }

    public function setPrimary($mediaId)
    {
        $this->product->media()->update(['is_primary' => false]);
        ProductMedia::where('id', $mediaId)->update(['is_primary' => true]);
        $this->product->refresh();
    }

    public function deleteImage($mediaId)
    {
        $media = ProductMedia::find($mediaId);
        if ($media) {
            Storage::disk('public')->delete($media->file_path);
            $media->delete();
            $this->product->refresh();
        }
    }

    public function moveUp($mediaId)
    {
        $media = ProductMedia::find($mediaId);
        if (!$media) return;

        $previous = ProductMedia::where('product_id', $this->product->id)
            ->where('sort_order', '<', $media->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();

        if ($previous) {
            $temp = $media->sort_order;
            $media->sort_order = $previous->sort_order;
            $previous->sort_order = $temp;
            $media->save();
            $previous->save();
            $this->product->refresh();
        }
    }

    public function moveDown($mediaId)
    {
        $media = ProductMedia::find($mediaId);
        if (!$media) return;

        $next = ProductMedia::where('product_id', $this->product->id)
            ->where('sort_order', '>', $media->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();

        if ($next) {
            $temp = $media->sort_order;
            $media->sort_order = $next->sort_order;
            $next->sort_order = $temp;
            $media->save();
            $next->save();
            $this->product->refresh();
        }
    }

    public function render()
    {
        return view('livewire.business.product-media-manager');
    }
}

