import type { TFunction } from 'i18next';

/**
 * Builds a suggested scenario name from the current age entered — FIRE has
 * no envelope and no single duration field, but the age is what actually
 * distinguishes one projection from another.
 */
export function suggestFireName(t: TFunction, currentAge: number): string {
    return t('simulator.fire.form.suggestName.template', {
        age: currentAge,
        yearsUnit: t('form.yearsUnit', { count: currentAge }),
    });
}
