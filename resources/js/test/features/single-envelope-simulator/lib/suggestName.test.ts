import { beforeEach, describe, expect, it } from 'vitest';
import i18n from '@/i18n';

import { suggestSingleEnvelopeName } from '@/features/single-envelope-simulator/lib/suggestName';

describe('suggestSingleEnvelopeName', () => {
    beforeEach(async () => {
        await i18n.changeLanguage('fr');
    });

    it('combines the wrapper and the duration', () => {
        expect(suggestSingleEnvelopeName(i18n.t, 'pea', 15)).toBe('PEA sur 15 ans');
    });

    it('pluralises the duration for a single year', () => {
        expect(suggestSingleEnvelopeName(i18n.t, 'cto', 1)).toBe('CTO sur 1 an');
    });

    it('follows the active language', async () => {
        await i18n.changeLanguage('en');

        expect(suggestSingleEnvelopeName(i18n.t, 'pea', 15)).toBe('PEA over 15 years');
    });
});
