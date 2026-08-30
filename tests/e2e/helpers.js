const ADMIN = { username: 'admin', password: 'password' };
const CUSTOMER = { username: 'e2e-customer', password: 'E2ePassword123!' };

// Fixed slugs seeded by docker/wordpress/seed-e2e-fixtures.php.
const PRODUCTS = {
  wishlist: 'e2e-wishlist-product',
  oosSimple: 'e2e-oos-simple',
  variable: 'e2e-variable',
};

async function login(page, user) {
  await page.goto('/wp-login.php');
  await page.locator('#user_login').fill(user.username);
  await page.locator('#user_pass').fill(user.password);
  await page.locator('#wp-submit').click();
}

async function logout(page) {
  await page.context().clearCookies();
}

function productUrl(slug) {
  return `/product/${slug}/`;
}

/**
 * Change one field on the Settings page (identified by its `name` attribute
 * suffix, e.g. "wishlist_btn_position") and save. Returns the field's value
 * before the change so callers can restore it afterwards.
 */
async function setWishlistSetting(page, key, value) {
  await page.goto('/wp-admin/admin.php?page=wpwing-wl-settings');
  const field = page.locator(`[name="wpwing_wl_${key}"]`);
  const previous = await field.inputValue();
  await field.selectOption(value);
  // wp-admin polls via heartbeat.js, so 'networkidle' never reliably fires,
  // and waitForNavigation() is ambiguous when more than one navigation-like
  // event can occur on the page. Wait for the actual options.php POST
  // response instead — unambiguous regardless of what else is happening.
  await Promise.all([
    page.waitForResponse((res) => res.url().includes('options.php') && res.request().method() === 'POST'),
    page.getByRole('button', { name: 'Save Changes' }).click(),
  ]);
  return previous;
}

module.exports = { ADMIN, CUSTOMER, PRODUCTS, login, logout, productUrl, setWishlistSetting };
