# Product Release Roadmap: Sept 2026 - Mar 2027

**Target Plugin:** Smart Wishlist & Product Waitlist for WooCommerce (`wpwing-smart-wishlist-product-waitlist-for-woocommerce`)
**Current Baseline:** v1.0.3 shipped 2026-09-08 (auto-remove after add-to-cart, wishlist total, social sharing)
**Release Cadence:** Biweekly (every 2 weeks) Tuesday releases, counted from the 2026-09-08 baseline. 14 slots from 2026-09-22 to 2027-03-30 (Pro checkpoint 2027-04-13).
**Core Objective:** Free growth and directory health only. Close free-tier parity gaps against TI Wishlist and Back In Stock Notifier, then extend the lead on the one thing no free competitor offers - **both halves in one plugin with real admin demand data**.

**No Pro launch this cycle.** Pro is a decision made at R14 (2027-04-13), informed by real install and review data - see Section 5. No v2.0.0 milestone, no commercial campaign date is committed here.

---

## 1. Strategic Principles

- **The waitlist bar moves; the wishlist bar does not.** Back In Stock Notifier (20,000+ installs, 4.7) ships near-weekly and added Fair Sending, email speed limiting, estimated stock arrival and a reporting widget to their *free* tier during 2026. Wishlist leaders (TI 2.12.1, WPC 6.0.9, MoreConvert 1.9.21) are all stale on WP 7.0.4. Waitlist work is therefore the perishable half of parity and gets the earlier slots.
- **Trust before polish on the waitlist.** We ship honeypot + rate limiting; BISN ships reCAPTCHA v2/v3 and Turnstile free. A merchant evaluating two waitlist plugins reads "bot protection" on the feature list before anything else. R1 fixes that.
- **Our unpaid wedge is MoreConvert's paid feature.** MoreConvert (8,000+, 4.9) sells back-in-stock as the premium upgrade on a free wishlist. Every release should keep both halves visibly first-class so that comparison stays true.
- **Demand analytics is the retention feature.** Wishlist plugins get installed, configured once, and forgotten. Ranked "most wished" and "highest demand" data gives merchants a reason to open the admin screen weekly - every competitor gates it behind premium (YITH, TI, WPC, MoreConvert). Shipping it free is both a differentiator and a churn brake.
- **Freshness is a ranking input we fully control.** Four of six tracked competitors declare tested-up-to WP 7.0.4; we declare 7.1. Biweekly releases plus same-week compatibility bumps hold that edge.
- **Cadence must not outrun QA.** Every release: `make check`, `make test-unit`, and the Playwright e2e suite green before tagging. If a week's scope slips, push it one slot rather than shipping half-done - the plugin has 0 reviews and cannot absorb a bad one.
- **Harden before the spike, not during it.** Black Friday 2026-11-27 / Cyber Monday 2026-11-30 produce the year's biggest restock-email volume for free-tier users. Throughput hardening lands R5 (2026-11-24); R6 (2026-12-08) is patch-only.

---

## 2. Release Overview (14 slots, free-tier only)

| # | Date (Tue) | Version | Primary Scope & Headline Feature |
| :-- | :--- | :--- | :--- |
| R0 | 2026-09-08 | v1.0.3 | **Shipped.** Auto-remove on add-to-cart, wishlist total, social sharing |
| R1 | 2026-09-22 | v1.1.0 | **Waitlist trust:** reCAPTCHA v3 / Turnstile + waitlist email log screen |
| R2 | 2026-10-13 | v1.2.0 | Waitlist form presets (3 inline designs + popup mode) + stock-status visibility rules |
| R3 | 2026-10-27 | v1.3.0 | Wishlist page UX: bulk actions, quantity, stock-status column, icon shapes |
| R4 | 2026-11-10 | v1.4.0 | My Account tab + per-product wishlist counts on the button |
| R5 | 2026-11-24 | v1.5.0 | **BFCM hardening:** send-rate control, fair sending, large-queue profiling |
| R6 | 2026-12-08 | v1.5.1 | **Patch window** - no scheduled feature scope, BFCM support cover |
| R7 | 2026-12-22 | v1.6.0 | Admin demand analytics: ranked dashboard widget + product-list count column |
| - | 2026-12-29 | - | **Holiday freeze - no release** |
| R8 | 2027-01-19 | v1.7.0 | REST API: read/join/leave for both halves + webhook on restock |
| R9 | 2027-02-02 | v1.8.0 | Double opt-in for guest waitlist signups + unsubscribe management screen |
| R10 | 2027-02-16 | v1.9.0 | Blocks: `@wordpress/scripts` pipeline + wishlist/waitlist blocks |
| R11 | 2027-03-02 | v1.10.0 | Elementor widgets (button, form, counter) reusing the block callbacks |
| R12 | 2027-03-16 | v1.11.0 | i18n + RTL: `wpml-config.xml`, RTL stylesheet, `.pot` refresh, locale QA |
| R13 | 2027-03-30 | v1.12.0 | Price-drop badge + on-sale nudge email to wishlist holders |
| R14 | 2027-04-13 | v1.13.0 | Retrospective, cache/theme compatibility pass, **Pro decision checkpoint** |

**Slack warning:** R6 is the only pure buffer slot. If two features slip, the 2027 dates move; do not absorb slippage by skipping the e2e run.

---

## 3. Detailed Implementation Plan

### September - October 2026

#### R1 - 2026-09-22 - v1.1.0: Waitlist Trust (captcha + email log)
- Optional **reCAPTCHA v3** and **Cloudflare Turnstile** on the waitlist form, alongside the existing honeypot and rate limiting. Provider select + site/secret key fields in `src/app/Core/Settings.php`; verification in `WaitlistController::ajax_join_waitlist()` before any DB write. Keys empty = feature off, no behavior change for existing installs.
- **Waitlist email log screen** under the existing WPWing admin menu: per-entry send status, timestamp, retry count, last error. The retry path already exists (`WaitlistController::retry_restock_email`) but the outcome is invisible to merchants today - log the result rather than adding new sending logic.
- Both are direct BISN free-tier parity items and the two most visible gaps on any side-by-side comparison.
- Changelog must also carry one fix or hardening item (see Section 4).

#### R2 - 2026-10-13 - v1.2.0: Form Presets + Stock-Status Visibility
- **Three inline form designs + a popup/modal mode** for `src/templates/waitlist-form.php`. BISN ships 6+6 free and XootiX ships popup + inline; we ship one inline form. Build the presets as CSS classes over one markup structure - do not fork the template per design, or every future form change becomes four edits.
- **Waitlist visibility rules by stock status:** out of stock (current behavior), on backorder, and optionally in stock. XootiX and BISN both expose this free; merchants running backorders cannot use us today.
- Preview of the selected design inside settings, so the choice is not blind.

#### R3 - 2026-10-27 - v1.3.0: Wishlist Page UX
- `src/templates/wishlist-view.php` currently renders Product / Price / Action only. Add: **stock-status column** (WPC ships it free), **per-item quantity selector**, and **bulk actions** - select all, add all to cart, clear wishlist.
- **Icon shape choices** for the wishlist button (heart / bookmark / bell / star) completing the v1.0.1 button-style work. TI ships predefined icons plus custom upload free; shapes are the visible half.
- One release because all four touch the same template and the same AJAX handlers - splitting them means editing that code twice.
- Quantities are premium in both YITH and TI. Say so in the release note.

#### R4 - 2026-11-10 - v1.4.0: My Account Tab + Social Proof Counts
- **My Account integration:** a "My Wishlist & Waitlist" tab via `woocommerce_account_*` hooks - the codebase has zero of these today. TI offers My Account placement free and it is where logged-in shoppers look first.
- **Per-product wishlist counts on the button** ("saved by 32 shoppers"), with a threshold setting so a count of 1 never renders. TI ships this free; YITH gates it. Read from the existing `wpwing_wl_wishlists` table with a cached aggregate - never a per-render `COUNT(*)` on an archive page.
- Cache note: the count must survive page caching. Render it through the same AJAX path the badge uses rather than in the cached HTML.

---

### November - December 2026

#### R5 - 2026-11-24 - v1.5.0: BFCM Throughput Hardening
- **Send-rate limiting** (emails per minute) and **Fair Sending** - first-come-first-served ordering for restocks with limited quantity, so the earliest subscriber is emailed first. Both are BISN free-tier features and both matter most exactly during BFCM restocks.
- Profile `WaitlistController::process_restock_queue()` on a several-thousand-entry queue: confirm batch chaining holds, no query fans out per entry, and Action Scheduler is not starved by one product's blast.
- Admin notice if Action Scheduler is unavailable already exists - extend it with a queue-depth readout so a stalled queue is visible before a merchant notices missing emails.
- This ships **before** the spike, not after it.

#### R6 - 2026-12-08 - v1.5.1: BFCM Patch Window
- No feature scope. No release at all unless something breaks. Watch support channels through Black Friday (2026-11-27) and Cyber Monday (2026-11-30) and patch only.

#### R7 - 2026-12-22 - v1.6.0: Admin Demand Analytics
- **Dashboard widget** with ranked "Most Wished Products" and "Highest Demand Waitlist Products" over a selectable window (7 / 30 / 90 days).
- **Wishlist and waitlist count columns** on the WooCommerce Products list screen, sortable.
- Both come off the existing custom tables; add the covering index in `src/app/Core/Database.php` with a migration in `Activator.php` rather than sorting in PHP.
- Premium in YITH, TI, WPC and MoreConvert. This is the strongest free-tier differentiator in the whole plan and the reason a merchant opens our admin screen after setup week.
- No revenue-attribution claims yet - ranking first, attribution only if R14 keeps it.

*No release 2026-12-29 to 2027-01-12 - holiday freeze.*

---

### January - March 2027

#### R8 - 2027-01-19 - v1.7.0: REST API + Webhooks
- `register_rest_route` endpoints under `wpwing/v1`: read wishlist, add/remove item, read waitlist, join/leave. Nonce + capability checks for own-user access; no admin-wide read without `manage_woocommerce`.
- **Webhook on restock notification sent**, mirroring BISN's free webhook support - this is what lets merchants wire Zapier/n8n/Make without us building integrations.
- Free in both TI and BISN; we have zero REST surface today. Required before any "integrates with anything" marketing claim.

#### R9 - 2027-02-02 - v1.8.0: Double Opt-In + Unsubscribe Management
- Optional **double opt-in** for guest waitlist signups: pending state in `wpwing_wl_waitlists`, confirmation email, token expiry, and purge of unconfirmed rows on the existing `Cron.php` cleanup schedule.
- **Unsubscribe management screen** listing opt-outs, pairing with the RFC 8058 one-click header we already send.
- Both are **paid add-ons at ProPluginsLab**. Shipping them free is the sharpest GDPR/deliverability story in the category and directly answers "is this plugin going to get my domain blocklisted".

#### R10 - 2027-02-16 - v1.9.0: Gutenberg Blocks
- Stand up `@wordpress/scripts` - `package.json` currently carries Playwright only, no JS build pipeline. Budget the slot for the pipeline plus two blocks, not four blocks.
- Native blocks for the wishlist page and waitlist form, wrapping the same render callbacks the shortcodes use. No duplicated markup.
- Shortcode-only is the visibly dated part of our listing in 2027.

#### R11 - 2027-03-02 - v1.10.0: Elementor Widgets
- Three widgets: Wishlist Button, Waitlist Form, Wishlist Counter Icon - each reusing the R10 render callbacks.
- MoreConvert ships Elementor widgets free and BISN advertises Elementor support; page-builder users filter plugins on this.

#### R12 - 2027-03-16 - v1.11.0: i18n and RTL
- `wpml-config.xml` for WPML/Polylang plus a tested-compatibility statement in `readme.txt`. Free in TI, a paid add-on at ProPluginsLab.
- RTL stylesheet (WPC ships RTL free; our CSS has no RTL pass today).
- `make pot` refresh and a locale QA pass on the strings added since v1.0.0.
- Translated listings surface in localized plugin directories - a far less competitive search landscape (see MARKETING-PLAN.md section 1f).

#### R13 - 2027-03-30 - v1.12.0: Price-Drop and On-Sale Nudges
- Price-drop badge on wishlist items plus an opt-in on-sale email to wishlist holders. Both key off the same price-change detection, reusing the Action Scheduler queue built for restock sends.
- Premium at YITH, TI and MoreConvert; free nowhere. Deliberately late - it is a differentiator, not parity, and it only pays off once there is an install base to email.
- Hard rule: the nudge email respects the same unsubscribe list and List-Unsubscribe header as restock alerts. One opt-out means out of both.

#### R14 - 2027-04-13 - v1.13.0: Retrospective and Pro Decision
- Cache-plugin compatibility verification (WP Rocket, LiteSpeed, Redis/Memcached object cache) and a theme-conflict pass on the button render hooks.
- Developer hook documentation page - hooks such as `wpwing_wl_wishlist_item_added` and `wpwing_wl_product_stock_changed` already exist; this documents and fills gaps.
- Publish a "Six months in" post and the Q2 2027 roadmap.
- **Pro decision checkpoint**, informed by: real release velocity across R1-R13, install bracket and review count, which support requests repeat, and whether demand analytics (R7) is what merchants actually cite.

---

## 4. Operational Checklist for AI Agents & Developers

- **Reality-check the plan first.** Before starting a release, confirm this document's assumed starting state against the actual code - check the plugin header version, `src/readme.txt` changelog, and the relevant class. The previous roadmap listed several already-shipped items as pending (the count badge and CSV export were both in the codebase from the earliest commits).
- **Cadence:** biweekly Tuesday releases. If scope slips, move the feature to the next slot; do not stack two features into one slot to catch up.
- **Verify before ship:** `make check` (lint + phpcs + version consistency), `make test-unit`, and the Playwright e2e suite green before tagging. `make analyse` clean.
- **Tag and dist every release:** `make release V=x.y.z`, then `make tag V=x.y.z`. Untagged releases leave `make changelog` with nothing to diff.
- **Changelog rule:** every release carries at least one fix or hardening item alongside the headline feature - except R6, whose entire scope *is* fixes.
- **Dates in `readme.txt` use yyyy-mm-dd**, matching the existing changelog entries.
- **Compatibility watch:** bump `Tested up to:` in both `src/readme.txt` and the plugin header the same week a major WordPress or WooCommerce release lands. Four of six tracked competitors are stale at WP 7.0.4 - that edge is free and visible on the listing card.
- **Settings defaults are backward compatible:** every new setting added by this plan defaults to current behavior. An existing install that updates and touches nothing must behave identically.
- **Email discipline:** anything that sends to a shopper goes through the existing Action Scheduler queue and honors the unsubscribe list and the RFC 8058 header. No new direct `wp_mail` send paths.
- **Query discipline:** new counts and rankings read the custom tables with an index, never a per-render `COUNT(*)` on archive pages, and never postmeta.
- **Support beats features:** a regression report takes priority over that slot's feature. Ship the fix, slip the feature one slot.

---

## 5. Background Track: Pro Candidates (unscheduled, no launch commitment)

Do not give these away in free-tier releases without a deliberate decision. Revisit scheduling at R14 (2027-04-13):

- Multiple named wishlists per user, with public / private / shared links.
- Email marketing integrations: Mailchimp, Klaviyo, ActiveCampaign, Brevo.
- SMS / WhatsApp restock notifications (Twilio).
- Automated wishlist reminder campaigns ("still thinking about it?", price drop, low stock) beyond the single R13 nudge.
- Advanced analytics with segmentation and revenue attribution (wishlisted -> purchased, notified -> purchased) on top of the free R7 ranking UI.
- Quote requests / estimates from a wishlist (B2B angle; YITH premium).
- Waitlist CSV import and filtered export.
- Priority support.

**Model:** freemium single Pro plugin (MoreConvert style) is simpler to maintain and price than per-add-on (ProPluginsLab style) - but ProPluginsLab's one-time "no subscription" pitch converts well and deserves a real look at R14. Price lane: $29-49/yr, between MoreConvert ($29) and the official Woo Waitlist extension (~$49).

**Keep the free tier at or above TI and BISN free parity regardless of what Pro becomes.** Reviews are the moat, and a hollowed-out free tier costs reviews faster than Pro earns revenue.

---

## 6. Explicit Non-Goals

- **Multi-vendor marketplace support** (Dokan/WCFM) - large surface, small overlap with our users.
- **Inventory / stock management features** (the Notifima direction) - stay a customer-intent plugin, not an inventory plugin.
- **Our own email sending infrastructure** - stay on `wp_mail` + hooks so merchants keep their existing ESP.
- **PDF export of wishlists** (MoreConvert ships it free) - low demand signal, and it drags in a PDF library that doubles our footprint. Revisit only if support requests ask.
