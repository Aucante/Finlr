import { useForm } from '@inertiajs/react';
import type { SubmitEvent } from 'react';
import { useTranslation } from 'react-i18next';

import AmountField from '@/components/form/AmountField';
import SliderField from '@/components/form/SliderField';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    FORM_FIELD_CONFIG,
    FORM_SECTIONS,
    groupFieldsForLayout,
    type FormFieldKey,
} from '@/features/single-envelope-simulator/lib/formFields';
import { suggestSingleEnvelopeName } from '@/features/single-envelope-simulator/lib/suggestName';
import type {
    Jurisdiction,
    SingleEnvelopeFormDefaults,
    SingleEnvelopeFormValues,
    TaxWrapper,
} from '@/features/single-envelope-simulator/types';

interface CheckboxFieldProps {
    fieldKey: FormFieldKey;
    label: string;
    helpText: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
}

function CheckboxField({ fieldKey, label, helpText, checked, onChange }: CheckboxFieldProps) {
    return (
        <div className="flex items-start gap-3">
            <Checkbox
                id={fieldKey}
                checked={checked}
                onCheckedChange={(value) => onChange(value === true)}
            />
            <div className="flex flex-col gap-1">
                <Label htmlFor={fieldKey} className="font-medium">
                    {label}
                </Label>
                <p className="text-xs text-muted-foreground">{helpText}</p>
            </div>
        </div>
    );
}

interface SingleEnvelopeFormProps {
    defaults: SingleEnvelopeFormDefaults;
    jurisdiction: Jurisdiction;
    wrapper: TaxWrapper;
}

export default function SingleEnvelopeForm({ defaults, jurisdiction, wrapper }: SingleEnvelopeFormProps) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors, setError, clearErrors } = useForm<SingleEnvelopeFormValues>({
        ...defaults,
        name: '',
    });

    const wrapperLabel = t(`simulator.singleEnvelope.form.wrapperOptions.${wrapper}`);

    // Not a field of SingleEnvelopeFormValues: flashed by the controller
    // when the calculation itself fails, so it isn't tied to any one input.
    const simulationError = (errors as Record<string, string | undefined>).simulation;

    const submit = (e: SubmitEvent) => {
        e.preventDefault();

        if (data.name.trim() === '') {
            setError('name', t('simulator.form.nameRequired'));
            return;
        }

        post(route('simulators.single-envelope.run', { jurisdiction, wrapper }));
    };

    const suggestName = () => {
        setData('name', suggestSingleEnvelopeName(t, wrapper, data.years));
        clearErrors('name');
    };

    const renderField = (fieldKey: FormFieldKey) => {
        const config = FORM_FIELD_CONFIG[fieldKey];
        const label = t(`simulator.singleEnvelope.form.fields.${fieldKey}.label`);
        const helpText = t(`simulator.singleEnvelope.form.fields.${fieldKey}.helpText.${wrapper}`);
        const error = errors[fieldKey];

        if (config.control === 'amount') {
            return (
                <AmountField
                    key={fieldKey}
                    fieldKey={fieldKey}
                    label={label}
                    helpText={helpText}
                    value={data[fieldKey] as number}
                    step={config.step}
                    error={error}
                    onChange={(value) => setData(fieldKey, value)}
                />
            );
        }

        if (config.control === 'slider') {
            return (
                <SliderField
                    key={fieldKey}
                    fieldKey={fieldKey}
                    label={label}
                    helpText={helpText}
                    value={data[fieldKey] as number}
                    unit={config.unit}
                    step={config.step}
                    min={config.min}
                    max={config.max}
                    error={error}
                    onChange={(value) => setData(fieldKey, value)}
                />
            );
        }

        return (
            <CheckboxField
                key={fieldKey}
                fieldKey={fieldKey}
                label={label}
                helpText={helpText}
                checked={data[fieldKey] as boolean}
                onChange={(checked) => setData(fieldKey, checked)}
            />
        );
    };

    return (
        <form onSubmit={submit} className="grid gap-6 lg:grid-cols-3 lg:items-start">
            <div className="flex flex-col gap-6 lg:col-span-2">
                {simulationError && (
                    <p role="alert" className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">
                        {simulationError}
                    </p>
                )}

                {FORM_SECTIONS.map((section, index) => (
                    <Card key={section.titleKey}>
                        <CardHeader className="flex flex-row items-center gap-2">
                            <span className="font-mono text-xs text-brand">
                                {String(index + 1).padStart(2, '0')}
                            </span>
                            <h2 className="text-base font-semibold tracking-tight">
                                {t(`simulator.singleEnvelope.form.sections.${section.titleKey}`)}
                            </h2>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-6">
                            {index === 0 && (
                                <div className="flex flex-col gap-2">
                                    <div className="flex items-center justify-between gap-2">
                                        <Label htmlFor="name">
                                            {t('simulator.singleEnvelope.form.name')}
                                        </Label>
                                        <Button type="button" variant="ghost" size="sm" onClick={suggestName}>
                                            {t('simulator.singleEnvelope.form.suggestName.button')}
                                        </Button>
                                    </div>
                                    <Input
                                        id="name"
                                        name="name"
                                        type="text"
                                        maxLength={255}
                                        placeholder={t('simulator.singleEnvelope.form.namePlaceholder', {
                                            wrapper: wrapperLabel,
                                        })}
                                        value={data.name}
                                        aria-invalid={Boolean(errors.name)}
                                        onChange={(e) => {
                                            setData('name', e.target.value);
                                            clearErrors('name');
                                        }}
                                    />
                                    {errors.name && (
                                        <p className="text-xs text-destructive">{errors.name}</p>
                                    )}
                                </div>
                            )}

                            {groupFieldsForLayout(section.fields).map((group) =>
                                group.length > 1 ? (
                                    <div key={group.join('-')} className="grid gap-4 sm:grid-cols-2">
                                        {group.map(renderField)}
                                    </div>
                                ) : (
                                    renderField(group[0])
                                ),
                            )}
                        </CardContent>
                    </Card>
                ))}
            </div>

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="gap-2">
                        <span className="font-mono text-xs tracking-wide text-muted-foreground uppercase">
                            {t('simulator.singleEnvelope.form.summary.eyebrow')}
                        </span>
                        <p className="text-sm text-muted-foreground">
                            {t('simulator.singleEnvelope.form.summary.description')}
                        </p>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-4">
                        <dl className="flex flex-col gap-2.5 border-t border-border pt-4 text-sm">
                            <div className="flex items-center justify-between gap-4">
                                <dt className="text-muted-foreground">
                                    {t('simulator.singleEnvelope.form.summary.scenarioLabel')}
                                </dt>
                                <dd className="truncate font-medium">
                                    {data.name || t('simulator.singleEnvelope.form.summary.scenarioPlaceholder')}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between gap-4">
                                <dt className="text-muted-foreground">
                                    {t('simulator.singleEnvelope.form.fields.initialCapital.label')}
                                </dt>
                                <dd className="font-mono tabular-nums">
                                    {data.initialCapital} {t('form.currencyUnit')}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between gap-4">
                                <dt className="text-muted-foreground">
                                    {t('simulator.singleEnvelope.form.fields.monthlyContribution.label')}
                                </dt>
                                <dd className="font-mono tabular-nums">
                                    {data.monthlyContribution} {t('form.currencyUnit')}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between gap-4">
                                <dt className="text-muted-foreground">
                                    {t('simulator.singleEnvelope.form.fields.years.label')}
                                </dt>
                                <dd className="font-mono tabular-nums">
                                    {data.years} {t('form.yearsUnit', { count: data.years })}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between gap-4">
                                <dt className="text-muted-foreground">
                                    {t('simulator.singleEnvelope.form.fields.annualRate.label')}
                                </dt>
                                <dd className="font-mono tabular-nums">
                                    {data.annualRate} {t('form.percentUnit')}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between gap-4">
                                <dt className="text-muted-foreground">
                                    {t('simulator.singleEnvelope.form.fields.wrapperFee.label')}
                                </dt>
                                <dd className="font-mono tabular-nums">
                                    {data.wrapperFee} {t('form.percentUnit')}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between gap-4">
                                <dt className="text-muted-foreground">
                                    {t('simulator.singleEnvelope.form.fields.fundFee.label')}
                                </dt>
                                <dd className="font-mono tabular-nums">
                                    {data.fundFee} {t('form.percentUnit')}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between gap-4">
                                <dt className="text-muted-foreground">
                                    {t('simulator.singleEnvelope.form.fields.taxRate.label')}
                                </dt>
                                <dd className="font-mono tabular-nums">
                                    {data.taxRate} {t('form.percentUnit')}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between gap-4">
                                <dt className="text-muted-foreground">
                                    {t('simulator.singleEnvelope.form.fields.inflationRate.label')}
                                </dt>
                                <dd className="font-mono tabular-nums">
                                    {data.inflationEnabled
                                        ? `${data.inflationRate} ${t('form.percentUnit')}`
                                        : t('simulator.singleEnvelope.form.summary.inflationDisabled')}
                                </dd>
                            </div>
                        </dl>

                        <Button type="submit" variant="brand" size="lg" disabled={processing}>
                            {t('simulator.singleEnvelope.form.submit')}
                        </Button>

                        <p className="text-center text-xs text-muted-foreground">
                            {t('simulator.singleEnvelope.form.summary.retentionNote')}
                        </p>
                    </CardContent>
                </Card>

                <Card className="border-dashed">
                    <CardContent className="flex flex-col gap-3">
                        <span className="inline-flex w-fit items-center rounded-full border border-muted-foreground/35 px-2.5 py-1 font-mono text-[10px] tracking-wide text-muted-foreground uppercase">
                            {t('simulator.singleEnvelope.form.comparisonPromo.badge')}
                        </span>
                        <p className="text-sm text-muted-foreground">
                            {t('simulator.singleEnvelope.form.comparisonPromo.description')}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </form>
    );
}
