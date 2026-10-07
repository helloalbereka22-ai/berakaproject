<?php

namespace App\Livewire\Admin;

use App\Models\Language;
use App\Models\Translation;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class TranslationManager extends Component
{
    use WithPagination;

    // ── State ─────────────────────────────────────────────────────────────
    public string $activeTab  = 'system';   // system | content
    public string $locale     = 'en';
    public string $searchTerm = '';

    // System strings editor (lang/ files)
    public string $editGroup  = '';
    public string $editKey    = '';
    public string $editValue  = '';
    public bool   $showSysForm = false;

    // Content translation editor (DB translations table)
    public string $contentType  = '';   // App\Models\Product, etc.
    public int    $contentId    = 0;
    public string $contentField = '';
    public string $contentValue = '';
    public bool   $showContentForm = false;

    /** @var array Supported translatable content types */
    public array $contentTypes = [
        'App\\Models\\Product'  => 'Products',
        'App\\Models\\Article'  => 'Articles',
        'App\\Models\\FAQ'      => 'FAQs',
        'App\\Models\\Category' => 'Categories',
        'App\\Models\\Page'     => 'Pages',
    ];

    /** @var array Available languages */
    public array $languages = [];

    public function mount(): void
    {
        $this->authorize('system.settings.update');
        $this->languages = Language::where('is_active', true)->orderBy('code')->pluck('name', 'code')->toArray();

        // Default to first non-English locale for translation work
        $codes = array_keys($this->languages);
        $this->locale = in_array('ar', $codes) ? 'ar' : ($codes[0] ?? 'en');
    }

    // ── System strings ────────────────────────────────────────────────────

    /** Returns the system strings from the lang/ files for the active locale */
    public function getSystemStrings(): array
    {
        $path = lang_path("{$this->locale}/app.php");
        if (!file_exists($path)) {
            return [];
        }

        $data    = include $path;
        $flat    = $this->flatten($data);
        $search  = strtolower($this->searchTerm);

        if ($search) {
            $flat = array_filter($flat, fn($v, $k) =>
                str_contains(strtolower($k), $search) || str_contains(strtolower($v), $search),
                ARRAY_FILTER_USE_BOTH
            );
        }

        return $flat;
    }

    public function openSysEdit(string $group, string $key, string $value): void
    {
        $this->editGroup   = $group;
        $this->editKey     = $key;
        $this->editValue   = $value;
        $this->showSysForm = true;
    }

    public function saveSysTranslation(): void
    {
        $this->validate(['editValue' => 'required|string']);

        $path = lang_path("{$this->locale}/app.php");
        if (!file_exists($path)) {
            session()->flash('error', "Lang file for [{$this->locale}] not found.");
            return;
        }

        $data = include $path;

        // Navigate to group.key and update
        $keys = explode('.', "{$this->editGroup}.{$this->editKey}");
        $ref  = &$data;
        foreach ($keys as $k) {
            if (!isset($ref[$k])) { $ref[$k] = []; }
            $ref = &$ref[$k];
        }
        $ref = $this->editValue;

        // Write back
        $export = "<?php\n\nreturn " . var_export($data, true) . ";\n";
        file_put_contents($path, $export);

        $this->showSysForm = false;
        $this->resetPage();
        session()->flash('success', "Translation saved for [{$this->locale}].");
    }

    // ── Content translations (DB) ─────────────────────────────────────────

    public function getContentTranslations()
    {
        if (!$this->contentType) return collect();

        return Translation::where('translatable_type', $this->contentType)
            ->where('locale', $this->locale)
            ->when($this->searchTerm, fn($q) => $q->where('value', 'like', "%{$this->searchTerm}%"))
            ->orderBy('translatable_id')
            ->orderBy('field')
            ->paginate(30);
    }

    public function openContentEdit(int $id, string $field, string $value): void
    {
        $this->contentId    = $id;
        $this->contentField = $field;
        $this->contentValue = $value;
        $this->showContentForm = true;
    }

    public function saveContentTranslation(): void
    {
        $this->validate([
            'contentType'  => 'required|string',
            'contentId'    => 'required|integer|min:1',
            'contentField' => 'required|string',
            'contentValue' => 'required|string',
        ]);

        Translation::updateOrCreate(
            [
                'translatable_type' => $this->contentType,
                'translatable_id'   => $this->contentId,
                'locale'            => $this->locale,
                'field'             => $this->contentField,
            ],
            ['value' => $this->contentValue]
        );

        $this->showContentForm = false;
        session()->flash('success', 'Content translation saved.');
    }

    public function deleteContentTranslation(int $id): void
    {
        Translation::find($id)?->delete();
        session()->flash('success', 'Translation deleted.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /** Flatten a nested array to dot-notation keys, returning group and key separately */
    private function flatten(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->flatten($value, $prefix . $key . '.'));
            } else {
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
    }

    public function render()
    {
        $systemStrings       = $this->activeTab === 'system'  ? $this->getSystemStrings()       : [];
        $contentTranslations = $this->activeTab === 'content' ? $this->getContentTranslations() : collect();

        return view('livewire.admin.translation-manager', compact(
            'systemStrings', 'contentTranslations'
        ))->title('Translation Manager');
    }
}
