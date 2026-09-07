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
});
