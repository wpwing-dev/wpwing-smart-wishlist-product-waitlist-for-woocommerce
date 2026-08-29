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

echo "Updating WordPress core, themes, and plugins to latest..."
wp --path="$WP_PATH" core update --allow-root
wp --path="$WP_PATH" core update-db --allow-root
wp --path="$WP_PATH" theme update --all --allow-root
# exclude the bind-mounted dev plugin so wp.org can never overwrite ./src
wp --path="$WP_PATH" plugin update --all \
    --exclude=wpwing-smart-wishlist-product-waitlist-for-woocommerce \
    --allow-root

echo "Done. Visit https://smart-wishlist.local/wp-admin (admin / password)"
