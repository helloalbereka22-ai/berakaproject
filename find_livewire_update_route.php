<?php
$dir = new RecursiveDirectoryIterator(__DIR__ . '/vendor/livewire/livewire/src');
$iterator = new RecursiveIteratorIterator($dir);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (str_contains($content, 'update') && (str_contains($content, 'Route::') || str_contains($content, 'route'))) {
            $relPath = str_replace(__DIR__, '', $file->getPathname());
            echo "Match in $relPath:\n";
            preg_match_all('/.*(?:Route::|update).*/i', $content, $matches);
            foreach (array_slice($matches[0], 0, 5) as $line) {
                echo "  " . trim($line) . "\n";
            }
        }
    }
}
