import type { TFunction } from 'i18next';

import type { TaxWrapper } from '@/features/single-envelope-simulator/types';

/**
 * Builds a suggested scenario name from the wrapper (known from the page's
 * own URL/context, not a form field) and the duration currently entered —
 * the only two values that make a single-envelope scenario recognisable at
 * a glance.
 */
export function suggestSingleEnvelopeName(t: TFunction, wrapper: TaxWrapper, years: number): string {
    return t('simulator.singleEnvelope.form.suggestName.template', {
        wrapper: t(`simulator.singleEnvelope.form.wrapperOptions.${wrapper}`),
        years,
        yearsUnit: t('form.yearsUnit', { count: years }),
    });
}
