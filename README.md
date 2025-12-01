# WooCommerce Discount Display

A WordPress plugin that automatically displays discount information below product prices when products are on promotion in WooCommerce.

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-green.svg)
![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-orange.svg)

## 📋 Description

WooCommerce Discount Display enhances your WooCommerce store by automatically showing customers how much they're saving on discounted products. The plugin displays discount information in an attractive format: **"Save: [amount] -X%"** directly below the product price.

### ✨ Key Features

- **Automatic Discount Display** - Shows savings amount and percentage for all sale products
- **Variable Product Support** - Dynamically updates discount information when customers select product variations
- **Simple & Variable Products** - Works seamlessly with both product types
- **Responsive Design** - Mobile-friendly display that adapts to all screen sizes
- **Theme Compatible** - Pre-configured compatibility with popular themes (Storefront, Astra, OceanWP, GeneratePress, Phlox)
- **Customizable Styling** - Easy-to-modify CSS for matching your brand
- **Translation Ready** - Fully translatable with included EN & BG translations
- **Lightweight** - Minimal performance impact with optimized code
- **WooCommerce Native** - Integrates seamlessly with WooCommerce pricing system

## 🚀 Installation

### Via WordPress Admin

1. Download the latest release from this repository
2. Navigate to **Plugins > Add New** in your WordPress admin
3. Click **Upload Plugin** and select the downloaded ZIP file
4. Click **Install Now** and then **Activate**

### Manual Installation

1. Download and extract the plugin files
2. Upload the `woo-discount-display` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress

### Requirements

- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.0 or higher

## 💡 Usage

The plugin works automatically once activated! No configuration needed.

### How It Works

1. **Simple Products** - Discount information appears immediately below the product price
2. **Variable Products** - Discount updates dynamically when customers select variations

### Discount Display Format

```
Save: $XX.XX -XX%
```

- The savings amount is formatted according to your WooCommerce currency settings
- The percentage is automatically calculated and rounded down
- Both values update in real-time for variable products

## 🎨 Customization

### Styling

The plugin includes a customizable CSS file at `assets/style.css`. You can modify:

- Background colors
- Text colors
- Border radius
- Padding and margins
- Font sizes and weights
- Hover effects

**Example:** The default red theme can be easily changed to green by uncommenting the alternative style in the CSS file.

### Theme Compatibility

Pre-configured styles are included for:
- Storefront
- Astra
- OceanWP
- GeneratePress
- Phlox

The plugin automatically adapts to most themes without additional configuration.

## 🌍 Translation

The plugin is translation-ready and includes:
- **English (en_US)** - Default
- **Bulgarian (bg_BG)** - Complete translation

### Adding Your Language

1. Use the included `.pot` file in the `languages/` folder
2. Create translations with Poedit or similar tools
3. Save as `woo-discount-display-{locale}.po` and `.mo`
4. Place files in the `languages/` folder

## 📸 Screenshots

*Screenshots will be added soon to demonstrate the plugin in action*

## 🔧 Technical Details

### File Structure

```
woo-discount-display/
├── assets/
│   ├── style.css                    # Main stylesheet
│   └── variation-discount.js        # Variable product handler
├── languages/
│   ├── woo-discount-display.pot     # Translation template
│   ├── woo-discount-display-en_US.po
│   ├── woo-discount-display-en_US.mo
│   ├── woo-discount-display-bg_BG.po
│   └── woo-discount-display-bg_BG.mo
└── woo-discount-display.php         # Main plugin file
```

### Features Implementation

- **Smart Detection** - Only displays for products with active sale prices
- **Precision Calculation** - Handles floating-point calculations correctly
- **Currency Aware** - Respects WooCommerce currency format settings
- **DOM Observer** - Uses MutationObserver for reliable variation updates
- **Performance Optimized** - JavaScript only loads on product pages with variations

## 📝 Changelog

### Version 1.0.0 (2025-11-19)
- Initial public release
- Simple product discount display
- Variable product discount display with real-time updates
- Multi-language support (EN, BG)
- Theme compatibility layer
- Responsive design

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 👨‍💻 Author

**2GOOD Technologies Ltd.**
- Website: [https://2good.tech](https://2good.tech)

## 🐛 Support

If you encounter any issues or have questions:
1. Check the [Issues](https://github.com/2good-tech/woo-discount-display/issues) page
2. Create a new issue if your problem isn't already listed
3. Provide as much detail as possible (WordPress version, WooCommerce version, theme, etc.)

## ⭐ Show Your Support

If you find this plugin helpful, please consider:
- Giving it a ⭐ on GitHub
- Sharing it with others
- Contributing to its development

---

**Made with ❤️ by 2GOOD Technologies Ltd.**
