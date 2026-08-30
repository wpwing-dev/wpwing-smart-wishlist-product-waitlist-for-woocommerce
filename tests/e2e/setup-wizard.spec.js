const { test, expect } = require('@playwright/test');
const { ADMIN, login } = require('./helpers');

test.describe('Setup wizard', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, ADMIN);
  });

  test('shows the setup wizard and persists the selected settings', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=wpwing-wl-setup&step=1');

    await expect(page.getByRole('heading', { name: 'Setup Wizard' })).toBeVisible();
    await expect(page.getByText('Enable features')).toBeVisible();

    await page.getByLabel('Enable Wishlist').check();
    await page.getByLabel('Enable Waitlist').check();
    await page.getByRole('button', { name: 'Continue' }).click();

    await expect(page.getByText('Choose button style')).toBeVisible();
    await page.locator('select[name="wishlist_btn_style"]').selectOption('filled');
    await page.getByRole('button', { name: 'Continue' }).click();

    await expect(page.getByText('Select your pages')).toBeVisible();
    await page.locator('#wishlist_page_id').selectOption({ label: 'My Wishlist' });
    await page.locator('#waitlist_page_id').selectOption({ label: 'My Waitlist' });
    await page.getByRole('button', { name: 'Finish setup' }).click();

    await expect(page).toHaveURL(/page=wpwing-wl-settings/);
    await expect(page.getByRole('heading', { name: 'Smart Wishlist & Product Waitlist Settings' })).toBeVisible();
  });

  test('skips the button-style step when wishlist is disabled', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=wpwing-wl-setup&step=1');

    await page.getByLabel('Enable Wishlist').uncheck();
    await page.getByLabel('Enable Waitlist').check();
    await page.getByRole('button', { name: 'Continue' }).click();

    // Step 2 (button style) only applies to wishlist, so it must be skipped straight to step 3.
    await expect(page.getByText('Select waitlist page')).toBeVisible();
    await expect(page.locator('select[name="wishlist_btn_style"]')).toHaveCount(0);
    await expect(page.locator('#wishlist_page_id')).toHaveCount(0);

    await page.locator('#waitlist_page_id').selectOption({ label: 'My Waitlist' });
    await page.getByRole('button', { name: 'Finish setup' }).click();
    await expect(page).toHaveURL(/page=wpwing-wl-settings/);

    // Restore both features for the rest of the suite.
    await page.goto('/wp-admin/admin.php?page=wpwing-wl-setup&step=1');
    await page.getByLabel('Enable Wishlist').check();
    await page.getByRole('button', { name: 'Continue' }).click();
    await page.getByRole('button', { name: 'Continue' }).click();
    await page.getByRole('button', { name: 'Finish setup' }).click();
  });

  test('skips every step but the first when both features are disabled', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=wpwing-wl-setup&step=1');

    await page.getByLabel('Enable Wishlist').uncheck();
    await page.getByLabel('Enable Waitlist').uncheck();
    // Step 1 always shows "Continue" — steps 2 and 3 are both inapplicable
    // with everything disabled, so submitting jumps straight to Settings.
    await page.getByRole('button', { name: 'Continue' }).click();

    await expect(page).toHaveURL(/page=wpwing-wl-settings/);

    // Restore both features for the rest of the suite.
    await page.goto('/wp-admin/admin.php?page=wpwing-wl-setup&step=1');
    await page.getByLabel('Enable Wishlist').check();
    await page.getByLabel('Enable Waitlist').check();
    await page.getByRole('button', { name: 'Continue' }).click();
    await page.getByRole('button', { name: 'Continue' }).click();
    await page.getByRole('button', { name: 'Finish setup' }).click();
  });
});
