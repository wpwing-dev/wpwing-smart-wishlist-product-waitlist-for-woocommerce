const { test, expect } = require('@playwright/test');
const { ADMIN, PRODUCTS, login, productUrl } = require('./helpers');

const ADMIN_WISHLIST_URL = '/wp-admin/admin.php?page=wpwing-wl-wishlist';
const SINGLE_BUTTON = '.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)';

test.describe('Admin Wishlist page', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, ADMIN);
  });

  test('lists an entry, filters by product, and deletes it', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.wishlist));
    await page.locator(SINGLE_BUTTON).click();
    await expect(page.locator(SINGLE_BUTTON)).toHaveAttribute('aria-pressed', 'true');

    await page.goto(ADMIN_WISHLIST_URL);
    const row = page.locator('tr', { has: page.getByText('E2E Wishlist Product') });
    await expect(row).toHaveCount(1);

    // Filter down to just this product. The option label includes "(#<id>)",
    // so read its value rather than guessing the exact label text.
    const productOption = page.locator('select[name="filter_product"] option', { hasText: 'E2E Wishlist Product' });
    await page.locator('select[name="filter_product"]').selectOption(await productOption.getAttribute('value'));
    await page.getByRole('button', { name: 'Filter' }).click();
    await expect(page.locator('tr', { has: page.getByText('E2E Wishlist Product') })).toHaveCount(1);

    // The delete link lives in .row-actions, which WP admin CSS only reveals
    // on row hover — read its href and navigate directly rather than clicking,
    // which sidesteps that hover-reveal quirk entirely.
    const deleteHref = await page
      .locator('tr', { has: page.getByText('E2E Wishlist Product') })
      .locator('a.submitdelete')
      .getAttribute('href');
    await page.goto(deleteHref);

    // Not asserting on the URL's ?deleted=1 param: WP admin strips it from the
    // address bar via history.replaceState() shortly after load (so a refresh
    // doesn't redisplay the notice), so it's not a reliable signal to poll for.
    await expect(page.locator('tr', { has: page.getByText('E2E Wishlist Product') })).toHaveCount(0);
  });

  test('bulk-deletes selected entries', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.wishlist));
    await page.locator(SINGLE_BUTTON).click();
    await expect(page.locator(SINGLE_BUTTON)).toHaveAttribute('aria-pressed', 'true');

    await page.goto(productUrl(PRODUCTS.oosSimple));
    await page.locator(SINGLE_BUTTON).click();
    await expect(page.locator(SINGLE_BUTTON)).toHaveAttribute('aria-pressed', 'true');

    await page.goto(ADMIN_WISHLIST_URL);
    const rowA = page.locator('tr', { has: page.getByText('E2E Wishlist Product') });
    const rowB = page.locator('tr', { has: page.getByText('E2E Out of Stock Simple') });
    await rowA.locator('.wpwing-entry-cb').check();
    await rowB.locator('.wpwing-entry-cb').check();

    await page.locator('#bulk-action-selector-top').selectOption('delete');
    page.once('dialog', (dialog) => dialog.accept());
    await page.locator('.tablenav.top input.button.action').click();

    // Not asserting on ?deleted=2: see the comment in the single-delete test above.
    await expect(page.locator('tr', { has: page.getByText('E2E Wishlist Product') })).toHaveCount(0);
    await expect(page.locator('tr', { has: page.getByText('E2E Out of Stock Simple') })).toHaveCount(0);
  });

  test('exports a CSV file', async ({ page }) => {
    await page.goto(ADMIN_WISHLIST_URL);
    const [download] = await Promise.all([
      page.waitForEvent('download'),
      page.getByRole('link', { name: 'Export CSV' }).click(),
    ]);
    expect(download.suggestedFilename()).toMatch(/^wpwing-wishlist-.*\.csv$/);
  });
});
