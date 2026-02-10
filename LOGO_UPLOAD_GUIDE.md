# 🎨 Logo Upload Guide - BadliCash

## 📍 Where to Upload Your Logo

### **Location:** `public/images/logo/`

**Full Path:** `C:\agdp_projects\Badlicash-Payment-Gateway\public\images\logo\`

### Steps:

1. **Create the directory** (if it doesn't exist):
   ```
   public/images/logo/
   ```

2. **Upload your logo file** to this directory:
   - Recommended filename: `logo.png` or `logo.svg`
   - Supported formats: `.png`, `.jpg`, `.jpeg`, `.svg`
   - Recommended size: 200x50px (or similar aspect ratio)

3. **File should be accessible at:**
   ```
   http://localhost:8000/images/logo/logo.png
   ```

---

## 🔧 How to Update the Views

### 1. **Sidebar Logo** (Main Application)

**File:** `resources/views/layouts/app-sidebar.blade.php`

**Current Code (Line ~548):**
```blade
<div class="sidebar-brand">
    <i class="bi bi-wallet2"></i>
    <span>BadiliCash</span>
</div>
```

**Replace with:**
```blade
<div class="sidebar-brand">
    <img src="{{ asset('images/logo/logo.png') }}" alt="BadiliCash" style="height: 32px; width: auto;">
    <span>BadiliCash</span>
</div>
```

**Or if you want logo only (no text):**
```blade
<div class="sidebar-brand">
    <img src="{{ asset('images/logo/logo.png') }}" alt="BadiliCash" style="height: 40px; width: auto;">
</div>
```

---

### 2. **Landing Page Logo** (Homepage)

**File:** `resources/views/landing.blade.php`

Find the logo section and update it similarly.

---

### 3. **Payment Checkout Page Logo**

**File:** `resources/views/checkout/payment.blade.php`

Update the merchant logo section if needed.

---

## 📐 Recommended Logo Specifications

### **For Sidebar:**
- **Format:** PNG (with transparency) or SVG
- **Size:** 200x50px (or 4:1 aspect ratio)
- **Background:** Transparent (preferred)
- **File Size:** < 50KB (optimized)

### **For Landing Page:**
- **Format:** PNG or SVG
- **Size:** 300x75px (or 4:1 aspect ratio)
- **Background:** Transparent or dark background
- **File Size:** < 100KB

### **For Favicon:**
- **Location:** `public/favicon.ico`
- **Size:** 32x32px or 16x16px
- **Format:** ICO or PNG

---

## 🚀 Quick Setup Steps

1. **Create directory:**
   ```bash
   mkdir -p public/images/logo
   ```

2. **Copy your logo file:**
   - Copy `logo.png` to `public/images/logo/logo.png`

3. **Update the view file:**
   - Edit `resources/views/layouts/app-sidebar.blade.php`
   - Replace the icon with `<img>` tag as shown above

4. **Clear cache (if needed):**
   ```bash
   php artisan view:clear
   php artisan cache:clear
   ```

5. **Test:**
   - Refresh your browser
   - Check if logo appears correctly

---

## 🎨 CSS Styling (Optional)

If you need to adjust the logo size or styling, add this to the CSS in `app-sidebar.blade.php`:

```css
.sidebar-brand img {
    height: 32px;
    width: auto;
    max-width: 180px;
    object-fit: contain;
    margin-right: 8px;
}
```

---

## 📝 Multiple Logo Versions

If you have different logos for different contexts:

- **Main Logo:** `public/images/logo/logo.png`
- **White Logo (for dark backgrounds):** `public/images/logo/logo-white.png`
- **Icon Only:** `public/images/logo/logo-icon.png`
- **Favicon:** `public/favicon.ico`

Then use conditionally:
```blade
@if(request()->routeIs('landing'))
    <img src="{{ asset('images/logo/logo-white.png') }}" alt="BadiliCash">
@else
    <img src="{{ asset('images/logo/logo.png') }}" alt="BadiliCash">
@endif
```

---

## ✅ Checklist

- [ ] Created `public/images/logo/` directory
- [ ] Uploaded logo file (logo.png)
- [ ] Updated `app-sidebar.blade.php` view
- [ ] Tested logo display
- [ ] Cleared view cache
- [ ] Verified logo on different screen sizes

---

## 🔍 Troubleshooting

### Logo Not Showing?
1. Check file path: `public/images/logo/logo.png`
2. Check file permissions (should be readable)
3. Clear browser cache (Ctrl+F5)
4. Check browser console for 404 errors
5. Verify `asset()` helper is working: `{{ asset('images/logo/logo.png') }}`

### Logo Too Big/Small?
- Adjust the `height` in the `<img>` tag style
- Or add CSS class for better control

### Logo Not Centered?
- Add CSS: `display: flex; align-items: center; justify-content: center;`

---

## 📞 Need Help?

If you need assistance:
1. Check file permissions
2. Verify file exists in correct location
3. Test URL directly: `http://localhost:8000/images/logo/logo.png`
4. Check Laravel logs for errors

---

*Last Updated: December 2025*

