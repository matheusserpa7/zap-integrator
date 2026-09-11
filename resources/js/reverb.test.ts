import { describe, expect, it } from 'vitest';
import { isReverbConfigured } from '@/reverb';

describe('isReverbConfigured', () => {
    it('returns false when the public Reverb key is missing', () => {
        expect(
            isReverbConfigured({
                key: null,
                host: 'localhost',
                port: 8000,
                scheme: 'http',
            }),
        ).toBe(false);
    });

    it('returns true when the public Reverb key is present', () => {
        expect(
            isReverbConfigured({
                key: 'zap-local-reverb-key',
                host: 'localhost',
                port: 8000,
                scheme: 'http',
            }),
        ).toBe(true);
    });
});
