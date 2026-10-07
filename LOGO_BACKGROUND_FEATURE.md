# System Logo and Login Background Upload Feature

## ✅ What Was Added

### 1. Settings Page (Super Admin → Settings)
**Location**: Super Admin menu → System → Settings

**New Features**:
- **System Logo Upload**: Upload a custom logo that appears in the admin sidebar navigation
- **Login Background Upload**: Upload a custom background image for the login page

**Features**:
- Image preview before saving
- Shows current uploaded images
- Automatic deletion of old images when uploading new ones
- File size limits:
  - System Logo: 2MB max
  - Login Background: 5MB max
- Stored in `storage/app/public/system/`

### 2. Files Modified

#### Backend:
- **`app/Livewire/Admin/System/Settings.php`**
  - Added `WithFileUploads` trait
  - Added `$systemLogo`, `$loginBackground` properties
  - Added `$currentLogoPath`, `$currentBgPath` properties
  - Updated `mount()` to load existing images
  - Updated `save()` with image upload logic and validation

- **`app/Services/SettingsService.php`**
  - Added `BRANDING_KEYS` constant with `system_logo_path` and `login_bg_path`
  - Added `branding()` method

#### Frontend:
- **`resources/views/livewire/admin/system/settings.blade.php`**
  - Added "System Branding" section with upload fields
  - Added image preview functionality
  - Professional card-based design matching the rest of the UI

- **`resources/views/components/layouts/guest.blade.php`**
  - Added logic to check for custom login background
  - Displays custom background if uploaded
  - Falls back to gradient animation if no custom background
  - Added dark overlay for better text readability

- **`resources/views/components/layouts/admin.blade.php`**
  - Added logic to check for custom system logo
  - Displays custom logo in sidebar if uploaded
  - Falls back to default "A" gradient icon if no custom logo

### 3. Database
Settings are stored in the `settings` table with these keys:
- `system_logo_path`: Path to the uploaded logo
- `login_bg_path`: Path to the uploaded login background

## 📁 Storage Structure

```
storage/
└── app/
    └── public/
        ├── system/          ← Logo and background images
        │   ├── [logo files]
        │   └── [background files]
        └── products/        ← Product images (already exists)
```

## 🚀 How to Use

### For Super Admin:

1. **Navigate to Settings**:
   - Login as Super Admin
   - Go to: **Super Admin** → **System** → **Settings**

2. **Upload System Logo**:
   - In the "System Branding" card (top-left)
   - Click "Upload Logo" or "Change Logo"
   - Select your logo image (PNG, JPG, etc.)
   - Preview appears immediately
   - Click "Save Settings" to apply

3. **Upload Login Background**:
   - In the "System Branding" card
   - Click "Upload Background" or "Change Background"
   - Select your background image
   - Preview appears immediately
   - Click "Save Settings" to apply

### Where Images Appear:

**System Logo**:
- Shows in the admin sidebar (top-left corner)
- Appears next to "Albareka ERP" text
- Visible on all admin pages

**Login Background**:
- Covers the entire login page background
- Has a dark overlay for better contrast
- Replaces the default gradient animation

## ⚠️ Important: Storage Link

**You MUST create a storage link for images to work!**

See `STORAGE_LINK_INSTRUCTIONS.md` for detailed steps.

Quick command (run as Administrator):
```powershell
cd c:\xammpp82\htdocs\albareka
php artisan storage:link
```

Or manually:
```powershell
New-Item -ItemType SymbolicLink -Path "c:\xammpp82\htdocs\albareka\public\storage" -Target "c:\xammpp82\htdocs\albareka\storage\app\public"
```

## 🎨 Design Features

- **Professional UI**: Matches the existing admin design with gradients and modern styling
- **Side-by-side Layout**: Logo and background upload fields are in the same card
- **Image Previews**: See your images before saving
- **Current Images**: Shows currently uploaded images with labels
- **Responsive**: Works on mobile and desktop
- **Smooth Animations**: Hover effects and transitions

## 🔒 Security

- Only Super Admins can upload/change images
- File type validation (images only)
- File size limits enforced
- Old images are deleted when new ones are uploaded
- Stored outside web root for security

## 🧪 Testing

1. **Upload Logo**:
   - Upload a square logo (recommended: 200x200px or larger)
   - Should appear in admin sidebar immediately after saving
   - Should replace the default "A" icon

2. **Upload Background**:
   - Upload a landscape image (recommended: 1920x1080px or larger)
   - Visit the login page
   - Should see your custom background with dark overlay
   - Text should still be readable

3. **Replace Images**:
   - Upload new logo/background
   - Old files should be deleted
   - Only new images should appear

## 🐛 Troubleshooting

**Images not showing?**
- ✅ Check if storage link exists: `Test-Path "c:\xammpp82\htdocs\albareka\public\storage"`
- ✅ Create storage link if missing (see instructions above)
- ✅ Check file permissions on storage directory
- ✅ Clear browser cache

**Upload fails?**
- ✅ Check file size (Logo: 2MB max, Background: 5MB max)
- ✅ Verify file is an image (PNG, JPG, GIF, etc.)
- ✅ Check storage directory is writable

**Logo/Background not appearing?**
- ✅ Hard refresh the page (Ctrl+F5)
- ✅ Check browser console for errors
- ✅ Verify the image path in database (settings table)

## 📝 Notes

- Images are stored in `storage/app/public/system/`
- Accessible via `public/storage/system/` (through symlink)
- Default fallbacks exist if no custom images uploaded
- Images persist across page refreshes and logins
- Can be changed/updated at any time by Super Admin
