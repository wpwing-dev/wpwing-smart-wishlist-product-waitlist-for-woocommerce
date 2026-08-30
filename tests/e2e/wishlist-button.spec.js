const { test, expect } = require('@playwright/test');
const { ADMIN, CUSTOMER, PRODUCTS, login, productUrl, setWishlistSetting } = require('./helpers');

const CATEGORY_URL = '/product-category/e2e-fixtures/';
// The fixtures share a category so loop-context tests have a small, stable
// place to look, but that means each fixture also appears as a "related
// product" at the bottom of every other fixture's single-product page — so
// the single-page button must be distinguished from that loop button.
const SINGLE_BUTTON = '.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)';

test.describe('Wishlist toggle button', () => {
  test('toggles add/remove on the single product page and persists across reload (guest)', async ({ page }) => {
    await page.goto(productUrl(PRODUCTS.wishlist));

    const button = page.locator(SINGLE_BUTTON);
    await expect(button).toHaveAttribute('aria-pressed', 'false');

    await button.click();
    await expect(button).toHaveAttribute('aria-pressed', 'true');
    await expect(button).toHaveAttribute('data-in-wishlist', '1');

    await page.reload();
    await expect(page.locator(SINGLE_BUTTON)).toHaveAttribute('aria-pressed', 'true');

    // Clean up so the fixture returns to its baseline state.
    await page.locator(SINGLE_BUTTON).click();
    await expect(page.locator(SINGLE_BUTTON)).toHaveAttribute('aria-pressed', 'false');
  });

  test('toggles add/remove from a shop/archive loop card (guest)', async ({ page }) => {
    await page.goto(CATEGORY_URL);

    const card = page.locator('li.product', { has: page.locator(`a[href*="/${PRODUCTS.wishlist}/"]`) });
    const loopButton = card.locator('.wpwing-wishlist-toggle');

    await expect(loopButton).toHaveAttribute('aria-pressed', 'false');
    await loopButton.click();
    await expect(loopButton).toHaveAttribute('aria-pressed', 'true');

    // The same product's single-page button should reflect the same state.
    await page.goto(productUrl(PRODUCTS.wishlist));
    await expect(page.locator(SINGLE_BUTTON)).toHaveAttribute('aria-pressed', 'true');

    // Clean up.
    await page.locator(SINGLE_BUTTON).click();
    await expect(page.locator(SINGLE_BUTTON)).toHaveAttribute('aria-pressed', 'false');
  });

  test('reflects wishlist state for a logged-in customer, independent of guest cookies', async ({ page }) => {
    await login(page, CUSTOMER);
    await page.goto(productUrl(PRODUCTS.wishlist));

    const button = page.locator(SINGLE_BUTTON);
    await expect(button).toHaveAttribute('aria-pressed', 'false');
    await button.click();
    await expect(button).toHaveAttribute('aria-pressed', 'true');

    await page.reload();
    await expect(page.locator(SINGLE_BUTTON)).toHaveAttribute('aria-pressed', 'true');

    // Clean up.
    await page.locator(SINGLE_BUTTON).click();
    await expect(page.locator(SINGLE_BUTTON)).toHaveAttribute('aria-pressed', 'false');
  });

  test('applies the selected button style', async ({ page }) => {
    await login(page, ADMIN);
    const previous = await setWishlistSetting(page, 'wishlist_btn_style', 'filled');

    await page.goto(productUrl(PRODUCTS.wishlist));
    await expect(page.locator(SINGLE_BUTTON)).toHaveClass(/wpwing-wishlist-style-filled/);

    await setWishlistSetting(page, 'wishlist_btn_style', previous || 'default');
  });

  test('renders before or after Add to Cart per the position setting', async ({ page }) => {
    await login(page, ADMIN);

    async function buttonOrder() {
      await page.goto(productUrl(PRODUCTS.wishlist));
      return page.evaluate((selector) => {
        const wishlistBtn = document.querySelector(selector);
        const addToCart = document.querySelector('.single_add_to_cart_button');
        // DOCUMENT_POSITION_FOLLOWING on addToCart means wishlistBtn comes first.
        const position = wishlistBtn.compareDocumentPosition(addToCart);
        return (position & Node.DOCUMENT_POSITION_FOLLOWING) ? 'before' : 'after';
      }, SINGLE_BUTTON);
    }

    // Default, unmodified setting: after Add to Cart.
    expect(await buttonOrder()).toBe('after');

    await setWishlistSetting(page, 'wishlist_btn_position', 'before_add_to_cart');
    expect(await buttonOrder()).toBe('before');

    await setWishlistSetting(page, 'wishlist_btn_position', 'after_add_to_cart');
    expect(await buttonOrder()).toBe('after');
  });
});
