import { defineConfig, devices } from '@playwright/test';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.dirname(fileURLToPath(import.meta.url));

export const e2eEnv: NodeJS.ProcessEnv = {
    ...process.env,
    APP_ENV: 'e2e',
    APP_DEBUG: 'true',
    APP_KEY: process.env.APP_KEY ?? 'base64:2fl+KTV4KMjxP3a4jXcB/j9zTNl/s5b0dYIFoNo3/4I=',
    APP_URL: 'http://127.0.0.1:8001',
    ADMIN_EMAIL: 'admin@zap.test',
    ADMIN_PASSWORD: 'Zap-e2e-pass-1',
    MESSAGING_DRIVER: 'fake',
    WEBHOOK_FAKE_DELIVERY: 'true',
    WEBHOOK_ALLOW_HTTP: 'true',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: path.join(root, 'database', 'e2e.sqlite'),
    SESSION_DRIVER: 'file',
    CACHE_STORE: 'array',
    QUEUE_CONNECTION: 'sync',
    BROADCAST_CONNECTION: 'null',
    MAIL_MAILER: 'array',
    BCRYPT_ROUNDS: '4',
    MAX_INSTANCES_PER_WORKSPACE: '1',
};

export default defineConfig({
    testDir: './e2e',
    fullyParallel: false,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 1 : 0,
    workers: 1,
    reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : 'list',
    globalSetup: './e2e/global-setup.ts',
    use: {
        baseURL: 'http://127.0.0.1:8001',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8001',
        url: 'http://127.0.0.1:8001/up',
        reuseExistingServer: !process.env.CI,
        timeout: 120_000,
        env: e2eEnv,
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
