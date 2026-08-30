const { test, expect } = require('@playwright/test');
const { CUSTOMER, PRODUCTS, login, productUrl } = require('./helpers');

test.describe('Guest wishlist persistence and account merge', () => {
  test('guest wishlist survives a reload via the guest cookie', async ({ page, context }) => {
    await page.goto(productUrl(PRODUCTS.wishlist));
    await page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)').click();
    await expect(page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)')).toHaveAttribute('aria-pressed', 'true');

    const cookies = await context.cookies();
    expect(cookies.some((c) => c.name === 'wpwing_wl_guest')).toBe(true);

    await page.reload();
    await expect(page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)')).toHaveAttribute('aria-pressed', 'true');

    // Clean up.
    await page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)').click();
  });

  test('guest wishlist merges into the account on login', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.wishlist));
    await page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)').click();
    await expect(page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)')).toHaveAttribute('aria-pressed', 'true');

    await login(page, CUSTOMER);

    await page.goto(productUrl(PRODUCTS.wishlist));
    await expect(page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)')).toHaveAttribute('aria-pressed', 'true');

    // Clean up.
    await page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)').click();
    await expect(page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)')).toHaveAttribute('aria-pressed', 'false');
  });

  test('guest wishlist merges into a brand new account on registration', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.wishlist));
    await page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)').click();
    await expect(page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)')).toHaveAttribute('aria-pressed', 'true');

    await page.goto('/my-account/');
    const registerForm = page.locator('form.woocommerce-form-register, form.register');
    const email = `e2e-${Date.now()}@example.test`;
    await registerForm.locator('input[name="email"]').fill(email);
    const passwordField = registerForm.locator('input[name="password"]');
    if (await passwordField.count()) {
      await passwordField.fill('E2ePassword123!');
    }
    await registerForm.getByRole('button', { name: 'Register' }).click();

    await expect(page).toHaveURL(/my-account/);

    await page.goto(productUrl(PRODUCTS.wishlist));
    await expect(page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)')).toHaveAttribute('aria-pressed', 'true');

    // Clean up (new accounts are cheap to leave behind, but the wishlist row isn't ours to keep).
    await page.locator('.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)').click();
  });
});
