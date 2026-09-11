import type { ReverbPublicConfig } from '@/Types';

export const inboxMessageEvents = ['.MessageAccepted', '.MessageReceived', '.MessageSent', '.MessageFailed'] as const;

export function isReverbConfigured(config: ReverbPublicConfig): config is ReverbPublicConfig & { key: string } {
    return typeof config.key === 'string' && config.key !== '';
}
