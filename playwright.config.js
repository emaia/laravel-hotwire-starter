import { defineConfig } from '@playwright/test';

if (!process.env.PW_TEST_CONNECT_WS_ENDPOINT) {
    throw new Error('Browser checks require the Playwright sidecar and PW_TEST_CONNECT_WS_ENDPOINT.');
}

export default defineConfig({
    testDir: './tests/Browser',
    testMatch: '**/*.pw.js',
    outputDir: './test-results',
    workers: 1,
    timeout: 45_000,
    use: {
        // Browser localhost belongs to the sidecar, not to Laravel.
        baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://app.test:8080',
        connectOptions: { wsEndpoint: process.env.PW_TEST_CONNECT_WS_ENDPOINT },
        screenshot: 'only-on-failure',
    },
    projects: [
        { name: 'desktop', use: { viewport: { width: 1440, height: 1000 } } },
        { name: 'mobile', use: { viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true } },
    ],
});
