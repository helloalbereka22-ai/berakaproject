<?php

namespace App\Livewire\Admin\System;

use App\Services\AuditService;
use App\Services\SettingsService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class ContactSettings extends Component
{
    use WithFileUploads;

    // ── Organization ──────────────────────────────────────────────────────
    public string $orgName        = '';
    public string $orgTagline     = '';
    public string $orgDescription = '';
    public string $orgWebsite     = '';
    public        $orgLogo        = null; // file upload

    // ── Contact ───────────────────────────────────────────────────────────
    public string $contactPhone          = '';
    public string $contactPhoneSecondary = '';
    public string $contactEmail          = '';
    public string $contactEmailSecondary = '';
    public string $contactAddress        = '';
    public string $contactAddressPostal  = '';
    public string $contactOfficeHours    = '';
    public string $contactMapsUrl        = '';

    // ── Social media ──────────────────────────────────────────────────────
    public string $socialFacebook  = '';
    public string $socialTelegram  = '';
    public string $socialYoutube   = '';
    public string $socialLinkedin  = '';
    public string $socialInstagram = '';
    public string $socialTwitter   = '';
    public string $socialWhatsapp  = '';
    public string $socialTiktok    = '';

    // ── State ─────────────────────────────────────────────────────────────
    public string $activeTab     = 'organization';
    public string $successMsg    = '';

    public function mount(): void
    {
        $this->authorize('system.settings.update');

        $s = app(SettingsService::class)->all();

        // Organization
        $this->orgName        = $s['org_name']        ?? 'Al-Baraka Business Group';
        $this->orgTagline     = $s['org_tagline']     ?? '';
        $this->orgDescription = $s['org_description'] ?? '';
        $this->orgWebsite     = $s['org_website']     ?? '';

        // Contact
        $this->contactPhone          = $s['contact_phone']           ?? '';
        $this->contactPhoneSecondary = $s['contact_phone_secondary']  ?? '';
        $this->contactEmail          = $s['contact_email']            ?? '';
        $this->contactEmailSecondary = $s['contact_email_secondary']  ?? '';
        $this->contactAddress        = $s['contact_address']          ?? '';
        $this->contactAddressPostal  = $s['contact_address_postal']   ?? '';
        $this->contactOfficeHours    = $s['contact_office_hours']     ?? '';
        $this->contactMapsUrl        = $s['contact_maps_url']         ?? '';

        // Social
        $this->socialFacebook  = $s['social_facebook']  ?? '';
        $this->socialTelegram  = $s['social_telegram']  ?? '';
        $this->socialYoutube   = $s['social_youtube']   ?? '';
        $this->socialLinkedin  = $s['social_linkedin']  ?? '';
        $this->socialInstagram = $s['social_instagram'] ?? '';
        $this->socialTwitter   = $s['social_twitter']   ?? '';
        $this->socialWhatsapp  = $s['social_whatsapp']  ?? '';
        $this->socialTiktok    = $s['social_tiktok']    ?? '';
    }

    // ── Validation rules ──────────────────────────────────────────────────

    protected function rulesForOrganization(): array
    {
        return [
            'orgName'        => 'required|string|max:255',
            'orgTagline'     => 'nullable|string|max:255',
            'orgDescription' => 'nullable|string|max:1000',
            'orgWebsite'     => 'nullable|url|max:255',
            'orgLogo'        => 'nullable|image|max:2048|mimes:jpg,jpeg,png,webp,svg',
        ];
    }

    protected function rulesForContact(): array
    {
        return [
            'contactPhone'          => 'nullable|string|max:50',
            'contactPhoneSecondary' => 'nullable|string|max:50',
            'contactEmail'          => 'nullable|email|max:255',
            'contactEmailSecondary' => 'nullable|email|max:255',
            'contactAddress'        => 'nullable|string|max:500',
            'contactAddressPostal'  => 'nullable|string|max:255',
            'contactOfficeHours'    => 'nullable|string|max:255',
            'contactMapsUrl'        => 'nullable|url|max:500',
        ];
    }

    protected function rulesForSocial(): array
    {
        $socialKeys = ['socialFacebook','socialTelegram','socialYoutube','socialLinkedin',
                       'socialInstagram','socialTwitter','socialWhatsapp','socialTiktok'];
        return array_fill_keys(
            $socialKeys,
            'nullable|url|max:500'
        );
    }

    // ── Save handlers ─────────────────────────────────────────────────────

    public function saveOrganization(): void
    {
        $this->authorize('system.settings.update');
        $this->validate($this->rulesForOrganization());

        $svc = app(SettingsService::class);
        $old = $svc->organization();

        $data = [
            'org_name'        => trim($this->orgName),
            'org_tagline'     => trim($this->orgTagline),
            'org_description' => trim($this->orgDescription),
            'org_website'     => trim($this->orgWebsite),
        ];

        // Handle logo upload
        if ($this->orgLogo) {
            $path = $this->orgLogo->store('logos', 'public');
            $data['org_logo_path'] = $path;
        }

        $svc->setMany($data);

        AuditService::log(
            action:        'settings_updated',
            auditableType: 'Setting',
            auditableId:   0,
            beforeData:    $old,
            afterData:     $data,
        );

        $this->orgLogo   = null;
        $this->successMsg = 'Organization information saved.';
    }

    public function saveContact(): void
    {
        $this->authorize('system.settings.update');
        $this->validate($this->rulesForContact());

        $svc = app(SettingsService::class);
        $old = $svc->contact();

        $data = [
            'contact_phone'           => trim($this->contactPhone),
            'contact_phone_secondary' => trim($this->contactPhoneSecondary),
            'contact_email'           => trim($this->contactEmail),
            'contact_email_secondary' => trim($this->contactEmailSecondary),
            'contact_address'         => trim($this->contactAddress),
            'contact_address_postal'  => trim($this->contactAddressPostal),
            'contact_office_hours'    => trim($this->contactOfficeHours),
            'contact_maps_url'        => trim($this->contactMapsUrl),
        ];

        $svc->setMany($data);

        AuditService::log('settings_updated', 'Setting', 0, $old, $data);
        $this->successMsg = 'Contact information saved.';
    }

    public function saveSocial(): void
    {
        $this->authorize('system.settings.update');
        $this->validate($this->rulesForSocial());

        $svc = app(SettingsService::class);
        $old = $svc->social();

        $data = [
            'social_facebook'  => trim($this->socialFacebook),
            'social_telegram'  => trim($this->socialTelegram),
            'social_youtube'   => trim($this->socialYoutube),
            'social_linkedin'  => trim($this->socialLinkedin),
            'social_instagram' => trim($this->socialInstagram),
            'social_twitter'   => trim($this->socialTwitter),
            'social_whatsapp'  => trim($this->socialWhatsapp),
            'social_tiktok'    => trim($this->socialTiktok),
        ];

        $svc->setMany($data);
        AuditService::log('settings_updated', 'Setting', 0, $old, $data);
        $this->successMsg = 'Social media links saved.';
    }

    public function render()
    {
        $preview = app(SettingsService::class)->all();

        return view('livewire.admin.system.contact-settings', compact('preview'))
            ->title('Contact & Organization Settings');
    }
}
