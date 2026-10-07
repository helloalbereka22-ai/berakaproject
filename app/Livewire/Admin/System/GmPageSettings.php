<?php

namespace App\Livewire\Admin\System;

use App\Services\SettingsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class GmPageSettings extends Component
{
    // ── About Us ──────────────────────────────────────────────────────────
    public string $aboutUsContent   = '';

    // ── Vision / Mission / Values ─────────────────────────────────────────
    public string $visionText       = '';
    public string $missionText      = '';
    public string $valuesText       = '';

    // ── Home page ─────────────────────────────────────────────────────────
    public string $homeHeroTitle    = '';
    public string $homeHeroSubtitle = '';

    // ── Contact Us — structured ───────────────────────────────────────────
    // 1. Header
    public string $contactSectionTitle = 'Contact Us';
    public string $contactMainHeading  = "We'd Love to Hear From You";
    public string $contactIntroduction = '';
    // 2. Contact Information
    public string $contactInfoTitle    = 'Contact Information';
    public string $contactPhoneLabel   = 'Phone';
    public string $contactPhone        = '';
    public string $contactEmailLabel   = 'Email';
    public string $contactEmail        = '';
    public string $contactAddressLabel = 'Address';
    public string $contactAddress      = '';
    public string $contactWhatsapp     = '';
    public string $contactHours        = '';
    // 3. Get In Touch
    public string $contactGetInTouchTitle = 'Get In Touch';
    public string $contactGetInTouchDesc  = '';
    public string $contactLocationLabel   = 'Our Location';
    public string $contactLocationDesc    = '';
    // 4. Social Media
    public string $contactFacebook    = '';
    public string $contactTelegram    = '';
    public string $contactTiktok      = '';
    public string $contactYoutube     = '';
    public string $contactInstagram   = '';
    // 5. Footer
    public string $contactTagline     = '';
    public string $contactFooterDesc  = '';

    public function mount(SettingsService $settings): void
    {
        $s = $settings->all();

        $this->aboutUsContent   = $s['about_us_content']    ?? "About Al-Baraka\nAl-Bareka Business Group is a diversified organization operating a lubricating oil manufacturing factory and a multi-branch bookstore retail network.";
        $this->visionText       = $s['vision_text']         ?? 'To be a leading and trusted organization delivering quality products and services that improve lives and communities.';
        $this->missionText      = $s['mission_text']        ?? 'To manufacture and distribute premium quality lubricating oils and provide a wide selection of books through our branch network and digital marketplace.';
        $this->valuesText       = $s['values_text']         ?? 'Quality · Integrity · Service Excellence · Community Focus';
        $this->homeHeroTitle    = $s['home_hero_title']     ?? '';
        $this->homeHeroSubtitle = $s['home_hero_subtitle']  ?? '';

        // Contact structured
        $this->contactSectionTitle  = $s['contact_section_title']   ?? 'Contact Us';
        $this->contactMainHeading   = $s['contact_main_heading']    ?? "We'd Love to Hear From You";
        $this->contactIntroduction  = $s['contact_introduction']    ?? '';
        $this->contactInfoTitle     = $s['contact_info_title']      ?? 'Contact Information';
        $this->contactPhoneLabel    = $s['contact_phone_label']     ?? 'Phone';
        $this->contactPhone         = $s['contact_phone']           ?? '';
        $this->contactEmailLabel    = $s['contact_email_label']     ?? 'Email';
        $this->contactEmail         = $s['contact_email']           ?? '';
        $this->contactAddressLabel  = $s['contact_address_label']   ?? 'Address';
        $this->contactAddress       = $s['contact_address']         ?? '';
        $this->contactWhatsapp      = $s['contact_whatsapp']        ?? '';
        $this->contactHours         = $s['contact_hours']           ?? '';
        $this->contactGetInTouchTitle = $s['contact_get_in_touch_title'] ?? 'Get In Touch';
        $this->contactGetInTouchDesc  = $s['contact_get_in_touch_desc']  ?? '';
        $this->contactLocationLabel   = $s['contact_location_label']     ?? 'Our Location';
        $this->contactLocationDesc    = $s['contact_location_desc']      ?? '';
        $this->contactFacebook      = $s['contact_facebook']        ?? '';
        $this->contactTelegram      = $s['contact_telegram']        ?? '';
        $this->contactTiktok        = $s['contact_tiktok']          ?? '';
        $this->contactYoutube       = $s['contact_youtube']         ?? '';
        $this->contactInstagram     = $s['contact_instagram']       ?? '';
        $this->contactTagline       = $s['contact_tagline']         ?? 'Albereka — Quality, Service & Trust';
        $this->contactFooterDesc    = $s['contact_footer_desc']     ?? '';
    }

    public function saveAbout(SettingsService $settings): void
    {
        $this->validate(['aboutUsContent' => 'required|string|max:5000']);
        $settings->set('about_us_content', $this->aboutUsContent, 'text');
        $this->dispatch('saved');
    }

    public function saveContact(SettingsService $settings): void
    {
        $this->validate([
            'contactSectionTitle'   => 'nullable|string|max:100',
            'contactMainHeading'    => 'nullable|string|max:200',
            'contactIntroduction'   => 'nullable|string|max:1000',
            'contactPhone'          => 'nullable|string|max:50',
            'contactEmail'          => 'nullable|email|max:100',
            'contactAddress'        => 'nullable|string|max:300',
            'contactWhatsapp'       => 'nullable|string|max:50',
            'contactHours'          => 'nullable|string|max:200',
            'contactGetInTouchDesc' => 'nullable|string|max:1000',
            'contactLocationDesc'   => 'nullable|string|max:500',
            'contactTagline'        => 'nullable|string|max:200',
            'contactFooterDesc'     => 'nullable|string|max:500',
        ]);

        $settings->setMany([
            'contact_section_title'     => $this->contactSectionTitle,
            'contact_main_heading'      => $this->contactMainHeading,
            'contact_introduction'      => $this->contactIntroduction,
            'contact_info_title'        => $this->contactInfoTitle,
            'contact_phone_label'       => $this->contactPhoneLabel,
            'contact_phone'             => $this->contactPhone,
            'contact_email_label'       => $this->contactEmailLabel,
            'contact_email'             => $this->contactEmail,
            'contact_address_label'     => $this->contactAddressLabel,
            'contact_address'           => $this->contactAddress,
            'contact_whatsapp'          => $this->contactWhatsapp,
            'contact_hours'             => $this->contactHours,
            'contact_get_in_touch_title'=> $this->contactGetInTouchTitle,
            'contact_get_in_touch_desc' => $this->contactGetInTouchDesc,
            'contact_location_label'    => $this->contactLocationLabel,
            'contact_location_desc'     => $this->contactLocationDesc,
            'contact_facebook'          => $this->contactFacebook,
            'contact_telegram'          => $this->contactTelegram,
            'contact_tiktok'            => $this->contactTiktok,
            'contact_youtube'           => $this->contactYoutube,
            'contact_instagram'         => $this->contactInstagram,
            'contact_tagline'           => $this->contactTagline,
            'contact_footer_desc'       => $this->contactFooterDesc,
        ], 'text');

        $this->dispatch('saved');
    }

    public function saveFoundation(SettingsService $settings): void
    {
        $this->validate([
            'visionText'  => 'required|string|max:2000',
            'missionText' => 'required|string|max:2000',
            'valuesText'  => 'required|string|max:1000',
        ]);
        $settings->setMany([
            'vision_text'  => $this->visionText,
            'mission_text' => $this->missionText,
            'values_text'  => $this->valuesText,
        ], 'text');
        $this->dispatch('saved');
    }

    public function saveHome(SettingsService $settings): void
    {
        $this->validate([
            'homeHeroTitle'    => 'nullable|string|max:200',
            'homeHeroSubtitle' => 'nullable|string|max:500',
        ]);
        $settings->setMany([
            'home_hero_title'    => $this->homeHeroTitle,
            'home_hero_subtitle' => $this->homeHeroSubtitle,
        ], 'text');
        $this->dispatch('saved');
    }

    public function render()
    {
        return view('livewire.admin.system.gm-page-settings')
            ->title('Page Content Settings');
    }
}
