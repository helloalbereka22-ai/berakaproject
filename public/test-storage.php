<!DOCTYPE html>
<html>
<head>
    <title>Storage Test</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #1e293b; color: white; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .box { background: #334155; padding: 20px; border-radius: 10px; margin: 10px 0; }
        img { max-width: 300px; border: 2px solid #4f46e5; border-radius: 8px; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>🔍 Storage Image Test</h1>
    
    <?php
    $storagePath = __DIR__ . '/../storage/app/public/system';
    $publicStoragePath = __DIR__ . '/storage';
    
    echo "<div class='box'>";
    echo "<h2>Storage Directory Check:</h2>";
    
    if (is_dir($storagePath)) {
        echo "<p class='success'>✅ Storage directory EXISTS: $storagePath</p>";
        
        $files = glob($storagePath . '/*');
        echo "<p>Files found: " . count($files) . "</p>";
        
        foreach ($files as $file) {
            $filename = basename($file);
            echo "<p>📁 " . $filename . "</p>";
            
            // Try to display the image
            $imageUrl = '/storage/system/' . $filename;
            echo "<p>Trying to load: <code>$imageUrl</code></p>";
            echo "<img src='$imageUrl' alt='Test' onerror=\"this.parentElement.innerHTML += '<p class=error>❌ Failed to load</p>'\" onload=\"this.parentElement.innerHTML += '<p class=success>✅ Loaded successfully!</p>'\" />";
        }
    } else {
        echo "<p class='error'>❌ Storage directory NOT FOUND: $storagePath</p>";
    }
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2>Public Symlink Check:</h2>";
    
    if (file_exists($publicStoragePath)) {
        if (is_link($publicStoragePath)) {
            echo "<p class='success'>✅ Symlink EXISTS at: $publicStoragePath</p>";
            echo "<p>Points to: " . readlink($publicStoragePath) . "</p>";
        } else if (is_dir($publicStoragePath)) {
            echo "<p class='success'>✅ Directory EXISTS at: $publicStoragePath</p>";
        } else {
            echo "<p class='error'>⚠️ Path exists but is neither symlink nor directory</p>";
        }
    } else {
        echo "<p class='error'>❌ Public storage link/directory NOT FOUND at: $publicStoragePath</p>";
        echo "<p>This is why images are not showing!</p>";
        echo "<p><strong>Solution:</strong> Run PowerShell as Administrator and execute:</p>";
        echo "<pre style='background:#1e293b;padding:10px;border:1px solid #4f46e5'>New-Item -ItemType SymbolicLink -Path \"$publicStoragePath\" -Target \"" . realpath($storagePath) . "\"</pre>";
    }
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2>Alternative Solution (Route-based):</h2>";
    echo "<p>The /storage/{path} route has been added to routes/web.php</p>";
    echo "<p>This should work even without a symlink!</p>";
    echo "</div>";
    ?>
</body>
</html>
