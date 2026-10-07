@props([
    'title' => 'Albareka Enterprise',
    'description' => 'Professional Enterprise Business Management Platform',
    'canonical' => url()->current(),
    'ogImage' => asset('img/og-image.jpg')
])

<title>{{ $title }} - Albareka</title>
<meta name="description" content="{{ $description }}">

<!-- Canonical URL -->
<link rel="canonical" href="{{ $canonical }}">

<!-- Open Graph Tags -->
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:type" content="website">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $ogImage }}">
