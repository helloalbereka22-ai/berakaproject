# Afan Oromo Language Setup Guide

## ✅ What Has Been Created

### 1. Language Translation File
**Location**: `lang/om/app.php`

A complete Afan Oromo (Oromiffa) translation file with all navigation, UI elements, and content translated including:
- Navigation items (Home, Products, About, Contact, etc.)
- Authentication texts (Login, Register, Password, etc.)
- Product catalog (Add to Cart, Price, Quantity, etc.)
- Shopping cart and checkout
- Orders and payments
- CMS content (Articles, FAQs, Blog)
- General UI elements
- Public website sections

### 2. Database Seeder
**Location**: `database/seeders/AfanOromoLanguageSeeder.php`

A Laravel seeder that adds Afan Oromo to the languages table.

### 3. SQL Script
**Location**: `add_afan_oromo.sql`

A ready-to-run SQL script for manual database insertion.

---

## 🔧 Setup Instructions

### Option 1: Using SQL Script (Recommended - Easiest)

1. **Open phpMyAdmin**:
   - Go to: `http://localhost/phpmyadmin/`
   - Select your database (likely named `albareka` or similar)

2. **Run the SQL Script**:
   - Click on the **SQL** tab
   - Open the file: `add_afan_oromo.sql`
   - Copy all the SQL content
   - Paste it into the SQL query box
   - Click **Go** button

3. **Verify**:
   - Click on the `languages` table
   - You should see a new row with:
     - code: `om`
     - name: `Afan Oromo`
     - native_name: `Afaan Oromoo`
     - is_active: `1`

### Option 2: Using Laravel Seeder

If you have PHP 8.2+ configured:

```bash
# Navigate to project directory
cd c:\xammpp82\htdocs\albareka

# Run the seeder
php artisan db:seed --class=AfanOromoLanguageSeeder
```

### Option 3: Manual Database Entry

1. Open phpMyAdmin
2. Navigate to your database
3. Click on `languages` table
4. Click **Insert** tab
5. Fill in the form:
   - code: `om`
   - name: `Afan Oromo`
   - native_name: `Afaan Oromoo`
   - is_rtl: `0` (unchecked)
   - is_active: `1` (checked)
   - created_at: (current timestamp)
   - updated_at: (current timestamp)
6. Click **Go**

---

## ✅ Testing the Language

After adding Afan Oromo to the database:

1. **Visit Your Homepage**:
   ```
   http://localhost/albareka/public/
   ```

2. **Find Language Switcher**:
   - Look for the language dropdown in the top navigation bar
   - You should now see **4 languages**:
     - 🇬🇧 English
     - 🇪🇹 Amharic (አማርኛ)
     - 🇸🇦 Arabic (العربية)
     - 🇪🇹 Afan Oromo (Afaan Oromoo) ✨ NEW

3. **Switch to Afan Oromo**:
   - Click on the language switcher
   - Select "Afaan Oromoo"
   - The entire website will reload in Afan Oromo!

---

## 📋 Available Pages (All Translated)

Once Afan Oromo is active, all these pages will display in Oromiffa:

### Public Pages (Before Login)
- 🏠 **Home** - `/` - Mana
- 📖 **About** - `/about` - Waa'ee Keenya
- 🏢 **Organizations** - `/organizations` - Dhaabbilee
- 🏭 **Manufacturing** - `/manufacturing` - Oomisha
- 📚 **Bookstore** - `/bookstore` - Mana Kitaabaa
- 📰 **Blog/News** - `/blog` - Oduu
- ❓ **FAQs** - `/faqs` - Gaaffii Baay'ee Gaafataman
- 📞 **Contact** - `/contact` - Nu Quunnamaa

### Shop Pages
- 🛍️ **Products** - `/store/products` - Oomishaalee
- 🛒 **Cart** - `/store/cart` - Gara
- ❤️ **Wishlist** - `/store/wishlist` - Hawwii

### Authentication
- 🔐 **Login** - `/login` - Seeni
- 📝 **Register** - `/register` - Galmaa'i

---

## 🌍 All 4 Languages Now Available

| Language | Code | Native Name | Direction | Status |
|----------|------|-------------|-----------|--------|
| English | `en` | English | LTR | ✅ Active |
| Amharic | `am` | አማርኛ | LTR | ✅ Active |
| Arabic | `ar` | العربية | RTL | ✅ Active |
| Afan Oromo | `om` | Afaan Oromoo | LTR | ✅ Ready |

---

## 📝 Sample Translations

Here are some example translations to verify:

| English | Afan Oromo (Oromiffa) |
|---------|----------------------|
| Home | Mana |
| Products | Oomishaalee |
| About Us | Waa'ee Keenya |
| Contact | Nu Quunnamaa |
| Login | Seeni |
| Register | Galmaa'i |
| Shopping Cart | Gara Bittaa |
| Add to Cart | Gara Keessa Galchi |
| Search | Barbaadi |
| Orders | Ajajawwan |
| Profile | Piroofaayilii |

---

## 🔍 Troubleshooting

### Language Not Showing in Dropdown?

1. **Check Database**:
   - Open phpMyAdmin
   - Go to `languages` table
   - Verify that Afan Oromo exists with `is_active = 1`

2. **Clear Cache**:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

3. **Refresh Browser**:
   - Hard refresh: `Ctrl + Shift + R` (Windows) or `Cmd + Shift + R` (Mac)

### Translations Not Working?

1. **Verify File Location**:
   - Check that `lang/om/app.php` exists
   - Check file permissions (should be readable)

2. **Check Language Code**:
   - Database: code should be exactly `om`
   - Folder name: should be exactly `om`

---

## 🎉 Success!

Once you complete the setup (run the SQL script), your Al-Baraka application will support **4 languages** including Afan Oromo!

Users can switch between languages anytime using the language selector in the navigation bar, and all content will be displayed in their chosen language.

---

## 📧 Need Help?

If you encounter any issues:
1. Check that the SQL script ran successfully
2. Verify the `lang/om/app.php` file exists
3. Clear Laravel cache
4. Check browser console for JavaScript errors
5. Verify database connection is working

---

**Created**: 2026-08-29
**Files Modified**:
- ✅ `lang/om/app.php` (created)
- ✅ `database/seeders/AfanOromoLanguageSeeder.php` (created)
- ✅ `add_afan_oromo.sql` (created)

**Database Changes Required**:
- ✅ Insert Afan Oromo into `languages` table
