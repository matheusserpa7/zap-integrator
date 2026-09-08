import { describe, expect, it } from 'vitest';
import { cn } from '@/lib/utils';

describe('cn', () => {
    it('merges tailwind classes without duplicates', () => {
        expect(cn('px-2', 'px-4', 'text-ink')).toBe('px-4 text-ink');
    });
});
