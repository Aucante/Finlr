import { useTranslation } from 'react-i18next';

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { CURRENCIES, CURRENCY } from '@/lib/currency';
import { cn } from '@/lib/utils';

export default function SimulationPreferencesCard() {
    const { t } = useTranslation();

    return (
        <Card className="gap-0 py-0">
            <CardHeader className="gap-1.5 border-b border-border py-5">
                <div className="flex items-baseline gap-2">
                    <span aria-hidden className="font-mono text-xs text-brand">
                        04
                    </span>
                    <CardTitle className="text-base">
                        {t('settings.simulationPreferences.title')}
                    </CardTitle>
                </div>
                <CardDescription>
                    {t('settings.simulationPreferences.description')}
                </CardDescription>
            </CardHeader>

            <CardContent className="flex flex-col gap-2 py-6">
                <span className="text-sm text-foreground">
                    {t('settings.simulationPreferences.currency.label')}
                </span>

                <div
                    role="radiogroup"
                    aria-label={t('settings.simulationPreferences.currency.label')}
                    className="flex w-fit items-center gap-1 rounded-full border border-border p-1"
                >
                    {CURRENCIES.map((currency) => {
                        const active = currency.code === CURRENCY;

                        return (
                            <button
                                key={currency.code}
                                type="button"
                                role="radio"
                                aria-checked={active}
                                disabled={!active}
                                title={
                                    active
                                        ? undefined
                                        : t(
                                              'settings.simulationPreferences.currency.comingSoon',
                                          )
                                }
                                className={cn(
                                    'rounded-full px-3 py-1 text-xs font-medium transition-colors disabled:cursor-not-allowed',
                                    active
                                        ? 'bg-brand text-brand-foreground'
                                        : 'text-muted-foreground/50',
                                )}
                            >
                                {currency.symbol}
                            </button>
                        );
                    })}
                </div>

                <p className="text-xs text-muted-foreground">
                    {t('settings.simulationPreferences.currency.hint')}
                </p>
            </CardContent>
        </Card>
    );
}
