<?php

namespace App\Livewire;

use App\Models\Language;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    public string $currentLocale = 'en';
    public array $languages = [];

    public function mount()
    {
        $this->currentLocale = app()->getLocale();
        if (Session::has('locale')) {
            $this->currentLocale = Session::get('locale');
        }

        $this->loadLanguages();
    }

    private function loadLanguages(): void
    {
        $activeLangs = Language::where('is_active', true)->orderBy('code')->get();
        if ($activeLangs->count() > 0) {
            $this->languages = $activeLangs->pluck('name', 'code')->toArray();
        } else {
            $this->languages = [
                'en' => 'English',
                'am' => 'አማርኛ',
                'ar' => 'العربية',
                'om' => 'Afaan Oromoo',
            ];
        }
    }

    public function switchLanguage($locale)
    {
        if (array_key_exists($locale, $this->languages)) {
            $langModel = Language::where('code', $locale)->first();
            $isRtl = $langModel ? (bool)$langModel->is_rtl : ($locale === 'ar');

            Session::put('locale', $locale);
            Session::put('is_rtl', $isRtl);
            app()->setLocale($locale);
            $this->currentLocale = $locale;
            
            return redirect(request()->header('Referer', '/'));
        }
    }

    public function render()
    {
        return view('livewire.language-switcher');
    }
}
