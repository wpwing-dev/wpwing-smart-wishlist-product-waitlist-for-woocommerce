const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './tests/e2e',
    use: {
        baseURL: process.env.BASE_URL || 'https://smart-wishlist.local',
        screenshot: 'only-on-failure',
    },
    reporter: 'list',
});
