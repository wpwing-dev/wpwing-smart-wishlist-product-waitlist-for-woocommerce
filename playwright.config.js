const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './tests/e2e',
    // Tests share one mutable WordPress instance (single admin account, shared
    // fixture products) rather than an isolated environment per worker, so
    // suites run serially to avoid cross-test data races.
    workers: 1,
    // Some tests do multiple full WP-admin round trips (e.g. save settings
    // twice - once to change a value, once in a cleanup finally block), and
    // each round trip is genuinely slow on this resource-constrained stack.
    timeout: 60000,
    use: {
        baseURL: process.env.BASE_URL || 'https://smart-wishlist.local',
        ignoreHTTPSErrors: true,
        screenshot: 'only-on-failure',
        // The dev stack runs on tight memory limits under real browser load;
        // give navigation a bit more room than Playwright's 30s default.
        navigationTimeout: 45000,
    },
    reporter: 'list',
});
