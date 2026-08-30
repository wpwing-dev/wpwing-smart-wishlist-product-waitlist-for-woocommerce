# Competitor Analysis - WordPress.org Plugin Directory

Snapshot date: 2026-08-23. Install counts and ratings are from wordpress.org listing pages and will drift - re-check quarterly.

## Positioning summary

Our differentiator: **the only actively maintained free plugin that ships a guest-friendly wishlist AND an automated back-in-stock waitlist in one install, with zero configuration.** Every major competitor does one half only, or gates the second half behind premium (MoreConvert), or is effectively abandoned at trivial install counts (BeRocket).

Merchants today install two plugins (typically TI/YITH Wishlist + a back-in-stock notifier) to get what we ship in one. That is the core marketing message.

## Direct competitors (wishlist + waitlist combined)

| Plugin | Author | Installs | Rating | Notes |
|---|---|---|---|---|
| [Wishlist and Waitlist for WooCommerce](https://wordpress.org/plugins/wish-wait-list-for-woocommerce/) | BeRocket | 100+ | 7 ratings | Only true free combined competitor. Basic buttons, widgets, custom text. Low traction - not a threat, but proves the niche is under-served. |
| [MoreConvert Wishlist](https://wordpress.org/plugins/smart-wishlist-for-more-convert/) | MoreConvert | 8,000+ | 4.9 (120) | Wishlist free; back-in-stock, waitlist, multi-lists, analytics are premium (from $29/yr). Strongest model to study for a future Pro tier. Free tier: Elementor widgets, 100+ integrations, social share, PDF export, email automation (5 templates). |

## Wishlist-only competitors

| Plugin | Author | Installs | Rating | Last updated |
|---|---|---|---|---|
| [YITH WooCommerce Wishlist](https://wordpress.org/plugins/yith-woocommerce-wishlist/) | YITHEMES | 400,000+ | 3.9 (262) | Jul 2026 |
| [TI WooCommerce Wishlist](https://wordpress.org/plugins/ti-woocommerce-wishlist/) | TemplateInvaders | 100,000+ | 4.7 (501) | Jun 2026 |
| [WPC Smart Wishlist](https://wordpress.org/plugins/woo-smart-wishlist/) | WPClever | 100,000+ | 4.8 | Aug 2026 |
| WCBoost - Wishlist | WCBoost | 50,000+ | few ratings | - |
| QODE Wishlist | Qode | 10,000+ | 2 ratings | - |
| WishSuite | HT Plugins | 1,000+ | 11 ratings | - |
| Addonify Wishlist | Addonify | 1,000+ | 4 ratings | - |
| Wishlist for WooCommerce | WebToffee | 900+ | 6 ratings | - |

### YITH WooCommerce Wishlist (market leader, weakest rating)

- Free: dedicated wishlist page, button placement options, show/hide add-remove buttons, shop + product page buttons, customizable wishlist table columns, variation support.
- Premium: admin access to customer wishlists, popular-products tracking, promotional emails to users who wishlisted a product, quote requests, guest vs registered toggles, multiple wishlists, public/private/shared visibility, quantities, drag-and-drop ordering, layouts, widgets.
- Takeaway: 3.9 rating on 400k installs = large unhappy user base. Reviews complain about upsell nagging and bloat. "Lightweight, no nags" is a real wedge against them.

### TI WooCommerce Wishlist (best free feature depth)

- Free: setup wizard, wishlist product counters, flexible button positions, shortcodes, icon choices, auto-remove after add-to-cart, social sharing (Facebook, X, Pinterest, WhatsApp, email), custom CSS, WPML/Polylang, 50+ plugin integrations, REST API, cache-friendly AJAX, GDPR-clean.
- Premium: multiple wishlists, privacy levels, drag-and-drop sorting, wishlist total price, quantities, admin analytics, promo emails, estimate requests, wishlist following.
- Takeaway: this is the free-tier feature bar for wishlists. Social sharing, counters, and auto-remove-on-add-to-cart are the most visible gaps vs us.

### WPC Smart Wishlist

- Free: guests + users, popup UI, share / copy link, button text and position customization, per-category enablement, RTL, menu integration.
- Premium: statistics, multiple wishlists, per-item notes.

## Waitlist / back-in-stock competitors

| Plugin | Author | Installs | Rating | Last updated |
|---|---|---|---|---|
| [Back In Stock Notifier](https://wordpress.org/plugins/back-in-stock-notifier-for-woocommerce/) | ProPluginsLab | 20,000+ | 4.7 | Aug 2026 (very active) |
| [Waitlist Woocommerce](https://wordpress.org/plugins/waitlist-woocommerce/) | XootiX | 4,000+ | 4.6 | Aug 2026 |
| YITH WooCommerce Waitlist | YITHEMES | 3,000+ | 26 ratings | free = registered users only |
| Notifima (stock manager + waitlist) | MultiVendorX | 2,000+ | 46 ratings | - |
| AdMail multilingual notifier | aleswebs | 100+ | 8 ratings | - |
| WooCommerce Waitlist (official, paid) | Woo Marketplace | n/a | n/a | ~$49/yr, not on .org |

### Back In Stock Notifier (category leader)

- Free: guest + member signup, background email sending, email prefill for logged-in users, duplicate prevention, customizable subject/body with placeholders, backorder support, 6 form designs + 6 popup designs, reCAPTCHA and Cloudflare Turnstile, subscriber management dashboard, manual + bulk send, GDPR checkbox, REST API + webhooks, Elementor support, works with simple/variable/grouped products plus WooCommerce Subscriptions and Product Bundles.
- Paid add-ons (one-time pricing, 16 add-ons): double opt-in, unsubscribe management, email domain restrictions, Mailchimp / Klaviyo / ActiveCampaign, Twilio SMS, sales-revenue tracking, CSV import/export, custom CSS, WPML.
- Takeaway: the waitlist feature bar. Their gaps we already beat: no wishlist, no one-click List-Unsubscribe header advertised. Their strengths we lack: form/popup design presets, captcha options, double opt-in, marketing integrations, revenue attribution.

### Waitlist Woocommerce (XootiX)

- Free: waitlist visibility by stock status (out of stock / backorder / in stock), notifications, popup + inline templates, email logs, guest support, custom form fields, shortcodes.
- Add-ons: waitlist manager (unsubscribe + GDPR dashboard), email booster, custom fields, export/import.

## Feature matrix (free tiers)

| Feature | Us 1.0 | YITH WL | TI WL | WPC WL | BISN (ProPluginsLab) | XootiX |
|---|---|---|---|---|---|---|
| Wishlist | yes | yes | yes | yes | no | no |
| Back-in-stock waitlist | yes | no | no | no | yes | yes |
| Guest wishlist | yes | premium toggle | yes | yes | n/a | n/a |
| Guest merge on login | yes | ? | yes | ? | n/a | n/a |
| Variation-aware waitlist | yes | n/a | n/a | n/a | yes | yes |
| Async email queue (Action Scheduler) | yes | n/a | n/a | n/a | yes | ? |
| One-click unsubscribe (RFC 8058 header) | yes | n/a | n/a | n/a | no | no |
| Custom DB tables (no postmeta bloat) | yes | no | yes | ? | ? | ? |
| CSV export | yes | ? | no | no | add-on | add-on |
| Admin demand analytics (top wishlisted) | no (planned, roadmap week 14) | premium | premium | premium | n/a | n/a |
| Social sharing of wishlist | no | premium | yes | yes | n/a | n/a |
| Multiple wishlists | no | premium | premium | premium | n/a | n/a |
| Captcha (reCAPTCHA/Turnstile) | honeypot only | n/a | n/a | n/a | yes | ? |
| Double opt-in | no | n/a | n/a | n/a | add-on | no |
| Form design presets | no | n/a | n/a | n/a | 6+6 | yes |
| Email marketing integrations | hooks only | n/a | n/a | n/a | add-ons | add-on |
| Elementor/blocks | shortcode only | ? | yes | ? | yes | shortcode |
| REST API | no | no | yes | no | yes | no |
| GDPR export/erasure integration | yes | ? | yes (no data collected) | ? | checkbox only | add-on |

## Pricing benchmarks (for a future Pro tier)

- MoreConvert Pro: from $29/yr
- Official WooCommerce Waitlist extension: ~$49/yr
- ProPluginsLab: one-time per add-on, bundle discount, "no subscription" as a selling point
- YITH/TI premium: ~$70-100/yr range typical for YITH-class plugins
