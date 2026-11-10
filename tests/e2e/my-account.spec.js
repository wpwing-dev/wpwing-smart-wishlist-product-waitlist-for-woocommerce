const { test, expect } = require('@playwright/test');
const { ADMIN, CUSTOMER, login, setWishlistSetting } = require('./helpers');

test.describe('My Account integration', () => {
  test('My Account displays Wishlist & Waitlist tab and renders content', async ({ page }) => {
    await login(page, CUSTOMER);
    await page.goto('/my-account/');

    const tabLink = page.locator('.woocommerce-MyAccount-navigation a[href*="wpwing-wishlist"]');
    await expect(tabLink).toBeVisible();
    await expect(tabLink).toContainText('Wishlist & Waitlist');

    await tabLink.click();
    await expect(page).toHaveURL(/wpwing-wishlist/);
    await expect(page.locator('.wpwing-my-account-wrap')).toBeVisible();
    await expect(page.locator('.wpwing-my-account-wishlist h2')).toContainText('My Wishlist');
    await expect(page.locator('.wpwing-my-account-waitlist h2')).toContainText('My Waitlist');
  });

  test('My Account tab can be toggled off in settings', async ({ page }) => {
    await login(page, ADMIN);
    await setWishlistSetting(page, 'enable_my_account_tab', false);

    try {
      await login(page, CUSTOMER);
      await page.goto('/my-account/');
      const tabLink = page.locator('.woocommerce-MyAccount-navigation a[href*="wpwing-wishlist"]');
      await expect(tabLink).not.toBeAttached();
    } finally {
      await login(page, ADMIN);
      await setWishlistSetting(page, 'enable_my_account_tab', true);
    }
  });
});
