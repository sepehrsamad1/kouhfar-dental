import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/browser',
    timeout: 30_000,
    use: {
        baseURL: 'http://127.0.0.1:8000',
        browserName: 'chromium',
        headless: true,
        trace: 'on-first-retry',
    },
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8000',
        url: 'http://127.0.0.1:8000',
        reuseExistingServer: true,
        timeout: 120_000,
    },
});
