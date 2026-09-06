import type { TFunction } from 'i18next';

import type { EnvelopeFormValues } from '@/features/multi-envelope-simulator/types';

/**
 * Builds a suggested scenario name from the cascade's envelope count, and
 * its duration too when every envelope shares the same one — a cascade
 * with diverging durations has no single horizon worth naming, so the
 * suggestion falls back to the count alone.
 */
export function suggestMultiEnvelopeName(t: TFunction, envelopes: EnvelopeFormValues[]): string {
    const count = envelopes.length;
    const [first, ...rest] = envelopes;
    const hasCoherentDuration = first !== undefined && rest.every((envelope) => envelope.durationYears === first.durationYears);

    if (hasCoherentDuration && first !== undefined) {
        const years = first.durationYears;

        return t('simulator.multiEnvelope.form.suggestName.withDuration', {
            count,
            years,
            yearsUnit: t('form.yearsUnit', { count: years }),
        });
    }

    return t('simulator.multiEnvelope.form.suggestName.withoutDuration', { count });
}
