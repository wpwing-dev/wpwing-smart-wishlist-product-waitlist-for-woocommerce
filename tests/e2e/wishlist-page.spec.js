const { test, expect } = require('@playwright/test');
const { PRODUCTS, productUrl } = require('./helpers');

const WISHLIST_PAGE_URL = '/my-wishlist/';

test.describe('Wishlist account page ([wpwing_wishlist])', () => {
  test('shows the empty state with nothing saved', async ({ page }) => {
    await page.goto(WISHLIST_PAGE_URL);
    await expect(page.locator('.wpwing-wishlist-empty')).toBeVisible();
    await expect(page.locator('.wpwing-wishlist-table')).toHaveCount(0);
  });

  test('lists a saved product and removes it via the row action', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.wishlist));
    await page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)').click();
    await expect(page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)')).toHaveAttribute('aria-pressed', 'true');

    await page.goto(WISHLIST_PAGE_URL);
    const row = page.locator('.wpwing-wishlist-row');
    await expect(row).toHaveCount(1);
    await expect(row).toContainText('E2E Wishlist Product');
    await expect(row.locator('.wpwing-wishlist-add-to-cart')).toBeVisible();

    await row.locator('.wpwing-wishlist-toggle').click();
    await expect(page.locator('.wpwing-wishlist-row')).toHaveCount(0);
    await expect(page.locator('.wpwing-wishlist-empty')).toBeVisible();
  });

  test('shows the summed total of all saved items', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.wishlist));
    await page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)').click();
    await expect(page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)')).toHaveAttribute('aria-pressed', 'true');

    await page.goto(productUrl(PRODUCTS.oosSimple));
    await page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)').click();
    await expect(page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)')).toHaveAttribute('aria-pressed', 'true');

    await page.goto(WISHLIST_PAGE_URL);
    await expect(page.locator('.wpwing-wishlist-row')).toHaveCount(2);
    // 19.99 (wishlist fixture) + 29.99 (oos fixture) = 49.98.
    await expect(page.locator('.wpwing-wishlist-total')).toContainText('49.98');

    // Clean up.
    for (const row of await page.locator('.wpwing-wishlist-row').all()) {
      await row.locator('.wpwing-wishlist-toggle').click();
    }
    await expect(page.locator('.wpwing-wishlist-empty')).toBeVisible();
  });

  test('shows social share links and copies the wishlist link', async ({ page, context }) => {
    await page.goto(productUrl(PRODUCTS.wishlist));
    await page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)').click();
    await expect(page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)')).toHaveAttribute('aria-pressed', 'true');

    await page.goto(WISHLIST_PAGE_URL);
    const shareUrl = page.url();
    const encodedUrl = encodeURIComponent(shareUrl);

    await expect(page.locator('.wpwing-wishlist-share-whatsapp')).toHaveAttribute('href', new RegExp(`^https://wa\\.me/\\?text=.*${encodedUrl}`));
    await expect(page.locator('.wpwing-wishlist-share-x')).toHaveAttribute('href', new RegExp(`^https://twitter\\.com/intent/tweet\\?url=${encodedUrl}`));
    await expect(page.locator('.wpwing-wishlist-share-facebook')).toHaveAttribute('href', `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`);
    await expect(page.locator('.wpwing-wishlist-share-email')).toHaveAttribute('href', new RegExp(`^mailto:\\?subject=.*body=.*${encodedUrl}`));

    await context.grantPermissions(['clipboard-read', 'clipboard-write']);
    await page.locator('.wpwing-wishlist-share-copy').click();
    await expect(page.locator('.wpwing-wishlist-share-copy')).toHaveText('Copied!');
    await expect(page.locator('.wpwing-wishlist-share-copy')).toHaveText('Copy link');
    const clipboardText = await page.evaluate(() => navigator.clipboard.readText());
    expect(clipboardText).toBe(shareUrl);

    // Clean up.
    await page.locator('.wpwing-wishlist-row .wpwing-wishlist-toggle').click();
    await expect(page.locator('.wpwing-wishlist-empty')).toBeVisible();
  });

  test('auto-removes an item once it is added to the cart from the wishlist row', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.wishlist));
    await page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)').click();
    await expect(page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)')).toHaveAttribute('aria-pressed', 'true');

    await page.goto(WISHLIST_PAGE_URL);
    const row = page.locator('.wpwing-wishlist-row');
    await expect(row).toHaveCount(1);

    // The row's "Add to cart" link is a normal (non-AJAX) link, so clicking it
    // navigates away and WooCommerce redirects back here once the item is added.
    await row.locator('.wpwing-wishlist-add-to-cart').click();
    await expect(page).toHaveURL(new RegExp(WISHLIST_PAGE_URL.replace(/\//g, '\\/')));
    await expect(page.locator('.wpwing-wishlist-row')).toHaveCount(0);
    await expect(page.locator('.wpwing-wishlist-empty')).toBeVisible();
  });

  test('auto-removes an item once it is added to the cart from a shop loop card', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.wishlist));
    await page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)').click();
    await expect(page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)')).toHaveAttribute('aria-pressed', 'true');

    await page.goto('/product-category/e2e-fixtures/');
    const card = page.locator('li.product', { has: page.locator(`a[href*="/${PRODUCTS.wishlist}/"]`) });
    const loopButton = card.locator('.wpwing-wishlist-toggle');
    await expect(loopButton).toHaveAttribute('aria-pressed', 'true');

    // The loop's "Add to cart" button uses WooCommerce's AJAX add-to-cart flow,
    // so the wishlist button here must flip back without a page reload.
    await card.locator('a.add_to_cart_button').click();
    await expect(loopButton).toHaveAttribute('aria-pressed', 'false');

    await page.goto(WISHLIST_PAGE_URL);
    await expect(page.locator('.wpwing-wishlist-empty')).toBeVisible();
  });

  test('displays stock status column for each item', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.wishlist));
    await page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)').click();
    await expect(page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)')).toHaveAttribute('aria-pressed', 'true');

    await page.goto(productUrl(PRODUCTS.oosSimple));
    await page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)').click();
    await expect(page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)')).toHaveAttribute('aria-pressed', 'true');

    await page.goto(WISHLIST_PAGE_URL);
    await expect(page.locator('.wpwing-wishlist-row')).toHaveCount(2);

    const inStockRow = page.locator('.wpwing-wishlist-row', { has: page.getByText('E2E Wishlist Product') });
    await expect(inStockRow.locator('.wpwing-stock-status.wpwing-in-stock')).toHaveText('In Stock');

    const oosRow = page.locator('.wpwing-wishlist-row', { has: page.getByText('E2E Out of Stock Simple') });
    await expect(oosRow.locator('.wpwing-stock-status.wpwing-out-of-stock')).toHaveText('Out of Stock');

    // Clean up.
    for (const row of await page.locator('.wpwing-wishlist-row').all()) {
      await row.locator('.wpwing-wishlist-toggle').click();
    }
    await expect(page.locator('.wpwing-wishlist-empty')).toBeVisible();
  });

  test('updates quantity and recalculates table total dynamically', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.wishlist));
    await page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)').click();
    await expect(page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)')).toHaveAttribute('aria-pressed', 'true');

    await page.goto(WISHLIST_PAGE_URL);
    const row = page.locator('.wpwing-wishlist-row');
    await expect(row).toHaveCount(1);

    const qtyInput = row.locator('.wpwing-wishlist-item-qty');
    await expect(qtyInput).toHaveValue('1');
    await expect(page.locator('.wpwing-wishlist-total')).toContainText('19.99');

    // Change quantity to 3 (19.99 * 3 = 59.97).
    await qtyInput.fill('3');
    await qtyInput.dispatchEvent('change');
    await expect(page.locator('.wpwing-wishlist-total')).toContainText('59.97');

    // "Add to cart" link has updated quantity param.
    await expect(row.locator('.wpwing-wishlist-add-to-cart')).toHaveAttribute('href', /quantity=3/);

    // Clean up.
    await row.locator('.wpwing-wishlist-toggle').click();
    await expect(page.locator('.wpwing-wishlist-empty')).toBeVisible();
  });

  test('bulk actions select all and clear wishlist', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.wishlist));
    await page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)').click();
    await expect(page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)')).toHaveAttribute('aria-pressed', 'true');

    await page.goto(productUrl(PRODUCTS.oosSimple));
    await page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)').click();
    await expect(page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)')).toHaveAttribute('aria-pressed', 'true');

    await page.goto(WISHLIST_PAGE_URL);
    await expect(page.locator('.wpwing-wishlist-row')).toHaveCount(2);

    // Click select all checkbox.
    const selectAll = page.locator('.wpwing-wishlist-select-all');
    await selectAll.check();
    for (const cb of await page.locator('.wpwing-wishlist-select-item').all()) {
      await expect(cb).toBeChecked();
    }

    // Clear wishlist via bulk action button.
    page.once('dialog', (dialog) => dialog.accept());
    await page.locator('.wpwing-wishlist-clear-all').click();

    await expect(page.locator('.wpwing-wishlist-row')).toHaveCount(0);
    await expect(page.locator('.wpwing-wishlist-empty')).toBeVisible();
    await expect(page.locator('.wpwing-wishlist-count').first()).toHaveText('0');
  });

  test('bulk actions add all to cart redirects to cart', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.wishlist));
    await page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)').click();
    await expect(page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)')).toHaveAttribute('aria-pressed', 'true');

    await page.goto(WISHLIST_PAGE_URL);
    await expect(page.locator('.wpwing-wishlist-row')).toHaveCount(1);

    await page.locator('.wpwing-wishlist-bulk-add-to-cart').click();
    await expect(page).toHaveURL(/\/cart\//);
  });
});
