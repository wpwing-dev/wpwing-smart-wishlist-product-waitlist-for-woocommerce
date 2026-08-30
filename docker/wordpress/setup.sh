#!/bin/sh
set -e

WP_PATH=/var/www/html

echo "Waiting for wp-config.php..."
until [ -f "$WP_PATH/wp-config.php" ]; do
    sleep 2
done

if wp --path="$WP_PATH" core is-installed --allow-root 2>/dev/null; then
    echo "Already installed, skipping install."
else
    echo "Installing WordPress..."
    wp --path="$WP_PATH" core install \
        --url="https://smart-wishlist.local" \
        --title="Smart Wishlist Dev" \
        --admin_user=admin \
        --admin_password=password \
        --admin_email=dev@example.com \
        --skip-email \
        --allow-root

    echo "Installing WooCommerce..."
    wp --path="$WP_PATH" plugin install woocommerce --activate --allow-root
    wp --path="$WP_PATH" option update woocommerce_coming_soon no --allow-root

    echo "Importing WooCommerce sample data..."
    wp --path="$WP_PATH" plugin install wordpress-importer --activate --allow-root
    wp --path="$WP_PATH" import \
        "$WP_PATH/wp-content/plugins/woocommerce/sample-data/sample_products.xml" \
        --authors=create \
        --allow-root

    echo "Activating plugin..."
    wp --path="$WP_PATH" plugin activate wpwing-smart-wishlist-product-waitlist-for-woocommerce --allow-root
fi

echo "Ensuring Storefront is the only theme..."
if ! wp --path="$WP_PATH" theme is-installed storefront --allow-root; then
    wp --path="$WP_PATH" theme install storefront --allow-root
fi
wp --path="$WP_PATH" theme activate storefront --allow-root
# deletes all inactive themes, leaving only Storefront
wp --path="$WP_PATH" theme delete --all --allow-root

echo "Removing bundled plugins..."
wp --path="$WP_PATH" plugin delete akismet hello --allow-root || true

echo "Setting pretty permalinks (required for /product/slug/ URLs)..."
wp --path="$WP_PATH" rewrite structure '/%postname%/' --hard --allow-root
wp --path="$WP_PATH" rewrite flush --hard --allow-root
# wp-cli runs outside the web server, so it can't detect Apache and leaves
# .htaccess empty (no RewriteEngine block) — write the standard WordPress
# root .htaccess directly so pretty permalinks actually resolve.
cat > "$WP_PATH/.htaccess" <<'HTACCESS'
# BEGIN WordPress
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
# END WordPress
HTACCESS

echo "Enabling My Account registration (needed for the guest-merge-on-registration e2e test)..."
wp --path="$WP_PATH" option update woocommerce_enable_myaccount_registration yes --allow-root

echo "Seeding E2E fixture products..."
wp --path="$WP_PATH" eval-file /docker/wordpress/seed-e2e-fixtures.php --allow-root

echo "Ensuring the E2E customer test account exists..."
if ! wp --path="$WP_PATH" user get e2e-customer --field=ID --allow-root >/dev/null 2>&1; then
    wp --path="$WP_PATH" user create e2e-customer e2e-customer@example.test \
        --role=customer \
        --user_pass='E2ePassword123!' \
        --allow-root
fi

echo "Ensuring a primary navigation menu exists (needed for the wishlist nav badge)..."
MENU_COUNT=$(wp --path="$WP_PATH" menu list --format=count --allow-root)
if [ "$MENU_COUNT" -eq 0 ]; then
    wp --path="$WP_PATH" menu create "E2E Primary Menu" --allow-root
    SHOP_PAGE_ID=$(wp --path="$WP_PATH" option get woocommerce_shop_page_id --allow-root)
    wp --path="$WP_PATH" menu item add-post "E2E Primary Menu" "$SHOP_PAGE_ID" --allow-root
    wp --path="$WP_PATH" menu location assign "E2E Primary Menu" primary --allow-root
fi

echo "Updating WordPress core, themes, and plugins to latest..."
wp --path="$WP_PATH" core update --allow-root
wp --path="$WP_PATH" core update-db --allow-root
wp --path="$WP_PATH" theme update --all --allow-root
# exclude the bind-mounted dev plugin so wp.org can never overwrite ./src
wp --path="$WP_PATH" plugin update --all \
    --exclude=wpwing-smart-wishlist-product-waitlist-for-woocommerce \
    --allow-root

echo "Done. Visit https://smart-wishlist.local/wp-admin (admin / password)"
