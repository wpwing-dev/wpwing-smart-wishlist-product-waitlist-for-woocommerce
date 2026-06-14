# WPWing Smart Wishlist & Product Waitlist for WooCommerce

A unified customer-intent engine — wishlist and back-in-stock waitlist in one lightweight WooCommerce plugin.

![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue)
![WordPress](https://img.shields.io/badge/WordPress-%3E%3D6.4-21759b)
![PHP](https://img.shields.io/badge/PHP-%3E%3D8.1-8892bf)
![WooCommerce](https://img.shields.io/badge/WooCommerce-%3E%3D9.0%20tested%2010.7-96588a)

---

## Features

- **Wishlist** — lets shoppers save products for later, for both logged-in users and guests
- **Back-in-stock waitlist** — collects email addresses when a product is out of stock and notifies subscribers when it becomes available again
- Lightweight single plugin instead of two separate tools
- Custom database tables for performance — no post-meta bloat

## Requirements

| Dependency  | Minimum |
|-------------|---------|
| WordPress   | 6.4     |
| WooCommerce | 9.0     |
| PHP         | 8.1     |

## Installation

### From a release ZIP

1. Download the latest `wpwing-wishlist-and-waitlist-for-woocommerce.zip` from [Releases](../../releases).
2. In your WordPress admin go to **Plugins → Add New → Upload Plugin**.
3. Upload the ZIP and click **Install Now**, then **Activate**.

### From source (development)

```bash
git clone https://github.com/wpwing-dev/wpwing-wishlist-waitlist-for-woocommerce.git
cd wpwing-wishlist-waitlist-for-woocommerce
composer install
```

Then symlink or copy the folder into your local WordPress installation's `wp-content/plugins/` directory and activate it from the WordPress admin.

## Development

### Prerequisites

- PHP 8.1+
- [Composer](https://getcomposer.org/)
- `make`
- `rsync` and `zip` (for building a release)

### Available commands

| Command        | Description                                      |
|----------------|--------------------------------------------------|
| `make install` | Install all dependencies (including dev)         |
| `make lint`    | PHP syntax check on all plugin files             |
| `make phpcs`   | Run PHP_CodeSniffer against WordPress coding standards |
| `make phpcbf`  | Auto-fix PHPCS violations where possible         |
| `make check`   | Run lint + phpcs                                 |
| `make dist`    | Build a shippable ZIP (runs `check` first)       |
| `make clean`   | Remove the `dist/` folder                        |

### Project structure

```
├── app/
│   └── Core/
│       ├── Activator.php   # Activation / deactivation hooks, DB table creation
│       ├── Database.php    # Table name helpers
│       ├── Plugin.php      # Plugin singleton and boot sequence
│       └── Settings.php    # Option get/set/delete wrappers
├── vendor/                 # Composer dependencies (not committed)
├── dist/                   # Build output (not committed)
├── Makefile
├── composer.json
├── phpcs.xml.dist
└── wpwing-wishlist-and-waitlist-for-woocommerce.php
```

### Coding standards

The project follows the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) enforced via PHP_CodeSniffer. Run `make check` before every commit.

## License

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html) © [WPWing](https://wpwing.com)
