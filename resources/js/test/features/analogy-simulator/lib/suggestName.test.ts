import { beforeEach, describe, expect, it } from 'vitest';
import i18n from '@/i18n';

import { suggestAnalogyName } from '@/features/analogy-simulator/lib/suggestName';

describe('suggestAnalogyName', () => {
    beforeEach(async () => {
        await i18n.changeLanguage('fr');
    });

    it('joins the two translated account types with "vs"', () => {
        expect(suggestAnalogyName(i18n.t, 'PEA', 'CTO')).toBe('PEA vs CTO');
    });

    it('translates account types that are not bare codes', () => {
        expect(suggestAnalogyName(i18n.t, 'ASSURANCE_VIE', 'LIVRET_A')).toBe('Assurance-vie vs Livret A');
    });

    it('follows the active language', async () => {
        await i18n.changeLanguage('it');

        expect(suggestAnalogyName(i18n.t, 'ASSURANCE_VIE', 'CTO')).toBe('Assicurazione vita vs CTO');
    });
});
