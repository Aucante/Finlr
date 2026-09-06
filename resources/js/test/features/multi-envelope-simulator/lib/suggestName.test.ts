import { beforeEach, describe, expect, it } from 'vitest';
import i18n from '@/i18n';

import { suggestMultiEnvelopeName } from '@/features/multi-envelope-simulator/lib/suggestName';
import type { EnvelopeFormValues } from '@/features/multi-envelope-simulator/types';

function envelope(overrides: Partial<EnvelopeFormValues> = {}): EnvelopeFormValues {
    return {
        accountType: 'PEA',
        initialAmount: 0,
        monthlyContribution: 300,
        durationYears: 15,
        annualReturnRate: 6,
        managementFeeRate: 0.5,
        ...overrides,
    };
}

describe('suggestMultiEnvelopeName', () => {
    beforeEach(async () => {
        await i18n.changeLanguage('fr');
    });

    it('includes the duration when every envelope shares the same one', () => {
        const envelopes = [envelope({ durationYears: 15 }), envelope({ accountType: 'CTO', durationYears: 15 })];

        expect(suggestMultiEnvelopeName(i18n.t, envelopes)).toBe('Multi-enveloppe (2 poches, 15 ans)');
    });

    it('falls back to the count alone when durations diverge', () => {
        const envelopes = [
            envelope({ durationYears: 10 }),
            envelope({ accountType: 'CTO', durationYears: 20 }),
            envelope({ accountType: 'ASSURANCE_VIE', durationYears: 15 }),
        ];

        expect(suggestMultiEnvelopeName(i18n.t, envelopes)).toBe('Multi-enveloppe (3 poches)');
    });

    it('pluralises a single pocket', () => {
        expect(suggestMultiEnvelopeName(i18n.t, [envelope({ durationYears: 15 })])).toBe('Multi-enveloppe (1 poche, 15 ans)');
    });

    it('follows the active language', async () => {
        await i18n.changeLanguage('en');

        const envelopes = [envelope({ durationYears: 10 }), envelope({ accountType: 'CTO', durationYears: 20 })];

        expect(suggestMultiEnvelopeName(i18n.t, envelopes)).toBe('Multi-envelope (2 pockets)');
    });
});
