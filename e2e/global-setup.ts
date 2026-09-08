import { execSync } from 'node:child_process';
import fs from 'node:fs';
import { e2eEnv } from '../playwright.config';

export default function globalSetup(): void {
    const database = e2eEnv.DB_DATABASE;

    if (database && fs.existsSync(database)) {
        fs.unlinkSync(database);
    }

    execSync('php artisan migrate:fresh --seed --force', {
        stdio: 'inherit',
        env: e2eEnv,
    });
}
