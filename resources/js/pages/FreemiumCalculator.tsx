import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';

import Footer from '@/components/Footer';
import Navbar from '@/components/Navbar';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import CalculatorForm from '@/features/freemium-calculator/components/CalculatorForm';
import FreemiumDisclaimer from '@/features/freemium-calculator/components/FreemiumDisclaimer';
import GrowthChart from '@/features/freemium-calculator/components/GrowthChart';
import KpiCards from '@/features/freemium-calculator/components/KpiCards';
import { FORM_DEFAULTS, TAX_SUGGESTIONS } from '@/features/freemium-calculator/constants';
import { computeCompound } from '@/features/freemium-calculator/lib/compound';
import type {
    CompoundInputs,
    FreemiumCalculatorPageProps,
} from '@/features/freemium-calculator/types';

export default function FreemiumCalculator({
    canLogin,
    canRegister,
}: FreemiumCalculatorPageProps) {
    const { t } = useTranslation();
    const [inputs, setInputs] = useState<CompoundInputs>(FORM_DEFAULTS);

    const result = useMemo(() => computeCompound(inputs), [inputs]);

    const handleChange = (patch: Partial<CompoundInputs>) =>
        setInputs((prev) => ({ ...prev, ...patch }));

    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <Head title={t('hero.title')} />

            <Navbar canLogin={canLogin} canRegister={canRegister} />

            <main className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-8 px-4 py-8 lg:px-8 lg:py-12">
                <header className="flex flex-col gap-2">
                    <span className="flex items-center gap-2 text-xs font-medium tracking-wide text-brand uppercase">
                        <span aria-hidden className="size-1.5 rounded-full bg-brand" />
                        {t('hero.eyebrow')}
                    </span>
                    <h1 className="text-2xl font-semibold tracking-tight text-balance lg:text-3xl">
                        {t('hero.title')}
                    </h1>
                    <p className="max-w-2xl text-sm text-pretty text-muted-foreground">
                        {t('hero.description')}
                    </p>
                </header>

                <FreemiumDisclaimer />

                <div className="grid gap-6 lg:grid-cols-[minmax(0,380px)_minmax(0,1fr)] lg:items-stretch">
                    <CalculatorForm
                        inputs={inputs}
                        onChange={handleChange}
                        taxSuggestions={TAX_SUGGESTIONS}
                    />

                    <div className="flex h-full flex-col gap-6">
                        <KpiCards result={result} inflationEnabled={inputs.inflationEnabled} />

                        <Card className="flex-1 gap-0 py-0">
                            <CardHeader className="border-b border-border py-5">
                                <CardTitle className="text-base">
                                    {t('chart.title', { count: inputs.years })}
                                </CardTitle>
                                <CardDescription>
                                    {t('chart.description')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="py-6">
                                <GrowthChart
                                    result={result}
                                    inflationEnabled={inputs.inflationEnabled}
                                />
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </main>

            <Footer />
        </div>
    );
}
