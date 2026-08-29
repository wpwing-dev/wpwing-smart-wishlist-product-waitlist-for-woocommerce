# v1.0.0 Release Checklist

Target: Monday 2026-08-24 (submission day). Note: for a brand-new plugin, "release" on Monday means submitting to the wordpress.org review queue - the listing goes live only after human review, which typically takes from a few days to several weeks. Distribution from wpwing.com / GitHub Releases can start Monday.

## Code quality gates - PASSING as of 2026-08-23

- [x] `make check` (lint + PHPCS WordPress standards + version consistency) - passes
- [x] `make dist` builds and verifies (57 files, ~287 KB)
- [x] PHPUnit passes (coverage is thin - 1 test; acceptable for 1.0, grow it in 1.1)
- [x] PHPStan level 5 - remaining errors are missing WooCommerce stubs + benign esc_attr(int) coercions, no real defects
- [x] POT file regenerated 2026-08-23 (was stale from June)
- [x] Security review: nonces on all AJAX + admin actions, manage_woocommerce capability checks, $wpdb->prepare everywhere, honeypot + rate limiting on signup, 64-hex token unsubscribe, escaped template output
- [x] GDPR: personal data exporter + eraser registered, uninstall drops tables and options
- [x] HPOS (custom order tables) compatibility declared
- [x] Action Scheduler batching (50/batch, keyset pagination) for restock emails
- [x] RFC 8058 List-Unsubscribe headers on notification emails

## Remaining before submission - BLOCKERS

- [ ] Manual smoke test on a clean site (`make dev`): activate, wishlist toggle as guest + logged-in, guest merge on login, waitlist signup on out-of-stock simple + variable product, restock -> email received, unsubscribe link works, CSV exports, uninstall cleans up
- [ ] wordpress.org listing assets (readme.txt promises 6 screenshots):
  - [ ] screenshot-1.png - wishlist button on product page
  - [ ] screenshot-2.png - wishlist page (shortcode output)
  - [ ] screenshot-3.png - waitlist form on out-of-stock product
  - [ ] screenshot-4.png - variation-aware waitlist form
  - [ ] screenshot-5.png - admin waitlist table with CSV export
  - [ ] screenshot-6.png - admin wishlist ranking
  - [ ] banner-772x250.png and banner-1544x500.png
  - [ ] icon-128x128.png and icon-256x256.png
- [ ] Final read of readme.txt copy (first 150 chars of description are what search shows)
- [ ] Commit pending changes, tag `v1.0.0` (`make tag V=1.0.0`), push tag
- [ ] Submit ZIP at https://wordpress.org/plugins/developers/add/ under the wpwing.com account

## After .org approval

- [ ] SVN: commit plugin to trunk, assets to /assets, tag 1.0.0, confirm Stable tag matches
- [ ] Verify listing renders correctly (screenshots, FAQ, banners)
- [ ] Subscribe to the support forum RSS / email alerts
- [ ] Publish launch post on wpwing.com linking to the .org page
- [ ] Ask 3-5 early users for honest .org reviews (early reviews weigh heavily in search ranking)

## Known nits (fix any time, not blockers)

- README.md says `src/vendor/` is committed, but it is gitignored and built at zip time - update the doc
- README.md clone URL says `wpwing-wishlist-waitlist-for-woocommerce`; actual repo is `wpwing-smart-wishlist-product-waitlist-for-woocommerce`
- Add `php-stubs/woocommerce-stubs` to require-dev so PHPStan noise drops from 69 to ~7
- tests/e2e is empty; Playwright config exists - add the smoke-test flow above as the first e2e spec in 1.1
- PHPStan 1.12 is old; bump to ^2.2 when convenient

## Release-day command sequence

```bash
make check
make dist                     # dist/wpwing-smart-wishlist-product-waitlist-for-woocommerce.zip
git commit -am "Release v1.0.0"
make tag V=1.0.0
git push && git push --tags
```
