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
});
