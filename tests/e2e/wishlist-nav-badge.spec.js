const { test, expect } = require('@playwright/test');
const { PRODUCTS, productUrl } = require('./helpers');

test.describe('Wishlist nav count badge', () => {
  test('updates live as items are added and removed, without a reload', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.wishlist));

    const badge = page.locator('.wpwing-wishlist-count').first();
    await expect(badge).toBeVisible();
    await expect(badge).toHaveText('0');

    await page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)').click();
    await expect(badge).toHaveText('1');

    await page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)').click();
    await expect(badge).toHaveText('0');
  });

  test('badge link points to the configured wishlist page', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.wishlist));
    const link = page.locator('.wpwing-wishlist-count-link').first();
    await expect(link).toHaveAttribute('href', /my-wishlist/);
  });
});
