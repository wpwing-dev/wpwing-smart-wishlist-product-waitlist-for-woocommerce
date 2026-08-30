const { test, expect } = require('@playwright/test');
const { CUSTOMER, PRODUCTS, login, productUrl } = require('./helpers');

test.describe('Guest waitlist persistence and account merge', () => {
  test('guest join sets an unsubscribe-token cookie scoped to the product', async ({ page, context }) => {
    await page.goto(productUrl(PRODUCTS.oosSimple));
    await page.locator('.wpwing-waitlist-form #wpwing-waitlist-email').fill('guest-cookie@example.test');
    await page.locator('.wpwing-waitlist-form .wpwing-waitlist-submit').click();
    await expect(page.locator('.wpwing-waitlist-form .wpwing-waitlist-joined')).toBeVisible();

    const cookies = await context.cookies();
    expect(cookies.some((c) => c.name.startsWith('wpwing_wl_wj_'))).toBe(true);

    // Clean up.
    await page.locator('.wpwing-waitlist-form .wpwing-waitlist-leave').click();
  });

  test('guest waitlist entry merges into the account on login', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.oosSimple));
    await page.locator('.wpwing-waitlist-form #wpwing-waitlist-email').fill('guest-merge@example.test');
    await page.locator('.wpwing-waitlist-form .wpwing-waitlist-submit').click();
    await expect(page.locator('.wpwing-waitlist-form .wpwing-waitlist-joined')).toBeVisible();

    await login(page, CUSTOMER);

    await page.goto(productUrl(PRODUCTS.oosSimple));
    // The merged entry is now owned by the account (user_id, not a guest
    // token), so PHP renders the joined state server-side on first load.
    await expect(page.locator('.wpwing-waitlist-form .wpwing-waitlist-joined')).toBeVisible();

    // Clean up.
    await page.locator('.wpwing-waitlist-form .wpwing-waitlist-leave').click();
    await expect(page.locator('.wpwing-waitlist-form .wpwing-waitlist-fields')).toBeVisible();
  });
});
