# WPWing Smart Wishlist & Product Waitlist for WooCommerce

A unified customer-intent engine — wishlist and back-in-stock waitlist in one lightweight WooCommerce plugin.

![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue)
![WordPress](https://img.shields.io/badge/WordPress-%3E%3D6.4-21759b)
![PHP](https://img.shields.io/badge/PHP-%3E%3D8.0-8892bf)
![WooCommerce](https://img.shields.io/badge/WooCommerce-%3E%3D9.0%20tested%2010.8.1-96588a)

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
| PHP         | 8.0     |

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

- PHP 8.0+
- [Composer](https://getcomposer.org/)
- `make`
- `rsync` and `zip` (for building a release)

### Available commands

| Command        | Description                                      |
|----------------|--------------------------------------------------|
| `make setup`   | Bootstrap dev environment (first-time setup)     |
| `make lint`    | PHP syntax check on all plugin files             |
| `make phpcs`   | Run PHP_CodeSniffer against WordPress coding standards |
| `make phpcbf`  | Auto-fix PHPCS violations where possible         |
| `make check`   | Run lint + phpcs + version-check                 |
| `make zip`     | Build a shippable ZIP (runs `check` first)       |
| `make clean`   | Remove the `dist/` folder                        |

### Project structure

```
├── src/                    # Plugin source — everything that ships in the ZIP
│   ├── app/
│   │   ├── Admin/
│   │   │   ├── AdminMenu.php           # Admin menu registration
│   │   │   ├── AdminSettings.php       # Settings API fields and pages
│   │   │   ├── AdminWaitlist.php       # Waitlist entries admin list table
│   │   │   └── WelcomeNotice.php       # One-time dismissible activation notice
│   │   ├── Core/
│   │   │   ├── Activator.php           # Activation hooks, DB table creation, page setup
│   │   │   ├── Cron.php                # Scheduled cleanup tasks
│   │   │   ├── Database.php            # Table name helpers
│   │   │   ├── GdprHandler.php         # GDPR data export / erasure
│   │   │   ├── Plugin.php              # Plugin singleton and boot sequence
│   │   │   ├── ProductDeleteHandler.php # Cascade-delete on product removal
│   │   │   └── Settings.php            # Option get/set/delete wrappers
│   │   ├── Frontend/
│   │   │   └── Assets.php              # Conditional CSS/JS enqueue
│   │   ├── Waitlist/
│   │   │   ├── FrontendWaitlist.php    # Waitlist form rendering and shortcode
│   │   │   ├── GuestMergeHandler.php   # Merge guest waitlist entries on login
│   │   │   └── WaitlistController.php  # AJAX join/leave handlers, notifications
│   │   └── Wishlist/
│   │       ├── AdminWishlist.php       # Admin wishlist view
│   │       ├── FrontendWishlist.php    # Toggle button and shortcode rendering
│   │       ├── GuestMergeHandler.php   # Merge guest wishlist items on login
│   │       └── WishlistController.php  # AJAX toggle and check handlers
│   ├── assets/
│   │   ├── css/wpwing-public.css       # All frontend styles
│   │   └── js/
│   │       ├── wpwing-waitlist.js      # Waitlist form AJAX
│   │       └── wpwing-wishlist.js      # Wishlist toggle AJAX
│   ├── docs/
│   │   └── index.html                  # Action/filter hook reference
│   ├── languages/
│   │   └── *.pot                       # Translation template
│   ├── templates/
│   │   ├── waitlist-form.php           # Waitlist email capture form
│   │   ├── waitlist-view.php           # [wpwing_waitlist] shortcode output
│   │   └── wishlist-view.php           # [wpwing_wishlist] shortcode output
│   ├── vendor/                         # Runtime dependencies (committed)
│   ├── composer.json
│   ├── readme.txt                      # WordPress.org listing copy
│   ├── uninstall.php
│   └── wpwing-smart-wishlist-product-waitlist-for-woocommerce.php
├── tests/
│   ├── Unit/                           # PHPUnit unit tests
│   ├── e2e/                            # Playwright end-to-end tests
│   └── bootstrap.php
├── docker/                             # Local dev environment (Caddy + PHP + WP)
├── docker-compose.yml
├── dist/                               # Build output (not committed)
├── vendor/                             # Dev-only tools — PHPCS, PHPStan (not committed)
├── composer.json
├── Makefile
├── phpcs.xml.dist
├── phpstan.neon
├── phpunit.xml
└── playwright.config.js
```

### Coding standards

The project follows the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) enforced via PHP_CodeSniffer. Run `make check` before every commit.

## License

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html) © [WPWing](https://wpwing.com)
