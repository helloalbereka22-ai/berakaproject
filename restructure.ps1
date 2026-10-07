$ErrorActionPreference = "Stop"

# Create Directories
$dirs = @("app\Livewire\Admin", "app\Livewire\Business", "app\Livewire\Public", 
          "resources\views\livewire\admin", "resources\views\livewire\business", "resources\views\livewire\public")
foreach ($dir in $dirs) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Force -Path $dir | Out-Null
    }
}

# Define mappings for Livewire Component Files (Source path -> Destination Folder)
$componentMappings = @{
    "app\Modules\Identity\Presentation\Livewire\Login.php" = "Public"
    "app\Modules\Identity\Presentation\Livewire\Register.php" = "Public"
    "app\Modules\Identity\Presentation\Livewire\ForgotPassword.php" = "Public"
    "app\Modules\Identity\Presentation\Livewire\ResetPassword.php" = "Public"
    "app\Modules\Localization\Presentation\Livewire\LanguageSwitcher.php" = "Public"
    
    "app\Modules\Identity\Presentation\Livewire\SuperAdminDashboard.php" = "Admin"
    "app\Modules\Identity\Presentation\Livewire\RoleAssignmentManager.php" = "Admin"
    "app\Modules\Localization\Presentation\Livewire\TranslationManager.php" = "Admin"
    
    "app\Modules\Identity\Presentation\Livewire\ProfileManager.php" = "Business"
    "app\Modules\Catalog\Presentation\Livewire\AttributeManager.php" = "Business"
    "app\Modules\Catalog\Presentation\Livewire\CategoryManager.php" = "Business"
    "app\Modules\Catalog\Presentation\Livewire\ProductManager.php" = "Business"
    "app\Modules\Catalog\Presentation\Livewire\ProductMediaManager.php" = "Business"
    "app\Modules\Inventory\Presentation\Livewire\InventoryInitializer.php" = "Business"
}

foreach ($mapping in $componentMappings.GetEnumerator()) {
    $src = $mapping.Key
    $destFolder = $mapping.Value
    if (Test-Path $src) {
        $content = Get-Content -Path $src -Raw
        
        # Replace namespace
        $content = $content -replace "namespace App\\Modules\\[A-Za-z]+\\Presentation\\Livewire;", "namespace App\Livewire\$destFolder;"
        
        # Replace view references: view('livewire.identity.login') -> view('livewire.public.login')
        # We'll just do a regex replace for the common ones
        $content = $content -replace "view\('livewire\.[a-z]+\.", "view('livewire.$($destFolder.ToLower())."
        
        $destFile = "app\Livewire\$destFolder\$(Split-Path $src -Leaf)"
        Set-Content -Path $destFile -Value $content
        Remove-Item -Path $src -Force
    }
}

# Define mappings for View Files (Source directory -> Destination Folder)
# We will move all blade files in a directory to the target folder
$viewDirMappings = @{
    "resources\views\livewire\identity" = "public"
    "resources\views\livewire\iam" = "public"
    "resources\views\livewire\core" = "public"
    "resources\views\livewire\localization" = "admin" # translation manager
}

# Specific moves for identity views because they are split
$identityViews = @{
    "login.blade.php" = "public"
    "register.blade.php" = "public"
    "forgot-password.blade.php" = "public"
    "reset-password.blade.php" = "public"
    "super-admin-dashboard.blade.php" = "admin"
    "role-assignment-manager.blade.php" = "admin"
    "profile-manager.blade.php" = "business"
}

foreach ($view in $identityViews.GetEnumerator()) {
    $src = "resources\views\livewire\identity\$($view.Key)"
    $destFolder = $view.Value
    if (Test-Path $src) {
        Move-Item -Path $src -Destination "resources\views\livewire\$destFolder" -Force
    }
}

# Move remaining catalogs, inventory etc to Business
$businessDirs = @("catalog", "inventory", "manufacturing", "reconciliation")
foreach ($dir in $businessDirs) {
    $srcDir = "resources\views\livewire\$dir"
    if (Test-Path $srcDir) {
        Get-ChildItem -Path $srcDir -Recurse -Filter "*.blade.php" | ForEach-Object {
            Move-Item -Path $_.FullName -Destination "resources\views\livewire\business" -Force
        }
        Remove-Item -Path $srcDir -Recurse -Force
    }
}

# Cleanup empty module livewire dirs
Get-ChildItem -Path "app\Modules" -Recurse -Directory | Where-Object { $_.Name -eq "Livewire" } | ForEach-Object {
    if ((Get-ChildItem -Path $_.FullName).Count -eq 0) {
        Remove-Item -Path $_.FullName -Force
    }
}

Write-Output "Restructuring complete."
