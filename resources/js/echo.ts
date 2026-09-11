import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import type { ReverbPublicConfig } from '@/Types';
import { isReverbConfigured } from '@/reverb';

export { inboxMessageEvents, isReverbConfigured } from '@/reverb';

let echo: Echo<'reverb'> | null = null;

declare global {
    interface Window {
        Pusher: typeof Pusher;
    }
}

export function createWorkspaceEcho(config: ReverbPublicConfig): Echo<'reverb'> | null {
    if (!isReverbConfigured(config)) {
        return null;
    }

    if (echo) {
        return echo;
    }

    window.Pusher = Pusher;

    echo = new Echo({
        broadcaster: 'reverb',
        key: config.key,
        wsHost: config.host ?? window.location.hostname,
        wsPort: config.port ?? 80,
        wssPort: config.port ?? 443,
        forceTLS: config.scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
    });

    return echo;
}
