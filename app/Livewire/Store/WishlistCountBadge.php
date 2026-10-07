<?php

namespace App\Livewire\Store;

use App\Services\StoreService;
use Livewire\Attributes\On;
use Livewire\Component;

class WishlistCountBadge extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->refreshCount();
    }

    #[On('wishlist-updated')]
    public function refreshCount(): void
    {
        $this->count = count(app(StoreService::class)->getWishlist());
    }

    public function render()
    {
        return view('livewire.store.wishlist-count-badge');
    }
}
