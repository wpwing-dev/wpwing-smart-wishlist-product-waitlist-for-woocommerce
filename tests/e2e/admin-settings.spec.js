const { test, expect } = require('@playwright/test');
const { ADMIN, login } = require('./helpers');

const SETTINGS_URL = '/wp-admin/admin.php?page=wpwing-wl-settings';

async function save(page) {
  // wp-admin polls via heartbeat.js, so 'networkidle' never reliably fires,
  // and waitForNavigation() is ambiguous when more than one navigation-like
  // event can occur on the page. Wait for the actual options.php POST
  // response instead — unambiguous regardless of what else is happening.
  await Promise.all([
    page.waitForResponse((res) => res.url().includes('options.php') && res.request().method() === 'POST'),
    page.getByRole('button', { name: 'Save Changes' }).click(),
  ]);
}

test.describe('Settings page', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, ADMIN);
    await page.goto(SETTINGS_URL);
  });

  test('feature toggles persist and can be turned back on', async ({ page }) => {
    // Not getByLabel(): the field's <label> wraps its description text, not
    // the "Enable Wishlist" title — that title is a plain <th>, not a label.
    const checkbox = page.locator('[name="wpwing_wl_enable_wishlist"]:not([type="hidden"])');
    try {
      await checkbox.uncheck();
      await save(page);
      await page.goto(SETTINGS_URL);
      await expect(page.locator('[name="wpwing_wl_enable_wishlist"]:not([type="hidden"])')).not.toBeChecked();
    } finally {
      await page.locator('[name="wpwing_wl_enable_wishlist"]:not([type="hidden"])').check();
      await save(page);
    }
    await expect(page.locator('[name="wpwing_wl_enable_wishlist"]:not([type="hidden"])')).toBeChecked();
  });

  test('button style and position selections persist', async ({ page }) => {
    const styleField = page.locator('[name="wpwing_wl_wishlist_btn_style"]');
    const positionField = page.locator('[name="wpwing_wl_wishlist_btn_position"]');
    const previousStyle = await styleField.inputValue();
    const previousPosition = await positionField.inputValue();

    try {
      await styleField.selectOption('outline');
      await positionField.selectOption('before_add_to_cart');
      await save(page);
      await page.goto(SETTINGS_URL);
      await expect(page.locator('[name="wpwing_wl_wishlist_btn_style"]')).toHaveValue('outline');
      await expect(page.locator('[name="wpwing_wl_wishlist_btn_position"]')).toHaveValue('before_add_to_cart');
    } finally {
      await page.locator('[name="wpwing_wl_wishlist_btn_style"]').selectOption(previousStyle || 'default');
      await page.locator('[name="wpwing_wl_wishlist_btn_position"]').selectOption(previousPosition || 'after_add_to_cart');
      await save(page);
    }
  });

  test('wishlist button labels persist', async ({ page }) => {
    try {
      await page.locator('[name="wpwing_wl_wishlist_btn_add"]').fill('Save for later');
      await page.locator('[name="wpwing_wl_wishlist_btn_remove"]').fill('Unsave');
      await save(page);
      await page.goto(SETTINGS_URL);
      await expect(page.locator('[name="wpwing_wl_wishlist_btn_add"]')).toHaveValue('Save for later');
      await expect(page.locator('[name="wpwing_wl_wishlist_btn_remove"]')).toHaveValue('Unsave');
    } finally {
      await page.locator('[name="wpwing_wl_wishlist_btn_add"]').fill('');
      await page.locator('[name="wpwing_wl_wishlist_btn_remove"]').fill('');
      await save(page);
    }
  });

  test('waitlist form labels persist', async ({ page }) => {
    try {
      await page.locator('[name="wpwing_wl_waitlist_btn_submit"]').fill('Notify me please');
      await page.locator('[name="wpwing_wl_waitlist_joined_text"]').fill("You're in!");
      await save(page);
      await page.goto(SETTINGS_URL);
      await expect(page.locator('[name="wpwing_wl_waitlist_btn_submit"]')).toHaveValue('Notify me please');
      await expect(page.locator('[name="wpwing_wl_waitlist_joined_text"]')).toHaveValue("You're in!");
    } finally {
      await page.locator('[name="wpwing_wl_waitlist_btn_submit"]').fill('');
      await page.locator('[name="wpwing_wl_waitlist_joined_text"]').fill('');
      await save(page);
    }
  });

  test('email branding fields persist and reject an invalid address', async ({ page }) => {
    try {
      await page.locator('[name="wpwing_wl_from_name"]').fill('E2E Test Store');
      // Dotless domain: passes the browser's native type="email" constraint
      // (so the form actually submits) but fails WordPress's stricter
      // is_email(), which requires a dotted domain - exercising the
      // server-side rejection in Settings::sanitize_optional_email().
      await page.locator('[name="wpwing_wl_from_email"]').fill('test@localhost');
      await save(page);
      await page.goto(SETTINGS_URL);
      await expect(page.locator('[name="wpwing_wl_from_name"]')).toHaveValue('E2E Test Store');
      // Invalid addresses are discarded server-side (Settings::sanitize_optional_email).
      await expect(page.locator('[name="wpwing_wl_from_email"]')).toHaveValue('');
    } finally {
      await page.locator('[name="wpwing_wl_from_name"]').fill('');
      await page.locator('[name="wpwing_wl_from_email"]').fill('');
      await save(page);
    }
  });

  test('email subject/body templates persist', async ({ page }) => {
    try {
      await page.locator('[name="wpwing_wl_email_subject_template"]').fill('Back in stock: {product_name}');
      await page.locator('[name="wpwing_wl_email_body_template"]').fill('<p>{product_name} is back.</p>');
      await save(page);
      await page.goto(SETTINGS_URL);
      await expect(page.locator('[name="wpwing_wl_email_subject_template"]')).toHaveValue('Back in stock: {product_name}');
      await expect(page.locator('[name="wpwing_wl_email_body_template"]')).toHaveValue('<p>{product_name} is back.</p>');
    } finally {
      await page.locator('[name="wpwing_wl_email_subject_template"]').fill('');
      await page.locator('[name="wpwing_wl_email_body_template"]').fill('');
      await save(page);
    }
  });

  test('guest retention days persists', async ({ page }) => {
    // Out-of-range clamping (Settings::sanitize_retention_days) is covered by
    // SettingsTest::test_retention_sanitizer_clamps_values — not retestable
    // here through a real browser, since the field's max="3650" attribute
    // blocks submitting an out-of-range value before it ever reaches the server.
    const field = page.locator('[name="wpwing_wl_guest_retention_days"]');
    try {
      await field.fill('45');
      await save(page);
      await page.goto(SETTINGS_URL);
      await expect(field).toHaveValue('45');
    } finally {
      await field.fill('30');
      await save(page);
    }
  });
});
