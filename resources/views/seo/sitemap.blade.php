<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    {{-- Static Pages --}}
    @foreach($pages as $page)
    <url>
        <loc>{{ $page['url'] }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>{{ $page['changefreq'] ?? 'weekly' }}</changefreq>
        <priority>{{ $page['priority'] ?? '0.7' }}</priority>
    </url>
    @endforeach

    {{-- Products --}}
    @foreach($products as $product)
    <url>
        <loc>{{ route('store.product', $product->id) }}</loc>
        <lastmod>{{ $product->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach
</urlset>
