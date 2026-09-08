import { expect, test, type Page } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { e2eEnv } from '../playwright.config';

const adminEmail = 'admin@zap.test';
const adminPassword = 'Zap-e2e-pass-1';
const userEmail = 'user@zap.test';
const userPassword = 'Zap-e2e-pass-1';

async function login(page: Page, email: string, password: string): Promise<void> {
    await page.goto('/login');
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);
    await page.getByRole('button', { name: 'Entrar' }).click();
}

async function logout(page: Page): Promise<void> {
    await page.getByRole('button', { name: /Conta / }).click();
    await page.getByRole('button', { name: 'Sair' }).click();
    await expect(page).toHaveURL(/\/login/);
}

function ingestFixture(instancePublicId: string, fixture: string): void {
    execFileSync('php', ['artisan', 'e2e:ingest-provider', instancePublicId, fixture], {
        stdio: 'inherit',
        env: e2eEnv,
    });
}

test('critical path with Evolution mocked', async ({ page, request }) => {
    test.setTimeout(120_000);
    await test.step('platform admin allowlists an email', async () => {
        await login(page, adminEmail, adminPassword);
        await expect(page).toHaveURL(/\/platform\/allowlist/);
        await page.locator('#email').fill(userEmail);
        await page.getByRole('button', { name: 'Adicionar' }).click();
        await expect(page.getByText(userEmail)).toBeVisible();
        await logout(page);
    });

    await test.step('user registers into a workspace', async () => {
        await page.goto('/register');
        await page.locator('#name').fill('Utilizador E2E');
        await page.locator('#email').fill(userEmail);
        await page.locator('#password').fill(userPassword);
        await page.locator('#password_confirmation').fill(userPassword);
        await page.getByRole('button', { name: 'Criar conta' }).click();
        await expect(page).toHaveURL('/');
        await expect(page.getByTestId('dashboard-instances')).toContainText('0');
        await page.screenshot({ path: 'docs/screenshots/dashboard.png', fullPage: true });
    });

    let instancePublicId = '';

    await test.step('creates instance and simulated event marks it connected', async () => {
        await page.getByRole('link', { name: '+ Nova instância' }).click();
        await page.locator('#name').fill('Atendimento E2E');
        await page.getByRole('button', { name: 'Criar instância' }).click();
        await expect(page).toHaveURL(/\/instances\/ins_/);
        instancePublicId = page.url().split('/instances/')[1] ?? '';
        expect(instancePublicId).toMatch(/^ins_/);

        await expect(page.getByTestId('instance-status')).toContainText(/Aguardando leitura|Conectando|Criando/, {
            timeout: 15_000,
        });
        await page.screenshot({ path: 'docs/screenshots/instance-qr.png', fullPage: true });

        ingestFixture(instancePublicId, 'webhook-connection-update-open');
        await page.reload();
        await expect(page.getByTestId('instance-status')).toContainText('Conectado');
    });

    let apiKey = '';

    await test.step('creates API key', async () => {
        await page.goto('/api-keys');
        await page.getByRole('button', { name: '+ Gerar nova chave' }).click();
        await page.locator('#name').fill('E2E');
        for (const checkbox of await page.locator('input[name="abilities[]"]').all()) {
            await checkbox.check();
        }
        await page.getByRole('button', { name: 'Gerar chave' }).click();
        const token = page.getByTestId('plain-text-token');
        await expect(token).toContainText('zap_live_');
        apiKey = (await token.textContent())?.trim() ?? '';
        expect(apiKey).toMatch(/^zap_live_/);
        await page.screenshot({ path: 'docs/screenshots/api-keys.png', fullPage: true });
        await page.getByRole('button', { name: 'Entendi' }).click();
    });

    await test.step('sends text via API (202)', async () => {
        const response = await request.post('/api/v1/messages/text', {
            headers: {
                Authorization: `Bearer ${apiKey}`,
                'Content-Type': 'application/json',
                'Idempotency-Key': 'e2e-send-text-1',
            },
            data: {
                instance_id: instancePublicId,
                to: '5511999999999',
                text: 'Hello from ZAP',
            },
        });

        expect(response.status()).toBe(202);
        const body = (await response.json()) as { data: { status: string } };
        expect(body.data.status).toBe('sending');
    });

    await test.step('builder preview matches fixture and test does not block save', async () => {
        await page.goto('/webhooks/builder');
        await page.locator('#builder-url').fill('https://example.com/webhooks/zap');
        await expect(page.getByTestId('webhook-preview')).toContainText(
            'Olá! Quero saber como integrar com meu CRM.',
        );
        await page.screenshot({ path: 'docs/screenshots/builder.png', fullPage: true });
        await page.getByRole('button', { name: '▶ Testar webhook' }).click();
        await page.getByTestId('webhook-save').click();
        await expect(page).toHaveURL(/\/webhooks\/wh_/);
    });

    await test.step('inbound fixture creates inbox row, media object, and visible delivery', async () => {
        ingestFixture(instancePublicId, 'webhook-messages-upsert-image');

        await page.goto('/inbox');
        await expect(page.getByTestId('inbox-conversation').first()).toBeVisible({ timeout: 15_000 });
        await expect(page.getByText('[image]')).toBeVisible();
        await page.screenshot({ path: 'docs/screenshots/inbox.png', fullPage: true });

        await page.goto('/webhooks');
        await page.locator('a[href^="/webhooks/wh_"]').first().click();
        await expect(page.getByTestId('webhook-delivery-status').first()).toBeVisible({ timeout: 15_000 });
    });

    await test.step('media download succeeds with the API key', async () => {
        const conversations = await request.get('/api/v1/conversations', {
            headers: { Authorization: `Bearer ${apiKey}` },
        });
        expect(conversations.ok()).toBeTruthy();
        const list = (await conversations.json()) as { data: Array<{ id: string }> };
        expect(list.data.length).toBeGreaterThan(0);

        let mediaId: string | undefined;

        for (const conversation of list.data) {
            const messages = await request.get(`/api/v1/conversations/${conversation.id}/messages`, {
                headers: { Authorization: `Bearer ${apiKey}` },
            });
            expect(messages.ok()).toBeTruthy();
            const thread = (await messages.json()) as {
                data: Array<{ media?: { id: string } | null }>;
            };
            mediaId = thread.data.find((message) => message.media?.id)?.media?.id;

            if (mediaId) {
                break;
            }
        }

        expect(mediaId).toBeTruthy();

        const download = await request.get(`/api/v1/media/${mediaId}`, {
            headers: { Authorization: `Bearer ${apiKey}` },
        });
        expect(download.status()).toBe(200);
        expect(download.headers()['content-type']).toContain('image/jpeg');
        const bytes = await download.body();
        expect(bytes.byteLength).toBeGreaterThan(0);
    });
});
