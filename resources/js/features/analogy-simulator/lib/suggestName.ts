import type { TFunction } from 'i18next';

import type { AccountType } from '@/features/analogy-simulator/types';

/**
 * Builds a suggested scenario name from the two envelope types being
 * compared, not their labels (labelA/labelB are usually still empty at this
 * stage — the account types are what already carries meaning).
 */
export function suggestAnalogyName(t: TFunction, accountTypeA: AccountType, accountTypeB: AccountType): string {
    return t('simulator.analogy.form.suggestName.template', {
        accountTypeA: t(`simulator.analogy.accountTypes.${accountTypeA}`),
        accountTypeB: t(`simulator.analogy.accountTypes.${accountTypeB}`),
    });
}
