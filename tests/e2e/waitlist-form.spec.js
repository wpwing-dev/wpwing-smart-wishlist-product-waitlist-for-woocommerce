const { test, expect } = require('@playwright/test');
const { CUSTOMER, PRODUCTS, login, productUrl } = require('./helpers');

const WAITLIST_PAGE_URL = '/my-waitlist/';

test.describe('Waitlist signup form', () => {
  test('shows the join form on an out-of-stock simple product', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.oosSimple));
    const form = page.locator('.wpwing-waitlist-form');
    await expect(form).toBeVisible();
    await expect(form.locator('.wpwing-waitlist-fields')).toBeVisible();
    await expect(form.locator('.wpwing-waitlist-joined')).toBeHidden();
  });

  test('does not render on an in-stock product', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.wishlist));
    await expect(page.locator('.wpwing-waitlist-form')).toHaveCount(0);
  });

  test('guest can join, state persists across reload, then leave', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.oosSimple));
    const form = page.locator('.wpwing-waitlist-form');

    await form.locator('#wpwing-waitlist-email').fill('guest-waitlist@example.test');
    await form.locator('.wpwing-waitlist-submit').click();

    await expect(form.locator('.wpwing-waitlist-joined')).toBeVisible();
    await expect(form.locator('.wpwing-waitlist-fields')).toBeHidden();

    await page.reload();
    const formAfterReload = page.locator('.wpwing-waitlist-form');
    await expect(formAfterReload.locator('.wpwing-waitlist-joined')).toBeVisible();
    await expect(formAfterReload.locator('.wpwing-waitlist-fields')).toBeHidden();

    await formAfterReload.locator('.wpwing-waitlist-leave').click();
    await expect(formAfterReload.locator('.wpwing-waitlist-fields')).toBeVisible();
    await expect(formAfterReload.locator('.wpwing-waitlist-joined')).toBeHidden();

    await page.reload();
    await expect(page.locator('.wpwing-waitlist-form .wpwing-waitlist-fields')).toBeVisible();
  });

  test('logged-in customer sees their account email pre-filled and can join', async ({ page }) => {
    await login(page, CUSTOMER);
    await page.goto(productUrl(PRODUCTS.oosSimple));
    const form = page.locator('.wpwing-waitlist-form');

    await expect(form.locator('#wpwing-waitlist-email')).toHaveValue('e2e-customer@example.test');

    await form.locator('.wpwing-waitlist-submit').click();
    await expect(form.locator('.wpwing-waitlist-joined')).toBeVisible();

    // Clean up.
    await form.locator('.wpwing-waitlist-leave').click();
    await expect(form.locator('.wpwing-waitlist-fields')).toBeVisible();
  });
});

test.describe('Waitlist account page ([wpwing_waitlist])', () => {
  test('shows the empty state with nothing joined', async ({ page }) => {
    await page.goto(WAITLIST_PAGE_URL);
    await expect(page.locator('.wpwing-waitlist-empty')).toBeVisible();
    await expect(page.locator('.wpwing-waitlist-table')).toHaveCount(0);
  });

  test('lists a joined product and removes it via the row action', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.oosSimple));
    await page.locator('.wpwing-waitlist-form #wpwing-waitlist-email').fill('guest-waitlist-page@example.test');
    await page.locator('.wpwing-waitlist-form .wpwing-waitlist-submit').click();
    await expect(page.locator('.wpwing-waitlist-form .wpwing-waitlist-joined')).toBeVisible();

    await page.goto(WAITLIST_PAGE_URL);
    const row = page.locator('.wpwing-waitlist-row');
    await expect(row).toHaveCount(1);
    await expect(row).toContainText('E2E Out of Stock Simple');

    await row.locator('.wpwing-waitlist-view-leave').click();
    await expect(page.locator('.wpwing-waitlist-row')).toHaveCount(0);
    await expect(page.locator('.wpwing-waitlist-empty')).toBeVisible();
  });
});
