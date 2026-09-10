# Smart Wishlist & Product Waitlist for WooCommerce

Wishlist and back-in-stock waitlist in one lightweight WooCommerce plugin - the two conversion features most stores install two separate plugins to get, guest-friendly and zero-config, with no ads or upsell nags.

![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue)
![WordPress](https://img.shields.io/badge/WordPress-%3E%3D6.4%20tested%207.1-21759b)
![PHP](https://img.shields.io/badge/PHP-%3E%3D8.0-8892bf)
![WooCommerce](https://img.shields.io/badge/WooCommerce-%3E%3D9.0%20tested%2011.0.1-96588a)

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
git clone https://github.com/wpwing-dev/wpwing-smart-wishlist-product-waitlist-for-woocommerce.git
cd wpwing-smart-wishlist-product-waitlist-for-woocommerce
make setup
```

Then symlink or copy the folder into your local WordPress installation's `wp-content/plugins/` directory and activate it from the WordPress admin.

## Development

### Prerequisites

- PHP 8.0+
- [Composer](https://getcomposer.org/)
- `make`
- `rsync` and `zip` (for building a release)
- [Docker](https://docs.docker.com/get-docker/) and Docker Compose (for the local WordPress stack, unit tests, and e2e tests — see [Testing](#testing) below)

### Available commands

| Command             | Description                                            |
|----------------------|--------------------------------------------------------|
| `make help`          | Show all available commands                            |
| `make setup`         | Bootstrap dev environment (first-time setup)            |
| `make lint`          | PHP syntax check on all plugin files                    |
| `make phpcs`         | Run PHP_CodeSniffer against WordPress coding standards  |
| `make phpcbf`        | Auto-fix PHPCS violations where possible                |
| `make analyse`       | Run PHPStan static analysis                             |
| `make version-check` | Verify version strings are consistent across all files  |
| `make check`         | Run lint + phpcs + version-check                        |
| `make version V=X.Y.Z` | Bump version strings across the plugin                |
| `make pot`           | Regenerate the `.pot` translation file                  |
| `make dist`          | Build a shippable ZIP (runs `check` first)               |
| `make release V=X.Y.Z` | Bump version, build the ZIP, and print the tag/push steps |
| `make tag V=X.Y.Z`   | Create an annotated git tag                              |
| `make changelog`     | Print commits since the last tag                         |
| `make clean`         | Remove the `dist/` folder                                |

### Local WordPress environment

A dockerized WordPress + WooCommerce stack, served over HTTPS through Caddy.

| Command            | Description                                              |
|---------------------|-----------------------------------------------------------|
| `make dev`         | Start the local WordPress dev environment at `https://smart-wishlist.local` (admin / password) |
| `make caddy-trust` | Trust Caddy's local HTTPS CA — run once per machine, requires sudo |
| `make dev-stop`    | Stop the local dev environment                            |
| `make env-reset`   | Wipe all Docker volumes and containers (full reset)        |

Copy `.env.example` to `.env` first if you want non-default DB credentials — otherwise the defaults in `docker-compose.yml` are used as-is.

### Testing

The project has two test suites: fast PHPUnit unit tests for pure PHP logic, and a Playwright e2e suite that drives the dockerized WordPress + WooCommerce site through a real browser. Both are self-contained — each spins up its own containers via Docker, so it's fine to run them without `make dev` running first, and nothing besides Docker itself needs to be installed to run them.

| Command          | Description                                                |
|-------------------|-------------------------------------------------------------|
| `make test-unit` | Run PHPUnit tests (no WordPress stack needed)                |
| `make test-e2e`  | Run the Playwright e2e suite (provisions the full WP + WooCommerce stack, seeds fixture products, resets prior wishlist/waitlist data, then runs the tests) |

`make test-e2e` resets the plugin's wishlist/waitlist data before every run, so results don't depend on what a previous run left behind.

### Project structure

```
├── src/                    # Plugin source — everything that ships in the ZIP
│   ├── app/
│   │   ├── Admin/
│   │   │   ├── AdminMenu.php           # Admin menu registration
│   │   │   ├── AdminSettings.php       # Settings API fields and pages
│   │   │   ├── AdminWaitlist.php       # Waitlist entries admin list table
│   │   │   ├── SetupWizard.php         # First-run guided setup wizard
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
│   ├── vendor/                         # Runtime autoloader (generated, not committed - built into the ZIP)
│   ├── composer.json
│   ├── readme.txt                      # WordPress.org listing copy
│   ├── uninstall.php
│   └── wpwing-smart-wishlist-product-waitlist-for-woocommerce.php
├── tests/
│   ├── Unit/                           # PHPUnit unit tests (pure PHP logic, no WP stack)
│   ├── e2e/                            # Playwright end-to-end tests (helpers.js + one spec file per feature area)
│   └── bootstrap.php                   # Lightweight WP function stubs for PHPUnit
├── docker/                             # Local dev environment (Caddy + WP-CLI + WordPress)
│   ├── caddy/Caddyfile
│   ├── php/                            # Image for the PHPUnit `unit` service
│   └── wordpress/
│       ├── setup.sh                    # Installs WP/WooCommerce, provisions the dev/e2e site
│       ├── seed-e2e-fixtures.php       # Creates the fixture products used by the e2e suite
│       └── reset-e2e-data.php          # Clears wishlist/waitlist data before each e2e run
├── docs/                               # Contributor-facing planning docs (not shipped in the ZIP)
│   ├── UPDATE-PLAN.md                  # Biweekly release roadmap
│   ├── MARKETING-PLAN.md               # Growth plan and directory targets
│   └── COMPETITORS.md                  # Competitor snapshots and feature matrix
├── docker-compose.yml
├── dist/                               # Build output (not committed)
├── vendor/                             # Dev-only tools — PHPCS, PHPStan (not committed)
├── composer.json
├── package.json                        # Playwright dependency for tests/e2e
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
