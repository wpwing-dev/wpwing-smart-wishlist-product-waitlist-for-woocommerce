=== WPWing Wishlist and Waitlist for WooCommerce ===
Contributors: wpwing
Tags: wishlist, waitlist, back-in-stock, woocommerce, save-for-later
Requires at least: 6.4
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 8.1
Requires Plugins: woocommerce
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let shoppers save products to a wishlist and get back-in-stock notifications — WooCommerce wishlist and waitlist plugin, guest-friendly, zero setup.

== Description ==

Most stores install two separate plugins to offer a wishlist and a back-in-stock waitlist. **WPWing Wishlist and Waitlist for WooCommerce** does both in a single install — sharing one admin menu, one asset file, and one pair of database tables.

Activate it, and both features are live immediately. No settings page to configure. No shortcodes required (unless you want a dedicated wishlist page).

Requires WooCommerce 9.0 or higher.

= Wishlist =

Shoppers can save any product with a single click. The button appears on every product page and toggles between "Add to wishlist" and "Remove from wishlist" via AJAX — no page reload.

* Works for logged-in customers and guests (cookie-based, no account required)
* Guest items carry over automatically when the shopper creates an account or logs in — no duplicates, no lost saves
* `[wpwing_wishlist]` shortcode renders the shopper's saved products anywhere on your site, with an inline Remove button
* Wishlist data stored in a dedicated database table — not post meta

= Back-in-stock Waitlist =

When a product or variation goes out of stock, an email capture form appears on the product page automatically. When stock is restored, subscribers receive a notification email and can return to buy.

* Variation-aware: the form shows and hides in real time as a shopper selects different options — only out-of-stock selections trigger the form
* Notifications dispatched via Action Scheduler (bundled with WooCommerce) so a large restock batch never slows down your admin
* Every notification email includes a one-click unsubscribe link
* Honeypot field blocks automated spam submissions without captcha friction

= Admin =

* **WPWing → Waitlist** — paginated table of all signups, filterable by status, with one-click CSV export
* **WPWing → Wishlist** — top 50 most-saved products ranked by wishlist count, so you can spot demand before it shows in sales

= For Developers =

Every meaningful step fires an action or filter hook so you can customise behaviour without modifying plugin files:

`wpwing_wl_restock_email_message` (filter the notification email body), `wpwing_wl_restock_queue_entries` (filter who gets notified), `wpwing_wl_before_send_restock_email`, `wpwing_wl_after_send_restock_email`, `wpwing_wl_before_process_restock_queue`, `wpwing_wl_after_process_restock_queue`, `wpwing_wl_product_stock_changed`, `wpwing_wl_wishlist_item_added`, `wpwing_wl_wishlist_item_removed`.

== Installation ==

1. Go to **Plugins → Add New → Upload Plugin**, upload the ZIP, and click **Activate**.
2. Both the wishlist toggle button and the waitlist form are now live on your product pages — no configuration needed.
3. Optional: create a page, add the `[wpwing_wishlist]` shortcode, and publish it to give shoppers a dedicated "My Wishlist" page.

== Frequently Asked Questions ==

= Does the plugin work with variable products? =

Yes. The waitlist form is variation-aware — it appears and disappears in real time as a shopper selects different options, and only fires for variations that are actually out of stock. The wishlist button works for both simple and variable products.

= Do shoppers need an account to use the wishlist or waitlist? =

No. Guests can save products to a wishlist (stored in a cookie) and join waitlists using only an email address. If a guest later logs in or registers, their wishlist items merge into their account automatically.

= Is there anything to configure after activation? =

No. Both features work immediately after activation using WooCommerce's own stock status and the email address from the waitlist form. A settings page is available under **WPWing → Settings** for merchants who want to disable a feature, customise the restock notification email, change the sender / Reply-To address, or adjust how long guest wishlists are retained.

= How does the restock email get sent? =

When WooCommerce records a stock change that brings a product back into stock, the plugin schedules a job via Action Scheduler (bundled with WooCommerce — no separate install needed). Action Scheduler runs the job in the background, so restocking 200 products at once does not slow down your admin screen.

= Can I customise the notification email? =

Yes. Use the `wpwing_wl_restock_email_message` filter to replace the default plain-text message with HTML, a custom template, or a call to a transactional email service.

= What happens to the data if I uninstall the plugin? =

Deleting the plugin via **Plugins → Delete** drops both custom database tables and removes all plugin options. Deactivating without deleting leaves all data in place.

== Screenshots ==

1. Wishlist toggle button on a single product page.
2. `[wpwing_wishlist]` shortcode rendering the customer's saved products.
3. Back-in-stock waitlist form on an out-of-stock product page.
4. Variation-aware waitlist: form appears only for the out-of-stock variation.
5. Admin Waitlist — entries table with CSV export.
6. Admin Wishlist — most-wishlisted products ranked by count.

== Changelog ==

= 1.0.0 =
* Initial release — full wishlist and back-in-stock waitlist system for WooCommerce.
