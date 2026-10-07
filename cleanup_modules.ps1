$ErrorActionPreference = "Stop"

# Move remaining Models
$modelsPath = "app\Models"
Get-ChildItem -Path "app\Modules" -Recurse -File | Where-Object { $_.DirectoryName -like "*Domain\Models*" } | ForEach-Object {
    $src = $_.FullName
    $dest = Join-Path $modelsPath $_.Name
    
    # If the model already exists in app/Models, we will just skip to avoid overwriting existing logic for now.
    if (-not (Test-Path $dest)) {
        $content = Get-Content $src -Raw
        $content = $content -replace "namespace App\\Modules\\[A-Za-z]+\\Domain\\Models;", "namespace App\Models;"
        Set-Content -Path $dest -Value $content
        Remove-Item -Path $src -Force
    } else {
        # Already exists, we can remove the module one
        Remove-Item -Path $src -Force
    }
}

# Move Services
$servicesPath = "app\Services"
Get-ChildItem -Path "app\Modules" -Recurse -File | Where-Object { $_.DirectoryName -like "*Application\Services*" } | ForEach-Object {
    $src = $_.FullName
    $dest = Join-Path $servicesPath $_.Name
    if (-not (Test-Path $dest)) {
        $content = Get-Content $src -Raw
        $content = $content -replace "namespace App\\Modules\\[A-Za-z]+\\Application\\Services;", "namespace App\Services;"
        Set-Content -Path $dest -Value $content
    }
    Remove-Item -Path $src -Force
}

# Move Middleware
$middlewarePath = "app\Http\Middleware"
Get-ChildItem -Path "app\Modules" -Recurse -File | Where-Object { $_.DirectoryName -like "*Infrastructure\Middleware*" } | ForEach-Object {
    $src = $_.FullName
    $dest = Join-Path $middlewarePath $_.Name
    if (-not (Test-Path $dest)) {
        $content = Get-Content $src -Raw
        $content = $content -replace "namespace App\\Modules\\[A-Za-z]+\\Infrastructure\\Middleware;", "namespace App\Http\Middleware;"
        Set-Content -Path $dest -Value $content
    }
    Remove-Item -Path $src -Force
}

# Fix references to SetLocale in bootstrap/app.php
$appPhpPath = "bootstrap\app.php"
if (Test-Path $appPhpPath) {
    $content = Get-Content $appPhpPath -Raw
    $content = $content -replace "\\App\\Modules\\Localization\\Infrastructure\\Middleware\\SetLocale::class", "\App\Http\Middleware\SetLocale::class"
    Set-Content -Path $appPhpPath -Value $content
}

# Remove app/Modules directory entirely
if (Test-Path "app\Modules") {
    Remove-Item -Path "app\Modules" -Recurse -Force
}

Write-Output "Modules cleanup complete."
