import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright E2E configuration for TB-PAPA-CEEAC.
 *
 * Requirements:
 *   - Laravel dev server running on http://127.0.0.1:8000
 *   - Database seeded with test data (php artisan tbpapa:seed-demo --append)
 *
 * Run:
 *   npm run test:e2e             — run all E2E tests headless
 *   npm run test:e2e:ui          — run with Playwright UI mode (debug)
 *   npm run test:e2e:debug       — run in headed browser with inspector
 */
export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false, // Database-backed tests : run serial to avoid race conditions
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: 1,
    reporter: process.env.CI ? 'github' : 'list',

    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'http://127.0.0.1:8000',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        locale: 'fr-FR',
        timezoneId: 'Africa/Libreville',
    },

    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
        // Décommenter pour cross-browser :
        // { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
        // { name: 'webkit', use: { ...devices['Desktop Safari'] } },
    ],

    expect: {
        timeout: 10_000,
    },
});
