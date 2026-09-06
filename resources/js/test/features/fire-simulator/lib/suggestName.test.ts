import { beforeEach, describe, expect, it } from 'vitest';
import i18n from '@/i18n';

import { suggestFireName } from '@/features/fire-simulator/lib/suggestName';

describe('suggestFireName', () => {
    beforeEach(async () => {
        await i18n.changeLanguage('fr');
    });

    it('combines "FIRE" and the current age', () => {
        expect(suggestFireName(i18n.t, 35)).toBe('FIRE à 35 ans');
    });

    it('pluralises a single-year age', () => {
        expect(suggestFireName(i18n.t, 1)).toBe('FIRE à 1 an');
    });

    it('follows the active language', async () => {
        await i18n.changeLanguage('en');

        expect(suggestFireName(i18n.t, 35)).toBe('FIRE at 35 years old');
    });
});
