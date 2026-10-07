<?php

$directory = new RecursiveDirectoryIterator(__DIR__ . '/app');
$iterator = new RecursiveIteratorIterator($directory);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $newContent = str_replace('App\Models\\', 'App\\', $content);
        if ($content !== $newContent) {
            file_put_contents($file->getPathname(), $newContent);
            echo "Updated: " . $file->getPathname() . "\n";
        }
    }
}

echo "Done.\n";
