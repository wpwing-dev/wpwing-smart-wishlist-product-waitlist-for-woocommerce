# Product Roadmap

Derived from the gap analysis in [competitor-analysis.md](competitor-analysis.md). Ordering favors items that are cheap for us, visible to merchants, and free in competitors' free tiers (table stakes) before premium-tier differentiators.

## v1.0.x - post-launch patch window

Bug fixes only, driven by support forum reports. No features. Goals:

- Respond to every .org support topic within 48 hours (reviews and response time drive early ranking)
- Watch for theme conflicts around the button render hooks and form CSS
- Watch email deliverability reports (SPF/DKIM misconfigured hosts blaming the plugin)

## v1.1 - close the free-tier table-stakes gaps

Wishlist:

- Button placement setting: before/after add-to-cart, on shop/archive loop cards (TI and YITH free have this; we only render on single product pages)
- Auto-remove item from wishlist after add-to-cart (TI free)
- Wishlist item count badge + optional menu/header icon with counter (TI free)
- Social sharing of the wishlist page: link copy, WhatsApp, X, Facebook, email (TI and WPC free)

Waitlist:

- Optional reCAPTCHA v3 / Cloudflare Turnstile alongside honeypot (BISN free)
- Waitlist email log screen (XootiX free)
- Backorder-allowed products: setting to show waitlist on backorder too (BISN free)
- A second and third form design preset (BISN ships 6)

Platform:

- Gutenberg blocks for `[wpwing_wishlist]` and `[wpwing_waitlist]` (shortcode-only looks dated in 2026)
- WPML / Polylang config file (`wpml-config.xml`) and tested compatibility statement in readme

## v1.2 - trust and deliverability

- Double opt-in option for guest waitlist signups (BISN sells this as an add-on; strong GDPR story if free)
- Per-product waitlist counts column on the WooCommerce Products list screen
- Waitlist CSV import (export exists; import is an add-on in XootiX)
- Wishlist CSV export/import
- REST API endpoints (read wishlist/waitlist, join/leave) - TI and BISN have this free
- Email preview + send-test-email button in settings

## v1.3 - analytics and demand intelligence

- Conversion attribution: wishlisted -> purchased and notified -> purchased (revenue per restock email); BISN sells this, nobody offers it free
- Dashboard widget: waitlist signups this week, top demanded out-of-stock products
- Price-drop notification for wishlisted items (nobody on .org does this free; YITH premium territory)

## Pro tier candidates (do not give away later - decide before building)

Benchmarks: MoreConvert $29/yr, Woo Waitlist $49/yr, ProPluginsLab one-time add-ons.

- Multiple named wishlists per user, public/private/shared links
- Email marketing integrations: Mailchimp, Klaviyo, ActiveCampaign, Brevo
- SMS / WhatsApp restock notifications (Twilio)
- Automated wishlist reminder campaigns ("still thinking about it?", price drop, low stock)
- Advanced analytics with segmentation and revenue reports
- Quote requests / estimate from wishlist (B2B angle, YITH premium)
- Priority support

Recommended model: freemium single Pro plugin (MoreConvert style) rather than per-add-on (ProPluginsLab style) - simpler to maintain and price. Keep the free tier at or above TI/BISN free feature parity so reviews stay strong.

## Explicit non-goals for now

- Multi-vendor marketplace support (Dokan/WCFM) - large surface, small overlap with target users
- Stock management features (Notifima direction) - stay a customer-intent plugin, not an inventory plugin
- Building our own email sending infrastructure - stay on wp_mail + hooks for ESPs
