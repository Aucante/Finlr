import { useForm } from '@inertiajs/react';
import type { SubmitEvent } from 'react';
import { useTranslation } from 'react-i18next';

import AmountField from '@/components/form/AmountField';
import SliderField from '@/components/form/SliderField';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { FIELD_CONFIG, FIELD_ORDER, type FieldKey } from '@/features/fire-simulator/lib/fields';
import type { FireFormValues, FireSimulatorPageProps } from '@/features/fire-simulator/types';

type FireFormProps = FireSimulatorPageProps;

export default function FireForm({ defaults }: FireFormProps) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<FireFormValues>({
        ...defaults,
        name: '',
    });

    // "simulation" is flashed by the controller when the calculation
    // itself fails, or when the package's own invariant validation rejects
    // the input (RunFireProjectionController) — not tied to any one field.
    const simulationError = (errors as Record<string, string | undefined>).simulation;

    const submit = (e: SubmitEvent) => {
        e.preventDefault();
        post(route('simulators.fire.run'));
    };

    const renderField = (fieldKey: FieldKey) => {
        const config = FIELD_CONFIG[fieldKey];
        const label = t(`simulator.fire.form.fields.${fieldKey}.label`);
        const helpText = t(`simulator.fire.form.fields.${fieldKey}.helpText`);
        const error = errors[fieldKey];

        if (config.control === 'amount') {
            return (
                <AmountField
                    key={fieldKey}
                    fieldKey={fieldKey}
                    label={label}
                    helpText={helpText}
                    value={data[fieldKey]}
                    step={config.step}
                    error={error}
                    onChange={(value) => setData(fieldKey, value)}
                />
            );
        }

        return (
            <SliderField
                key={fieldKey}
                fieldKey={fieldKey}
                label={label}
                helpText={helpText}
                value={data[fieldKey]}
                unit={config.unit}
                step={config.step}
                min={config.min}
                max={config.max}
                error={error}
                onChange={(value) => setData(fieldKey, value)}
            />
        );
    };

    return (
        <form onSubmit={submit} className="flex flex-col gap-6">
            {simulationError && (
                <p role="alert" className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">
                    {simulationError}
                </p>
            )}

            <Card>
                <CardHeader className="flex flex-row items-center gap-2">
                    <span className="font-mono text-xs text-brand">01</span>
                    <h2 className="text-base font-semibold tracking-tight">
                        {t('simulator.fire.form.sections.inputs')}
                    </h2>
                </CardHeader>
                <CardContent className="flex flex-col gap-6">
                    <div className="flex flex-col gap-2">
                        <Label htmlFor="name">{t('simulator.fire.form.name')}</Label>
                        <Input
                            id="name"
                            name="name"
                            type="text"
                            required
                            maxLength={255}
                            placeholder={t('simulator.fire.form.namePlaceholder')}
                            value={data.name}
                            aria-invalid={Boolean(errors.name)}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">{FIELD_ORDER.map(renderField)}</div>
                </CardContent>
            </Card>

            <div className="flex flex-col items-center gap-3">
                <Button type="submit" variant="brand" size="lg" disabled={processing}>
                    {t('simulator.fire.form.submit')}
                </Button>
                <p className="text-center text-xs text-muted-foreground">
                    {t('simulator.fire.form.retentionNote')}
                </p>
            </div>
        </form>
    );
}
