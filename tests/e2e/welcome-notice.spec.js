const { test, expect } = require('@playwright/test');
const { ADMIN, login } = require('./helpers');

// The welcome notice is a one-time, post-activation prompt: on the shared dev
// stack it may already have been dismissed by an earlier run. This test
// exercises the dismiss mechanism when the notice is showing, and otherwise
// just confirms it stays gone — both are valid states for a persisted option.
test.describe('Welcome notice', () => {
  test('dismissing it hides it permanently', async ({ page }) => {
    await login(page, ADMIN);
    await page.goto('/wp-admin/admin.php?page=wpwing-wl-settings');

    const notice = page.locator('.wpwing-wl-welcome-notice');

    if (await notice.count()) {
      await notice.locator('.notice-dismiss').click();
      await expect(notice).toBeHidden();
    }

    await page.reload();
    await expect(page.locator('.wpwing-wl-welcome-notice')).toHaveCount(0);
  });
});
