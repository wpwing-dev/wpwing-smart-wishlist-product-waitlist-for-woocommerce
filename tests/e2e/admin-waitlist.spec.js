const { test, expect } = require('@playwright/test');
const { ADMIN, PRODUCTS, login, productUrl } = require('./helpers');

const ADMIN_WAITLIST_URL = '/wp-admin/admin.php?page=wpwing-wl-waitlist';

test.describe('Admin Waitlist page', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, ADMIN);
  });

  test('lists an entry, filters by status, and deletes it', async ({ page }) => {
    // A distinguishing email, not the product name: other specs in this run
    // also add oosSimple waitlist entries, so a product-name-only match would
    // hit more than one row and break the toHaveCount(1) assertions below.
    const email = 'admin-list@example.test';
    await page.goto(productUrl(PRODUCTS.oosSimple));
    await page.locator('.wpwing-waitlist-form #wpwing-waitlist-email').fill(email);
    await page.locator('.wpwing-waitlist-form .wpwing-waitlist-submit').click();
    await expect(page.locator('.wpwing-waitlist-form .wpwing-waitlist-joined')).toBeVisible();

    await page.goto(ADMIN_WAITLIST_URL);
    const row = page.locator('tr', { has: page.getByText(email) });
    await expect(row).toHaveCount(1);

    await page.locator('select[name="filter_status"]').selectOption('active');
    await page.getByRole('button', { name: 'Filter' }).click();
    await expect(page.locator('tr', { has: page.getByText(email) })).toHaveCount(1);

    // The delete link lives in .row-actions, which WP admin CSS only reveals
    // on row hover — read its href and navigate directly rather than clicking,
    // which sidesteps that hover-reveal quirk entirely.
    const deleteHref = await page
      .locator('tr', { has: page.getByText(email) })
      .locator('a.submitdelete')
      .getAttribute('href');
    await page.goto(deleteHref);

    // Not asserting on the URL's ?deleted=1 param: WP admin strips it from the
    // address bar via history.replaceState() shortly after load (so a refresh
    // doesn't redisplay the notice), so it's not a reliable signal to poll for.
    await expect(page.locator('tr', { has: page.getByText(email) })).toHaveCount(0);
  });

  test('bulk-deletes selected entries', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.oosSimple));
    await page.locator('.wpwing-waitlist-form #wpwing-waitlist-email').fill('bulk-a@example.test');
    await page.locator('.wpwing-waitlist-form .wpwing-waitlist-submit').click();
    await expect(page.locator('.wpwing-waitlist-form .wpwing-waitlist-joined')).toBeVisible();

    await page.goto(productUrl(PRODUCTS.variable));
    await page.locator('select[name="attribute_size"]').selectOption({ label: 'Large' });
    await page.locator('.wpwing-waitlist-form #wpwing-waitlist-email').fill('bulk-b@example.test');
    await page.locator('.wpwing-waitlist-form .wpwing-waitlist-submit').click();
    await expect(page.locator('.wpwing-waitlist-form .wpwing-waitlist-joined')).toBeVisible();

    await page.goto(ADMIN_WAITLIST_URL);
    const rowA = page.locator('tr', { has: page.getByText('bulk-a@example.test') });
    const rowB = page.locator('tr', { has: page.getByText('bulk-b@example.test') });
    await rowA.locator('.wpwing-entry-cb').check();
    await rowB.locator('.wpwing-entry-cb').check();

    await page.locator('#bulk-action-selector-top').selectOption('delete');
    page.once('dialog', (dialog) => dialog.accept());
    await page.locator('.tablenav.top input.button.action').click();

    // Not asserting on ?deleted=2: see the comment in the single-delete test above.
    await expect(page.locator('tr', { has: page.getByText('bulk-a@example.test') })).toHaveCount(0);
    await expect(page.locator('tr', { has: page.getByText('bulk-b@example.test') })).toHaveCount(0);
  });

  test('exports a CSV file', async ({ page }) => {
    await page.goto(ADMIN_WAITLIST_URL);
    const [download] = await Promise.all([
      page.waitForEvent('download'),
      page.getByRole('link', { name: 'Export CSV' }).click(),
    ]);
    expect(download.suggestedFilename()).toMatch(/^wpwing-waitlist-.*\.csv$/);
  });
});
