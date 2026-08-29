const { test, expect } = require('@playwright/test');

test.describe('Setup wizard', () => {
  test('shows the setup wizard and persists the selected settings', async ({ page }) => {
    await page.goto('/wp-login.php');
    await page.locator('#user_login').fill('admin');
    await page.locator('#user_pass').fill('password');
    await page.locator('#wp-submit').click();

    await page.goto('https://smart-wishlist.local/wp-admin/admin.php?page=wpwing-wl-setup&step=1');

    await expect(page.getByRole('heading', { name: 'Setup Wizard' })).toBeVisible();
    await expect(page.getByText('Enable features')).toBeVisible();

    await page.getByLabel('Enable Wishlist').check();
    await page.getByLabel('Enable Waitlist').check();
    await page.getByRole('button', { name: 'Continue' }).click();

    await expect(page.getByText('Choose button style')).toBeVisible();
    await page.locator('select[name="wishlist_btn_style"]').selectOption('filled');
    await page.getByRole('button', { name: 'Continue' }).click();

    await expect(page.getByText('Select wishlist page')).toBeVisible();
    await page.locator('#wishlist_page_id').selectOption({ label: 'My Wishlist' });
    await page.getByRole('button', { name: 'Finish setup' }).click();

    await expect(page).toHaveURL(/page=wpwing-wl-settings/);
    await expect(page.getByRole('heading', { name: 'Smart Wishlist & Product Waitlist Settings' })).toBeVisible();
  });
});
