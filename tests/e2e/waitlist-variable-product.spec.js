const { test, expect } = require('@playwright/test');
const { PRODUCTS, productUrl } = require('./helpers');

test.describe('Waitlist form on a variable product', () => {
  test('shows only for the out-of-stock variation, hides for the in-stock one', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.variable));

    const container = page.locator('.wpwing-waitlist-form');
    await expect(container).toBeHidden(); // no variation selected yet

    const sizeSelect = page.locator('select[name="attribute_size"]');
    await sizeSelect.selectOption({ label: 'Small' }); // in stock
    await expect(container).toBeHidden();

    await sizeSelect.selectOption({ label: 'Large' }); // out of stock
    await expect(container).toBeVisible();
    await expect(container.locator('.wpwing-waitlist-fields')).toBeVisible();
  });

  test('can join for a specific variation and re-shows the joined state when reselected', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.variable));
    const container = page.locator('.wpwing-waitlist-form');
    const sizeSelect = page.locator('select[name="attribute_size"]');

    await sizeSelect.selectOption({ label: 'Large' });
    await container.locator('#wpwing-waitlist-email').fill('variable-waitlist@example.test');
    await container.locator('.wpwing-waitlist-submit').click();
    await expect(container.locator('.wpwing-waitlist-joined')).toBeVisible();

    // Switch to the other variation and back — the joined state for Large
    // should reappear (tracked client-side via localStorage per variation).
    await sizeSelect.selectOption({ label: 'Small' });
    await expect(container).toBeHidden();
    await sizeSelect.selectOption({ label: 'Large' });
    await expect(container.locator('.wpwing-waitlist-joined')).toBeVisible();

    // Clean up.
    await container.locator('.wpwing-waitlist-leave').click();
    await expect(container.locator('.wpwing-waitlist-fields')).toBeVisible();
  });
});
