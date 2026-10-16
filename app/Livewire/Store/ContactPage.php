<?php

namespace App\Livewire\Store;

use App\Services\SettingsService;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.marketplace')]
class ContactPage extends Component
{
    public string $facebookUrl = '';
    public string $telegramUrl = '';
    public string $tiktokUrl = '';
    public string $youtubeUrl = '';
    public string $instagramUrl = '';

    public function mount(SettingsService $settings): void
    {
        $s = $settings->all() ?? [];
        $this->facebookUrl = $s['contact_facebook'] ?? '';
        $this->telegramUrl = $s['contact_telegram'] ?? '';
        $this->tiktokUrl = $s['contact_tiktok'] ?? '';
        $this->youtubeUrl = $s['contact_youtube'] ?? '';
        $this->instagramUrl = $s['contact_instagram'] ?? '';
    }

    public function render()
    {
        return view('livewire.store.contact-page');
    }
}