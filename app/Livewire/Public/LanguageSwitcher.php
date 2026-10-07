<?php

namespace App\Livewire\Public;

use App\Models\Language;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

/**
 * Public-facing language switcher.
 *
 * Livewire tag: <livewire:public.language-switcher />
 *
 * Reads active languages from the database. On switch:
 *   1. Saves locale code to session
 *   2. Sets is_rtl flag in session (drives dir="rtl" on <html>)
 *   3. Full-page redirect to the same URL so the new locale takes effect
 *      in SetLocale middleware and all translated content re-renders.
 */
class LanguageSwitcher extends Component
{
    /** @var \Illuminate\Database\Eloquent\Collection */
    public $languages;

    public string $currentLocale = 'en';

    public function mount(): void
    {
        // Auto-create languages if table is empty
        $count = Language::count();
        if ($count === 0) {
            $defaults = [
                ['code' => 'en', 'name' => 'English',      'is_active' => true, 'is_rtl' => false],
                ['code' => 'am', 'name' => 'አማርኛ',         'is_active' => true, 'is_rtl' => false],
                ['code' => 'ar', 'name' => 'العربية',       'is_active' => true, 'is_rtl' => true],
                ['code' => 'om', 'name' => 'Afaan Oromoo',  'is_active' => true, 'is_rtl' => false],
            ];
            foreach ($defaults as $lang) {
                Language::firstOrCreate(['code' => $lang['code']], $lang);
            }
        } else {
            // Make sure all 4 are active
            Language::whereIn('code', ['en', 'am', 'ar', 'om'])->update(['is_active' => true]);
        }

        $this->languages     = Language::where('is_active', true)->orderBy('code')->get();
        $this->currentLocale = app()->getLocale();
    }

    /**
     * Switch the application locale.
     * Validates the code against the Languages table before accepting it.
     */
    public function switchLanguage(string $code): mixed
    {
        $language = Language::where('code', $code)
                            ->where('is_active', true)
                            ->first();

        if (!$language) {
            return null; // Silently ignore invalid codes
        }

        Session::put('locale',  $language->code);
        Session::put('is_rtl',  (bool) $language->is_rtl);
        app()->setLocale($language->code);
        $this->currentLocale = $language->code;

        // Full redirect so SetLocale middleware re-fires and all
        // server-rendered translations update on the next page load.
        // Use redirect()->away() to ensure a full browser navigation
        $referer = request()->header('Referer', url()->current());
        return redirect()->away($referer);
    }

    public function render()
    {
        return view('livewire.public.language-switcher');
    }
}
