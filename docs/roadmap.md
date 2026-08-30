# Product Roadmap

Derived from the gap analysis in [competitor-analysis.md](competitor-analysis.md). Ordering favors items that are cheap for us, visible to merchants, and free in competitors' free tiers (table stakes) before premium-tier differentiators. Laid out as a weekly release cadence for the 6 months following v1.0.0 - one shippable version per week. Solo-dev pace: if a week's item slips because of support-forum triage, push it one week rather than shipping half-done.

## Ongoing, every week - post-launch triage

Runs in parallel with every week below, not a blocking phase:

- Respond to every .org support topic within 48 hours (reviews and response time drive early ranking)
- Watch for theme conflicts around the button render hooks and form CSS
- Watch email deliverability reports (SPF/DKIM misconfigured hosts blaming the plugin)
- If a regression report comes in, it takes priority over that week's feature - ship the fix, slip the feature by a week

## Month 1 (v1.0.1 - v1.0.4) - close the free-tier table-stakes gaps

Goal: the single highest-leverage gap vs. TI/YITH/WPC free tiers - button placement - plus the cheap, visible wins around it.

| Week | Version | Focus | Why |
|---|---|---|---|
| 1 | v1.0.1 | Button placement setting: before/after add-to-cart, on shop/archive loop cards | TI and YITH free have this; we currently only render on single product pages - this is the #1 gap |
| 2 | v1.0.2 | Auto-remove item from wishlist after add-to-cart; wishlist count badge + optional header/menu icon with live AJAX counter | Both TI free features; badge counter is a prerequisite for the mini-drawer in week 6 |
| 3 | v1.0.3 | Social sharing of the wishlist page: link copy, WhatsApp, X, Facebook, email | TI and WPC free have this; confirmed fully absent in current code |
| 4 | v1.0.4 | Icon customization (heart/bookmark/bell/star, outline/filled states); phase wrap-up and regression pass | Rounds out the button-customization work from weeks 1-3 before moving to page-level UX |

## Month 2 (v1.1.0 - v1.1.3) - wishlist page UX and platform infra

Goal: bring the customer-facing wishlist page to table-stakes and lay the block-editor foundation (currently zero JS build tooling in the repo - `@wordpress/scripts` doesn't exist yet, so week 7 is infra + feature together, not just a feature).

| Week | Version | Focus | Why |
|---|---|---|---|
| 5 | v1.2.0 | Wishlist page bulk actions: select all, move all to cart, clear wishlist; add a stock-status column to the table | Current table (`templates/wishlist-view.php`) only has image/name/price/add-to-cart/remove - no bulk actions, no stock column |
| 6 | v1.2.1 | Slide-over mini-wishlist drawer from the header icon, live AJAX badge sync | New surface, not in the gap analysis, but a real UX gap vs. dedicated wishlist plugins; depends on week 2's badge |
| 7 | v1.2.2 | Stand up `@wordpress/scripts` build pipeline; native Gutenberg blocks for `[wpwing_wishlist]` and `[wpwing_waitlist]` | Shortcode-only looks dated in 2026, but there is no JS build tooling in `package.json` today - budget this week for the pipeline, not just the blocks |
| 8 | v1.2.3 | WPML/Polylang config file (`wpml-config.xml`) + tested compatibility statement in readme; wishlist CSV export | Cheap i18n win; wishlist CSV export brings parity with the waitlist CSV export that already ships |

## Month 3 (v1.2.4, v1.3.0 - v1.3.3) - waitlist trust and deliverability

Goal: match Back In Stock Notifier's free-tier trust features without building our own email infrastructure.

| Week | Version | Focus | Why |
|---|---|---|---|
| 9 | v1.2.4 | Optional reCAPTCHA v3 / Cloudflare Turnstile alongside the existing honeypot | BISN free has this; we only have honeypot + rate limiting today |
| 10 | v1.3.0 | Backorder-allowed products setting (show waitlist on backorder too); a second and third form design preset | BISN free has both; we ship one form design today |
| 11 | v1.3.1 | Double opt-in option for guest waitlist signups | BISN sells this as an add-on; strong free GDPR story if we ship it |
| 12 | v1.3.2 | Email preview + send-test-email button in settings; waitlist email log screen | Cheap win to ship before any visual email-builder work - do not jump straight to WYSIWYG (see month 6 note) |
| 13 | v1.3.3 | My Account integration: My Account -> My Wishlists & Waitlists dashboard tab | Currently zero `woocommerce_account_*` hooks in the codebase - cheap and visible, should not have waited this long in the original draft |

## Month 4 (v1.3.4, v1.4.0 - v1.4.3) - demand intelligence and admin tools

Goal: give merchants a reason to keep the plugin active beyond initial setup - visible admin value, honestly scoped (no revenue-math promises until the ranking UI it hangs off of actually exists).

| Week | Version | Focus | Why |
|---|---|---|---|
| 14 | v1.3.4 | Admin dashboard widget: ranked "Top Most Wished" and "Highest Demand Waitlist" products | Admin screens today can filter by product but don't rank by popularity - this is genuinely missing, not just under-surfaced |
| 15 | v1.4.0 | Per-product wishlist/waitlist count column on the WooCommerce Products list screen; manual "Send Re-Stock Alert" trigger per subscriber | Cheap, visible, sits next to data merchants already look at |
| 16 | v1.4.1 | Waitlist CSV import (export already ships); low-stock admin alert (threshold-based notification email) | Import is an add-on in XootiX - free parity win; low-stock alert reuses the restock cron infra from Cron.php |
| 17 | v1.4.2 | REST API endpoints: read wishlist/waitlist, join/leave | TI and BISN both have this free; we have none today |
| 18 | v1.4.3 | Conversion attribution (wishlisted -> purchased, notified -> purchased) and revenue-potential dashboard number | BISN sells this, nobody offers it free - now safe to build since week 14's ranking UI gives it somewhere to live |

## Month 5 (v1.4.4, v1.5.0 - v1.5.2) - page builder and theme compatibility

Goal: visual-builder parity. Sequenced after month 2's block work so Elementor widgets reuse the same render callbacks instead of duplicating them.

| Week | Version | Focus | Why |
|---|---|---|---|
| 19 | v1.4.4 | Elementor widget: Wishlist Button | Reuses the block render callback from week 7; zero Elementor code exists today |
| 20 | v1.5.0 | Elementor widgets: Waitlist Form, Wishlist Counter Icon | Completes the 3-widget set without cramming it into one week like the original draft did |
| 21 | v1.5.1 | Theme compatibility shield: CSS reset options, override hooks for popular WooCommerce themes; compatibility hooks for AJAX archive-page filters | No explicit CSS-reset system exists today; low urgency vs. months 1-4 but needed before wider distribution |
| 22 | v1.5.2 | Mobile UX: touch-optimized floating action bar on product detail pages | New surface for mobile conversion, not previously scoped |

## Month 6 (v1.5.3, v1.6.0 - v1.6.2) - performance, localization, growth

Goal: scale-readiness and review/retention mechanics. Ends with a decision checkpoint, not a shipped Pro tier.

| Week | Version | Focus | Why |
|---|---|---|---|
| 23 | v1.5.3 | Cache-plugin compatibility verification: WP Rocket, LiteSpeed, Redis/Memcached object cache | DB indexing is already solid (composite + unique keys shipped in v1.0.0) - the real remaining risk is cache-layer interaction, not schema work |
| 24 | v1.6.0 | RTL layout stylesheet support; re-verify translation readiness (.pot regen) | POT file already gets regenerated per release; RTL stylesheet is the missing piece |
| 25 | v1.6.1 | Price-drop badge on wishlist items; on-sale nudge email to wishlist holders | Paired because both key off the same price-change detection; per competitor analysis this is a premium-tier differentiator nobody offers free, so it belongs late, not month 2 |
| 26 | v1.6.2 | Developer hook documentation page; 6-month retrospective and Pro-tier scoping decision | Hooks (`wpwing_wl_wishlist_item_added`, `wpwing_wl_product_stock_changed`, etc.) already exist in code - this week documents and fills gaps, it doesn't build from zero. Pro tier itself is a business decision to make here, not a feature to ship on a deadline |

## Pro tier candidates (do not give away later - decide before building)

Benchmarks: MoreConvert $29/yr, Woo Waitlist $49/yr, ProPluginsLab one-time add-ons.

- Multiple named wishlists per user, public/private/shared links
- Email marketing integrations: Mailchimp, Klaviyo, ActiveCampaign, Brevo
- SMS / WhatsApp restock notifications (Twilio)
- Automated wishlist reminder campaigns ("still thinking about it?", price drop, low stock)
- Advanced analytics with segmentation and revenue reports
- Quote requests / estimate from wishlist (B2B angle, YITH premium)
- Priority support

Recommended model: freemium single Pro plugin (MoreConvert style) rather than per-add-on (ProPluginsLab style) - simpler to maintain and price. Keep the free tier at or above TI/BISN free feature parity so reviews stay strong. Week 26 above is where this gets decided, not implemented.

## Explicit non-goals for now

- Multi-vendor marketplace support (Dokan/WCFM) - large surface, small overlap with target users
- Stock management features (Notifima direction) - stay a customer-intent plugin, not an inventory plugin
- Building our own email sending infrastructure - stay on wp_mail + hooks for ESPs
