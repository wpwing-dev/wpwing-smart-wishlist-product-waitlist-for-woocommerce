# Competitor Tracking

Reference doc for competitive analysis when planning updates and a future freemium model.

**This file is NOT loaded automatically.** Ask Claude to "review the competitor doc" when planning a release, and it will read this and re-fetch the live pages (numbers below are snapshots and go stale).

- Our plugin: **Smart Wishlist & Product Waitlist for WooCommerce** (wpwing / voboghure)
- Our version at last update: 1.4.0 (2026-11-10) - Requires PHP 8.0, WP 6.4+, tested to WP 7.1 / WC 11.0.1
- Our wordpress.org stats: **fewer than 10 installs, 0 reviews**
- Last snapshot of this doc: 2026-11-24

---

## Positioning summary

Our differentiator: **the only actively maintained free plugin that ships a guest-friendly wishlist AND an automated back-in-stock waitlist in one install, with zero configuration.** Every major competitor does one half only, gates the second half behind premium (MoreConvert), or is effectively abandoned at trivial install counts (BeRocket).

Merchants today install two plugins (typically TI/YITH Wishlist + a back-in-stock notifier) to get what we ship in one. That is the core marketing message.

---

## Quick comparison

| Plugin | Vendor | Installs | Rating | Halves covered | Model | Paid pricing |
|---|---|---|---|---|---|---|
| [YITH WooCommerce Wishlist](#1-yith-woocommerce-wishlist-market-leader-weakest-rating) | YITH | 400,000+ | 3.9 | Wishlist | Freemium | ~$70-100/yr (YITH-class) |
| [TI WooCommerce Wishlist](#2-ti-woocommerce-wishlist-the-free-tier-feature-bar) | TemplateInvaders | 100,000+ | 4.7 (501) | Wishlist | Freemium | Premium tier |
| [WPC Smart Wishlist](#3-wpc-smart-wishlist) | WPClever | 100,000+ | 4.8 (34) | Wishlist | Freemium | Premium tier |
| [Back In Stock Notifier](#4-back-in-stock-notifier-bisn---waitlist-category-leader) | ProPluginsLab | 20,000+ | 4.7 (122) | Waitlist | Free + 16 add-ons | One-time per add-on |
| [MoreConvert Wishlist](#5-moreconvert-wishlist-closest-business-model-peer) | MoreConvert | 8,000+ | 4.9 (120) | Both (waitlist = premium) | Freemium | from $29/yr |
| [Waitlist WooCommerce](#6-waitlist-woocommerce-xootix) | XootiX | 4,000+ | 4.6 (114) | Waitlist | Free + 4 add-ons | Per add-on |
| [YITH WooCommerce Waitlist](#7-second-tier-and-watch-list) | YITH | 3,000+ | 26 ratings | Waitlist | Freemium | YITH-class |
| [Wishlist and Waitlist](#8-berocket-wishlist-and-waitlist-only-true-free-combined-rival) | BeRocket | 100+ | 3.9 (7) | Both | Free | n/a |
| WooCommerce Waitlist (official) | Woo Marketplace | n/a | n/a | Waitlist | Paid only | ~$49/yr |
| **Ours** | WPWing | **<10** | none yet | **Both, free** | Free (Pro undecided) | TBD - $29-49/yr lane |

Second-tier wishlist plugins tracked but not profiled: WCBoost Wishlist (50,000+), QODE Wishlist (10,000+), WishSuite (1,000+), Addonify Wishlist (1,000+), WebToffee Wishlist (900+). Waitlist side: Notifima / MultiVendorX (2,000+), AdMail (100+).

---

## 1. YITH WooCommerce Wishlist (market leader, weakest rating)

- URL: https://wordpress.org/plugins/yith-woocommerce-wishlist/
- Installs: 400,000+ | Rating: **3.9** | Version: **4.20.0** | Last updated: **2026-11-04**
- Requires: **WP 6.9+, PHP 8.0+** | Tested: WP 7.1 / WC 11.3 | 29 locales

**Free:** wishlist page selection, add-to-wishlist button placement across shop/archive, variation support, customizable wishlist table columns, social sharing, responsive layout.

**Premium:** admin view of customer wishlists, popular-products tracking, promotional emails to users who wishlisted, quote requests with notes, multiple wishlists, public/private/shared visibility, layouts and widgets, quantities, drag-and-drop reordering.

**Why they matter:** 3.9 across 400k installs is a large unhappy user base. Multiple vulnerability disclosures in late 2026 (stored XSS in 4.19.0, unauthenticated wishlist renaming in 4.18.1). Reviews cite upsell nagging and bloat.

---

## 2. TI WooCommerce Wishlist (the free-tier feature bar)

- URL: https://wordpress.org/plugins/ti-woocommerce-wishlist/
- Installs: 100,000+ | Rating: 4.7 (501 reviews) | Version: **2.14.2** | Last updated: **2026-11-04**
- Requires: WP 6.1+, PHP 7.4+ | Tested: **WP 7.1.4 / WC 11.2.1**

**Free:** setup wizard, customizable button with **predefined icons or custom icon upload**, wishlist page or **My Account section** placement, **product-level wishlist counts shown on the button**, shortcodes, social sharing, customizable table columns, auto-remove after add-to-cart, cache-friendly AJAX, **REST API**, 50+ integrations, WPML/Polylang, GDPR-clean.

**Premium:** multiple wishlists, privacy levels, drag-and-drop sorting, wishlist total price, quantities, admin analytics, promo emails, estimate requests.

**Why they matter:** this is the wishlist free-tier bar. In late 2026 they caught up to WP 7.1.4 and focused heavily on database migration recovery and counter performance. Our completed R3 (quantities, bulk actions) and R4 (My Account tab, product counts) closed major gaps against them. Remaining gaps: REST API, WPML/Polylang config.

---

## 3. WPC Smart Wishlist

- URL: https://wordpress.org/plugins/woo-smart-wishlist/
- Installs: 100,000+ | Rating: 4.8 (34 reviews) | Version: **6.2.1** | Last updated: **2026-10-20**
- Requires: WP 5.9+ | Tested: WP 7.1

**Free:** guests + logged-in users, popup UI, **stock-status column on the wishlist table**, share via social networks, copy link, button-or-link display, custom button text, position control on archive and single, per-category enablement, **RTL**, menu integration, **wishlist renaming** (6.2.0), **SameSite/Secure cookie hardening** (6.2.1).

**Premium:** statistics, multiple wishlists, per-item notes, custom theme design.

---

## 4. Back In Stock Notifier (BISN) - waitlist category leader

- URL: https://wordpress.org/plugins/back-in-stock-notifier-for-woocommerce/
- Installs: 20,000+ | Rating: 4.7 (122 reviews) | Version: **7.5.3** | Last updated: **2026-11-10**
- Requires: WP 4.7+, PHP 7.4+ | Tested: WP 7.1, WC 11.2

**Free:** guest + member signup, background email sending, duplicate prevention, customizable subject/body with placeholders, **6 form designs + 6 popup designs**, **reCAPTCHA v2/v3 + Cloudflare Turnstile**, subscriber dashboard with filtering/sorting, manual + bulk send, GDPR checkbox, **REST API + webhooks**, stock-status filtering, **Fair Sending (first-come-first-served for limited restocks)**, **email speed limiting**, **auto-retry for failed emails**, **dashboard reporting widget**, **estimated stock arrival messaging**, **Waitlist summary box on product edit screen** (7.5.0), Elementor support.

**Paid (16 one-time add-ons, "no subscription" as the pitch):** double opt-in, unsubscribe management, ban emails, Mailchimp / Klaviyo / ActiveCampaign, Twilio SMS, sales-revenue tracking, CSV import/export, subscriber editing.

**Why they matter:** the waitlist bar. In 7.5.0 they added the waitlist summary box on the product edit screen. 7.5.3 patched an input shortcode execution flaw. Our R5 directly targets their Fair Sending and speed limiting, giving merchants holiday restock safety for free.

---

## 5. MoreConvert Wishlist (closest business-model peer)

- URL: https://wordpress.org/plugins/smart-wishlist-for-more-convert/
- Installs: 8,000+ | Rating: 4.9 (120 reviews) | Version: **2.0.4** | Last updated: **2026-11-08**
- Requires: WP 5.8+, PHP 7.4+ | Tested: WP 7.1 / WC 11.2
- Pricing: from **$29/yr**

**Free:** wishlist with button placement, Elementor widgets, social share, PDF export, variable products, 5 scheduled email automations, drag-and-drop list management, **Real-Time Cross-Tab Synchronization** (2.0.0), PHP 8.4 support.

**Premium:** multi-list / bundle builder, **back-in-stock notifications**, save-cart-for-later, behavior analytics with segmentation, unlimited email sequences.

**Why they matter:** their **waitlist is still the paid upsell** (our strongest marketing pitch). In 2.0.0 they rebuilt their frontend with cross-tab synchronization. We will counter this in R6 with browser `storage` event broadcast.

---

## 6. Waitlist WooCommerce (XootiX)

- URL: https://wordpress.org/plugins/waitlist-woocommerce/
- Installs: 4,000+ | Rating: 4.6 (114 reviews) | Version: **2.9.3** | Last updated: **2026-11-12**
- Tested: WP 7.1.4

**Free:** waitlist visibility by stock status, restock notifications, popup + inline templates, email logs, guest toggle, custom form fields, shortcodes, new settings UI, WooCommerce native email templates, PHP 8.4 fixes.

**Add-ons (4):** Waitlist Manager (unsubscribe + GDPR dashboard), Email Booster, Custom Form Fields, Export/Import.

---

## 7. Second tier and watch list

- **YITH WooCommerce Waitlist** (3,000+, 26 ratings): free tier is **registered users only** - guest waitlist is paid. We give guests free; say so.
- **Notifima / MultiVendorX** (2,000+): stock-manager direction, waitlist attached. Different category (inventory), overlaps only partly.
- **WCBoost Wishlist** (50,000+, few ratings): growing quietly, wishlist only.
- **WooCommerce Waitlist (official marketplace, ~$49/yr)**: not on .org, sets the price anchor for waitlist functionality alone.

---

## 8. BeRocket "Wishlist and Waitlist" (only true free combined rival)

- URL: https://wordpress.org/plugins/wish-wait-list-for-woocommerce/
- Installs: 100+ | Rating: 3.9 (7 reviews) | Version: 3.6.4 | Last updated: ~2026-08-28 | Tested: WP 7.1

**Free:** wishlist + waitlist creation, buttons under products, text customization on dedicated pages, enable either or both lists, widgets for in-stock waitlist items, on-sale wishlist products, related and popular products.

**Why they matter:** proof the combined niche exists and is under-served - 100+ installs after years, at 3.9. They are not a threat; they are the evidence the positioning is open.

---

## Feature matrix (free tiers)

| Feature | Us 1.0.3 | YITH WL | TI WL | WPC WL | BISN | XootiX | MoreConvert |
|---|---|---|---|---|---|---|---|
| Wishlist | yes | yes | yes | yes | no | no | yes |
| Back-in-stock waitlist | yes | no | no | no | yes | yes | **premium** |
| Guest wishlist | yes | premium toggle | yes | yes | n/a | n/a | yes |
| Guest merge on login/registration | yes | ? | yes | ? | n/a | n/a | ? |
| Variation-aware waitlist | yes | n/a | n/a | n/a | yes | yes | premium |
| Async email queue (Action Scheduler) | yes | n/a | n/a | n/a | yes | ? | ? |
| Auto-retry failed restock email | yes | n/a | n/a | n/a | yes | ? | ? |
| One-click unsubscribe (RFC 8058) | **yes** | n/a | n/a | n/a | no | no | no |
| Custom DB tables (no postmeta bloat) | yes | no | yes | ? | ? | ? | ? |
| CSV export (wishlist + waitlist) | yes | ? | no | no | add-on | add-on | ? |
| CSV import | no | ? | no | no | add-on | add-on | ? |
| Setup wizard | yes | no | yes | no | no | no | no |
| Auto-remove after add-to-cart | yes | no | yes | ? | n/a | n/a | ? |
| Wishlist total price | yes | premium | premium | ? | n/a | n/a | ? |
| Social sharing | yes | yes | yes | yes | n/a | n/a | yes |
| Button style presets | yes (4) | ? | yes | yes | n/a | n/a | yes |
| Icon shape choices / custom icon | no | ? | **yes** | ? | n/a | n/a | ? |
| Per-product wishlist count on button | no | premium | **yes** | ? | n/a | n/a | ? |
| Stock-status column on wishlist | no | ? | ? | **yes** | n/a | n/a | ? |
| Bulk actions / quantities on wishlist | no | premium | premium | ? | n/a | n/a | ? |
| My Account tab | no | ? | **yes** | ? | n/a | n/a | ? |
| Waitlist form/popup design presets | 1 inline | n/a | n/a | n/a | **6+6** | popup+inline | n/a |
| Captcha (reCAPTCHA / Turnstile) | honeypot only | n/a | n/a | n/a | **yes** | ? | n/a |
| Double opt-in | no | n/a | n/a | n/a | add-on | no | no |
| Waitlist visibility by stock status | out-of-stock only | n/a | n/a | n/a | yes | **yes** | premium |
| Email log screen | no | n/a | n/a | n/a | yes | **yes** | premium |
| Admin demand analytics / ranking | no | premium | premium | premium | widget | no | premium |
| REST API | no | no | **yes** | no | **yes** | no | ? |
| Webhooks | no | no | no | no | **yes** | no | ? |
| Elementor / blocks | shortcode only | ? | yes | ? | yes | shortcode | **yes** |
| RTL stylesheet | no | ? | ? | **yes** | ? | ? | ? |
| WPML / Polylang config | no | yes | **yes** | ? | add-on | ? | yes |
| Email marketing integrations | hooks only | premium | premium | n/a | add-ons | add-on | premium |
| Price-drop / on-sale alert to wishlisters | no | premium | premium | n/a | n/a | n/a | premium |
| GDPR export/erasure integration | yes | ? | yes | ? | checkbox only | add-on | ? |

---

## Cross-market observations (2026-09-11)

- **Nobody free bundles both halves credibly.** BeRocket does at 100 installs and 3.9 stars; MoreConvert sells the waitlist half from $29/yr. That gap is the entire product thesis and it is still open.
- **The waitlist free bar moved this year.** BISN added Fair Sending, speed limiting, estimated stock arrival and a reporting widget to their *free* tier in 2026. Waitlist parity is a moving target; wishlist parity is not.
- **Captcha and form presets are the two most visible waitlist gaps** for us - both are cheap and both appear directly in the merchant's install-decision comparison.
- **REST API is free in both category leaders** (TI and BISN) and we have none. It is table stakes for the "integrates with anything" claim.
- **Freshness:** TI, WPC, XootiX and MoreConvert all declare **tested to WP 7.0.4**; we and BISN and YITH declare 7.1. Keep that edge every WordPress cycle - it shows on the listing card.
- **Ratings are the real moat:** TI 501 reviews, BISN 122, MoreConvert 120, us 0. No feature closes that gap; only a review engine does (see MARKETING-PLAN.md section 1d).
- **Price ladder for a future Pro:** MoreConvert $29/yr -> our suggested $29-49/yr lane -> official Woo Waitlist ~$49/yr -> YITH/TI ~$70-100/yr. ProPluginsLab's one-time add-on model is the alternative to study - "no subscription" is their headline selling point and it converts.

---

## How to use this doc when planning

1. Tell Claude: "review docs/COMPETITORS.md and re-fetch the pages."
2. Ask for: gaps in our free tier vs. competitor free tiers; which paid features convert across competitors (candidates for a Pro tier); price positioning.
3. After any review, update the snapshot numbers and the date above.

### Verify / TODO on next review

- MoreConvert Pro exact tier prices and what the $29 tier actually includes (landing page shows "from").
- XootiX add-on pricing (not published on the .org listing).
- Whether MoreConvert ships a release after ~mid-07-2026 (stall signal, or a re-acceleration).
- TI's tested-up-to - if they jump to 7.1 our freshness edge disappears.
- BISN free-tier additions since 7.4.2 - they move fastest of anyone here.
- Track our own first review and install bracket at every snapshot.
