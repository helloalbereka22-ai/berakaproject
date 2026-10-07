<?php
// Download Tailwind CSS and save locally
$url = 'https://cdn.tailwindcss.com/3.4.1';
$content = @file_get_contents($url, false, stream_context_create([
    'http' => ['timeout' => 30, 'header' => 'User-Agent: Mozilla/5.0']
]));

if ($content && strlen($content) > 1000) {
    file_put_contents(__DIR__ . '/tailwind.min.js', $content);
    echo "✅ Saved tailwind.min.js (" . round(strlen($content)/1024) . " KB)";
} else {
    // Try alternate URL
    $url2 = 'https://unpkg.com/tailwindcss@3.4.1/src/index.js';
    echo "❌ Failed to download from first URL. File content length: " . strlen($content ?? '');
    echo "<br>Try downloading manually from: https://cdn.tailwindcss.com/3.4.1";
}
