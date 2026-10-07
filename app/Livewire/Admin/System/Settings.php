<?php

namespace App\Livewire\Admin\System;

use App\Services\AuditService;
use App\Services\SettingsService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class Settings extends Component
{
    use WithFileUploads;

    // SEO Settings
    public string $seoMetaTitle = '';
    public string $seoMetaDescription = '';
    public string $seoMetaKeywords = '';

    // Location Settings
    public string $orgLatitude = '';
    public string $orgLongitude = '';

    // Image Uploads
    public $systemLogo;
    public $loginBackground;
    public string $currentLogoPath = '';
    public string $currentBgPath   = '';

    // Promo slider images (up to 5)
    public $promoImage1;
    public $promoImage2;
    public $promoImage3;
    public $promoImage4;
    public $promoImage5;
    public string $currentPromo1 = '';
    public string $currentPromo2 = '';
    public string $currentPromo3 = '';
    public string $currentPromo4 = '';
    public string $currentPromo5 = '';

    public string $successMsg = '';

    public function mount(SettingsService $settings): void
    {
        $this->authorize('system.settings.update');

        $seo = $settings->seo();
        $this->seoMetaTitle       = $seo['seo_meta_title'] ?? '';
        $this->seoMetaDescription = $seo['seo_meta_description'] ?? '';
        $this->seoMetaKeywords    = $seo['seo_meta_keywords'] ?? '';

        $location = $settings->location();
        $this->orgLatitude  = $location['org_latitude'] ?? '';
        $this->orgLongitude = $location['org_longitude'] ?? '';

        // Load existing image paths
        $this->currentLogoPath = $settings->get('system_logo_path', '');
        $this->currentBgPath   = $settings->get('login_bg_path', '');
        $this->currentPromo1   = $settings->get('promo_image_1', '');
        $this->currentPromo2   = $settings->get('promo_image_2', '');
        $this->currentPromo3   = $settings->get('promo_image_3', '');
        $this->currentPromo4   = $settings->get('promo_image_4', '');
        $this->currentPromo5   = $settings->get('promo_image_5', '');
    }

    public function save(SettingsService $settings): void
    {
        $this->authorize('system.settings.update');

        $this->validate([
            'seoMetaTitle'       => 'nullable|string|max:255',
            'seoMetaDescription' => 'nullable|string|max:500',
            'seoMetaKeywords'    => 'nullable|string|max:500',
            'orgLatitude'        => 'nullable|string|max:50',
            'orgLongitude'       => 'nullable|string|max:50',
            'systemLogo'         => 'nullable|image|max:2048',
            'loginBackground'    => 'nullable|image|max:5120',
            'promoImage1'        => 'nullable|image|max:5120',
            'promoImage2'        => 'nullable|image|max:5120',
            'promoImage3'        => 'nullable|image|max:5120',
            'promoImage4'        => 'nullable|image|max:5120',
            'promoImage5'        => 'nullable|image|max:5120',
        ]);

        $data = [
            'seo_meta_title'       => trim($this->seoMetaTitle),
            'seo_meta_description' => trim($this->seoMetaDescription),
            'seo_meta_keywords'    => trim($this->seoMetaKeywords),
            'org_latitude'         => trim($this->orgLatitude),
            'org_longitude'        => trim($this->orgLongitude),
        ];

        // Handle system logo upload
        if ($this->systemLogo) {
            $logoPath = $this->systemLogo->store('system', 'public');
            $data['system_logo_path'] = $logoPath;
            $this->currentLogoPath = $logoPath;
            
            // Delete old logo if exists
            $oldLogo = $settings->get('system_logo_path');
            if ($oldLogo && \Storage::disk('public')->exists($oldLogo)) {
                \Storage::disk('public')->delete($oldLogo);
            }
        }

        // Handle login background upload
        if ($this->loginBackground) {
            $bgPath = $this->loginBackground->store('system', 'public');
            $data['login_bg_path'] = $bgPath;
            $this->currentBgPath = $bgPath;
            
            // Delete old background if exists
            $oldBg = $settings->get('login_bg_path');
            if ($oldBg && \Storage::disk('public')->exists($oldBg)) {
                \Storage::disk('public')->delete($oldBg);
            }
        }

        $oldSeo = $settings->seo();
        $oldLocation = $settings->location();
        $old = array_merge($oldSeo, $oldLocation);

        $settings->setMany($data);

        AuditService::log(
            action:        'seo_and_website_settings_updated',
            auditableType: 'Setting',
            auditableId:   0,
            beforeData:    $old,
            afterData:     $data,
        );

        // Handle promo images upload
        foreach (range(1, 5) as $i) {
            $prop = "promoImage{$i}";
            $key  = "promo_image_{$i}";
            $cur  = "currentPromo{$i}";
            if ($this->$prop) {
                $old = $settings->get($key);
                if ($old && \Storage::disk('public')->exists($old)) {
                    \Storage::disk('public')->delete($old);
                }
                $path = $this->$prop->store('promo', 'public');
                $data[$key]    = $path;
                $this->$cur    = $path;
                $this->$prop   = null;
            }
        }

        // Reset file upload properties
        $this->systemLogo = null;
        $this->loginBackground = null;

        $this->successMsg = 'SEO and Website settings saved successfully.';
    }

    public function render()
    {
        return view('livewire.admin.system.settings');
    }
}