<?php

namespace App\Livewire\Localization;

use App\Models\Language;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

/**
 * Language switcher used in the admin panel.
 * Livewire tag: <livewire:localization.language-switcher />
 */
class LanguageSwitcher extends Component
{
    public array  $languages     = [];
    public string $currentLocale = 'en';

    public function mount(): void
    {
        $this->languages     = Language::where('is_active', true)
                                       ->orderBy('code')
                                       ->get(['code', 'name', 'is_rtl'])
                                       ->toArray();

        $this->currentLocale = Session::get('locale', app()->getLocale());
    }

    public function switchLanguage(string $code): void
    {
        $lang = Language::where('code', $code)->where('is_active', true)->first();

        if (!$lang) return;

        Session::put('locale', $code);
        Session::put('is_rtl', (bool) $lang->is_rtl);
        app()->setLocale($code);
        $this->currentLocale = $code;
    }

    public function render()
    {
        return view('livewire.localization.language-switcher');
    }
}

