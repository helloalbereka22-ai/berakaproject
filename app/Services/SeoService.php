<?php

namespace App\Services;

class SeoService
{
    private string $siteName;
    private string $siteUrl;
    private string $description;
    private string $logo;

    public function __construct()
    {
        $settings = app(SettingsService::class);
        $this->siteName = $settings->get('org_name', 'Al-Baraka Store');
        $this->siteUrl = url('/');
        $this->description = $settings->get('org_description', 'Al-Baraka - Premium quality oils, books, and manufactured products. Natural ingredients, certified quality, trusted by individuals and businesses.');
        $logoPath = $settings->get('system_logo_path', '');
        $this->logo = $logoPath ? asset('storage/' . $logoPath) : asset('img/logo.png');
    }

    /**
     * Get meta tags for a page
     */
    public function getMetaTags(
        string $title,
        string $description = '',
        string $canonicalUrl = '',
        array $ogTags = []
    ): array {
        $desc = $description ?: $this->description;
        
        return [
            'title' => $title . ' — ' . $this->siteName,
            'description' => $desc,
            'keywords' => 'oils, books, manufactured products, natural, quality, Ethiopia, Al-Baraka',
            'canonical' => $canonicalUrl,
            'og' => array_merge([
                'type' => 'website',
                'title' => $title,
                'description' => $desc,
                'image' => $this->logo,
                'url' => $canonicalUrl,
                'site_name' => $this->siteName,
            ], $ogTags),
            'twitter' => [
                'card' => 'summary_large_image',
                'title' => $title,
                'description' => $desc,
                'image' => $this->logo,
            ],
        ];
    }

    /**
     * Get structured data (JSON-LD) for organization
     */
    public function getOrganizationSchema(): array
    {
        $settings = app(SettingsService::class);
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $this->siteName,
            'url' => $this->siteUrl,
            'logo' => $this->logo,
            'description' => $this->description,
            'sameAs' => array_filter([
                $settings->get('social_facebook'),
                $settings->get('social_twitter'),
                $settings->get('social_instagram'),
            ]),
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'Customer Service',
                'telephone' => $settings->get('contact_phone', ''),
                'email' => $settings->get('contact_email', ''),
            ],
        ];
    }

    /**
     * Get structured data for product
     */
    public function getProductSchema($product): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $product->description ?: $product->name,
            'image' => $product->primaryImage ? asset('storage/' . $product->primaryImage['path']) : $this->logo,
            'brand' => [
                '@type' => 'Brand',
                'name' => $product->business?->name ?: $this->siteName,
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => route('store.product', $product->id),
                'priceCurrency' => 'ETB',
                'price' => $product->selling_price,
                'availability' => 'https://schema.org/InStock',
            ],
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => '4.5',
                'ratingCount' => '100',
            ],
        ];
    }

    /**
     * Get structured data for breadcrumb
     */
    public function getBreadcrumbSchema(array $breadcrumbs): array
    {
        $items = [];
        foreach ($breadcrumbs as $index => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Get structured data for business (local business)
     */
    public function getLocalBusinessSchema(): array
    {
        $settings = app(SettingsService::class);
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $this->siteName,
            'image' => $this->logo,
            'description' => $this->description,
            'url' => $this->siteUrl,
            'telephone' => $settings->get('contact_phone', ''),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $settings->get('contact_address', 'Addis Ababa'),
                'addressCountry' => 'ET',
            ],
            'openingHoursSpecification' => [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                'opens' => '08:00',
                'closes' => '18:00',
            ],
        ];
    }

    /**
     * Get structured data for web page
     */
    public function getWebPageSchema(string $title, string $description, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title,
            'description' => $description,
            'url' => $url,
            'mainEntity' => [
                '@type' => 'Organization',
                'name' => $this->siteName,
            ],
        ];
    }

    /**
     * Get site name
     */
    public function getSiteName(): string
    {
        return $this->siteName;
    }

    /**
     * Get site URL
     */
    public function getSiteUrl(): string
    {
        return $this->siteUrl;
    }
}
