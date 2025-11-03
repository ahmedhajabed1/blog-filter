# AJAX Post Filter - WordPress Plugin

**Version:** 1.0.0  
**Author:** Ahmed haj abed  
**License:** GPL v2 or later

## 📖 Description

A powerful WordPress plugin for filtering blog posts by categories with AJAX, featuring Elementor support, lazy loading, multiple pagination types, and complete customization options.

## ✨ Features

- ✅ **AJAX Filtering** - No page reloads
- ✅ **Elementor Widget** - Drag and drop interface
- ✅ **Lazy Loading** - 50-70% faster page loads
- ✅ **3 Pagination Types** - Standard, Load More, Infinite Scroll
- ✅ **SEO Optimized** - Proper meta tags
- ✅ **Fully Customizable** - Colors, text, styles
- ✅ **Multiple Presets** - Unlimited configurations
- ✅ **Responsive Design** - All devices supported
- ✅ **Professional Admin** - Beautiful interface

## 🚀 Installation

### From WordPress Admin

1. Go to **Plugins → Add New**
2. Click **Upload Plugin**
3. Choose the ZIP file
4. Click **Install Now** and **Activate**

### Manual Installation

1. Unzip the plugin file
2. Upload to `/wp-content/plugins/ajax-post-filter/`
3. Activate through WordPress admin

## 📝 Usage

### Method 1: Elementor (Recommended)

1. Edit page with Elementor
2. Search for "Ajax Post Filter"
3. Drag widget to page
4. Configure settings visually
5. Publish!

### Method 2: Shortcode

```
[ajax_post_filter slug="default-preset"]
```

## ⚙️ Configuration

### Admin Panel

Navigate to **Post Filter** in WordPress admin:

1. **Filter Presets** - Create and manage filter configurations
2. **General Options** - Set default settings
3. **Customization** - Customize colors and text

### Button Text Customization

Customize all button text:
- Apply Button
- Reset Button  
- Load More Button
- Loading Text
- Read More Text

### Color Customization

Customize all colors:
- Primary Color
- Button Background
- Button Text Color
- Button Hover Color

## 📱 Responsive

Works perfectly on:
- Desktop (1920px+)
- Laptop (1200px-1919px)
- Tablet (768px-1199px)
- Mobile (320px-767px)

## 🎨 Elementor Features

- Live preview
- Responsive controls
- Per-widget settings
- Visual interface
- All customization options

## 🔧 Technical Details

**Requirements:**
- WordPress 5.0+
- PHP 7.4+
- jQuery (included with WordPress)

**Compatible with:**
- Elementor 3.0+
- Most WordPress themes
- Popular caching plugins

**Performance:**
- Lazy loading (native + fallback)
- Optimized AJAX queries
- Minimal file size (~150KB)
- Fast load times

## 📚 Documentation

Complete documentation included:
- Installation guide
- Configuration examples
- Troubleshooting tips
- Best practices

## 🛠️ Development

### File Structure

```
ajax-post-filter/
├── ajax-post-filter.php (Main plugin file)
├── readme.txt (WordPress readme)
├── LICENSE.txt
├── admin/
│   ├── css/
│   ├── js/
│   └── class-ajax-post-filter-admin.php
├── public/
│   ├── css/
│   ├── js/
│   └── class-ajax-post-filter-public.php
└── includes/
    ├── class-ajax-post-filter-core.php
    ├── class-ajax-post-filter-ajax-handler.php
    └── class-ajax-post-filter-elementor.php
```

### Hooks & Filters

Developers can extend the plugin using WordPress hooks and filters.

## 🐛 Troubleshooting

### Widget Not Showing
- Clear Elementor cache
- Regenerate CSS/JS
- Check WordPress/Elementor versions

### AJAX Not Working
- Check browser console for errors
- Verify nonce is valid
- Clear site cache

### Styling Issues
- Clear browser cache
- Check for theme conflicts
- Use custom CSS override

## 📞 Support

For support, feature requests, or bug reports:
- WordPress.org support forum
- Contact developer

## 🎓 Credits

- **Developer:** Ahmed haj abed
- **Inspired by:** YITH WooCommerce Ajax Product Filter
- **Icons:** Dashicons (WordPress)

## 📄 License

This plugin is licensed under GPL v2 or later.

```
Copyright (C) 2025 Ahmed haj abed

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

## 🙏 Thank You

Thank you for using AJAX Post Filter!

---

**Made with ❤️ by Ahmed haj abed**
