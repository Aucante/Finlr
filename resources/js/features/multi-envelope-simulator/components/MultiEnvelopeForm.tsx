import { useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import type { SubmitEvent } from 'react';
import { useTranslation } from 'react-i18next';

import SliderField from '@/components/form/SliderField';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import EnvelopeRow from '@/features/multi-envelope-simulator/components/EnvelopeRow';
import { suggestMultiEnvelopeName } from '@/features/multi-envelope-simulator/lib/suggestName';
import type {
    AccountType,
    EnvelopeFormValues,
    MultiEnvelopeFormValues,
    MultiEnvelopeSimulatorPageProps,
} from '@/features/multi-envelope-simulator/types';
import { formatCurrency } from '@/lib/currency';

/**
 * Mirrors RunMultiEnvelopeSimulationRequest's own `envelopes` bound
 * (`max:8`, one per AccountType case) — kept in sync manually since the
 * two run in different languages; the request is the rule that is actually
 * enforced, this is only a friendlier UX cap before that request round-trip.
 */
const MAX_ENVELOPES = 8;

function makeEnvelope(accountType: AccountType, defaults: MultiEnvelopeSimulatorPageProps['defaults']): EnvelopeFormValues {
    return {
        accountType,
        initialAmount: defaults.initialAmount,
        monthlyContribution: defaults.monthlyContribution,
        durationYears: defaults.durationYears,
        annualReturnRate: defaults.annualReturnRate,
        managementFeeRate: defaults.managementFeeRate,
    };
}

type MultiEnvelopeFormProps = MultiEnvelopeSimulatorPageProps;

export default function MultiEnvelopeForm({ defaults, accountTypes }: MultiEnvelopeFormProps) {
    const { t, i18n } = useTranslation();
    const { data, setData, post, processing, errors, setError, clearErrors } = useForm<MultiEnvelopeFormValues>({
        name: '',
        inflationRate: defaults.inflationRate,
        envelopes: [
            makeEnvelope(accountTypes[0], defaults),
            makeEnvelope(accountTypes[2] ?? accountTypes[0], defaults),
        ],
    });

    // Not fields of MultiEnvelopeFormValues: "simulation" is flashed by the
    // controller when the calculation itself fails (not tied to one input),
    // and per-envelope errors use dotted keys ("envelopes.0.initialAmount")
    // that TypeScript can't express against a fixed-shape form type.
    const fieldErrors = errors as Record<string, string | undefined>;
    const simulationError = fieldErrors.simulation;

    const submit = (e: SubmitEvent) => {
        e.preventDefault();

        if (data.name.trim() === '') {
            setError('name', t('simulator.form.nameRequired'));
            return;
        }

        post(route('simulators.multi-envelope.run'));
    };

    const suggestName = () => {
        setData('name', suggestMultiEnvelopeName(t, data.envelopes));
        clearErrors('name');
    };

    const updateEnvelope = <K extends keyof EnvelopeFormValues>(index: number, field: K, value: EnvelopeFormValues[K]) => {
        setData(
            'envelopes',
            data.envelopes.map((envelope, i) => (i === index ? { ...envelope, [field]: value } : envelope)),
        );
    };

    const addEnvelope = () => {
        const nextAccountType =
            accountTypes.find((type) => !data.envelopes.some((envelope) => envelope.accountType === type)) ?? accountTypes[0];

        setData('envelopes', [...data.envelopes, makeEnvelope(nextAccountType, defaults)]);
    };

    const removeEnvelope = (index: number) => {
        setData(
            'envelopes',
            data.envelopes.filter((_, i) => i !== index),
        );
    };

    const totalInitialAmount = data.envelopes.reduce((sum, envelope) => sum + envelope.initialAmount, 0);
    const totalMonthlyContribution = data.envelopes.reduce((sum, envelope) => sum + envelope.monthlyContribution, 0);

    return (
        <form onSubmit={submit} className="grid gap-6 lg:grid-cols-3 lg:items-start">
            <div className="flex flex-col gap-6 lg:col-span-2">
                {simulationError && (
                    <p role="alert" className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">
                        {simulationError}
                    </p>
                )}

                <Card>
                    <CardHeader className="flex flex-row items-center gap-2">
                        <span className="font-mono text-xs text-brand">01</span>
                        <h2 className="text-base font-semibold tracking-tight">
                            {t('simulator.multiEnvelope.form.sections.name')}
                        </h2>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-6">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="flex flex-col gap-2">
                                <div className="flex items-center justify-between gap-2">
                                    <Label htmlFor="name">{t('simulator.multiEnvelope.form.name')}</Label>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="text-brand hover:bg-brand/10 hover:text-brand"
                                        onClick={suggestName}
                                    >
                                        {t('simulator.multiEnvelope.form.suggestName.button')}
                                    </Button>
                                </div>
                                <Input
                                    id="name"
                                    name="name"
                                    type="text"
                                    maxLength={255}
                                    placeholder={t('simulator.multiEnvelope.form.namePlaceholder')}
                                    value={data.name}
                                    aria-invalid={Boolean(errors.name)}
                                    onChange={(e) => {
                                        setData('name', e.target.value);
                                        clearErrors('name');
                                    }}
                                />
                                {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                            </div>
                        </div>

                        <SliderField
                            fieldKey="inflationRate"
                            label={t('simulator.multiEnvelope.form.fields.inflationRate.label')}
                            helpText={t('simulator.multiEnvelope.form.fields.inflationRate.helpText')}
                            value={data.inflationRate}
                            unit="percent"
                            step={0.1}
                            min={0}
                            max={50}
                            error={errors.inflationRate}
                            onChange={(value) => setData('inflationRate', value)}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center gap-2">
                        <span className="font-mono text-xs text-brand">02</span>
                        <h2 className="text-base font-semibold tracking-tight">
                            {t('simulator.multiEnvelope.form.sections.envelopes')}
                        </h2>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-4">
                        {errors.envelopes && (
                            <p role="alert" className="text-xs text-destructive">
                                {errors.envelopes}
                            </p>
                        )}

                        {data.envelopes.map((envelope, index) => (
                            <EnvelopeRow
                                key={index}
                                index={index}
                                accountTypes={accountTypes}
                                values={envelope}
                                errors={fieldErrors}
                                canRemove={data.envelopes.length > 1}
                                onChange={(field, value) => updateEnvelope(index, field, value)}
                                onRemove={() => removeEnvelope(index)}
                            />
                        ))}

                        <Button
                            type="button"
                            variant="brand"
                            size="sm"
                            className="w-1/4 min-w-fit self-end"
                            disabled={data.envelopes.length >= MAX_ENVELOPES}
                            onClick={addEnvelope}
                        >
                            <Plus aria-hidden className="size-4" />
                            {t('simulator.multiEnvelope.form.addEnvelope')}
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="gap-2">
                        <span className="font-mono text-xs tracking-wide text-muted-foreground uppercase">
                            {t('simulator.multiEnvelope.form.summary.eyebrow')}
                        </span>
                        <p className="text-sm text-muted-foreground">
                            {t('simulator.multiEnvelope.form.summary.description')}
                        </p>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-4">
                        <dl className="flex flex-col gap-2.5 border-t border-border pt-4 text-sm">
                            <div className="flex items-center justify-between gap-4">
                                <dt className="text-muted-foreground">
                                    {t('simulator.multiEnvelope.form.summary.scenarioLabel')}
                                </dt>
                                <dd className="truncate font-medium">
                                    {data.name || t('simulator.multiEnvelope.form.summary.scenarioPlaceholder')}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between gap-4">
                                <dt className="text-muted-foreground">
                                    {t('simulator.multiEnvelope.form.summary.envelopeCount')}
                                </dt>
                                <dd className="font-mono tabular-nums">{data.envelopes.length}</dd>
                            </div>
                            <div className="flex items-center justify-between gap-4">
                                <dt className="text-muted-foreground">
                                    {t('simulator.multiEnvelope.form.summary.totalInitialAmount')}
                                </dt>
                                <dd className="font-mono tabular-nums">
                                    {formatCurrency(totalInitialAmount, i18n.resolvedLanguage)}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between gap-4">
                                <dt className="text-muted-foreground">
                                    {t('simulator.multiEnvelope.form.summary.totalMonthlyContribution')}
                                </dt>
                                <dd className="font-mono tabular-nums">
                                    {formatCurrency(totalMonthlyContribution, i18n.resolvedLanguage)}
                                </dd>
                            </div>
                        </dl>

                        <Button type="submit" variant="brand" size="lg" disabled={processing}>
                            {t('simulator.multiEnvelope.form.submit')}
                        </Button>

                        <p className="text-center text-xs text-muted-foreground">
                            {t('simulator.multiEnvelope.form.summary.retentionNote')}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </form>
    );
}
