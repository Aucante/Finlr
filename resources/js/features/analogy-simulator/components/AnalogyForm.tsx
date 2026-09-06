import { useForm } from '@inertiajs/react';
import type { SubmitEvent } from 'react';
import { useTranslation } from 'react-i18next';

import AmountField from '@/components/form/AmountField';
import SliderField from '@/components/form/SliderField';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ScenarioColumn from '@/features/analogy-simulator/components/ScenarioColumn';
import { SHARED_FIELD_CONFIG, SHARED_FIELD_ORDER, type SharedFieldKey } from '@/features/analogy-simulator/lib/sharedFields';
import { suggestAnalogyName } from '@/features/analogy-simulator/lib/suggestName';
import type { AccountType, AnalogyFormValues, AnalogySimulatorPageProps } from '@/features/analogy-simulator/types';

type AnalogyFormProps = AnalogySimulatorPageProps;

export default function AnalogyForm({ defaults, accountTypes }: AnalogyFormProps) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors, setError, clearErrors } = useForm<AnalogyFormValues>({
        ...defaults,
        name: '',
        accountTypeA: accountTypes[0],
        labelA: '',
        accountTypeB: accountTypes[2] ?? accountTypes[0],
        labelB: '',
    });

    // "simulation" is flashed by the controller when the calculation
    // itself fails, not tied to any one input field.
    const simulationError = (errors as Record<string, string | undefined>).simulation;

    const submit = (e: SubmitEvent) => {
        e.preventDefault();

        if (data.name.trim() === '') {
            setError('name', t('simulator.form.nameRequired'));
            return;
        }

        post(route('simulators.analogy.run'));
    };

    const suggestName = () => {
        setData('name', suggestAnalogyName(t, data.accountTypeA, data.accountTypeB));
        clearErrors('name');
    };

    const renderSharedField = (fieldKey: SharedFieldKey) => {
        const config = SHARED_FIELD_CONFIG[fieldKey];
        const label = t(`simulator.analogy.form.fields.${fieldKey}.label`);
        const helpText = t(`simulator.analogy.form.fields.${fieldKey}.helpText`);
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
                        {t('simulator.analogy.form.sections.shared')}
                    </h2>
                </CardHeader>
                <CardContent className="flex flex-col gap-6">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center justify-between gap-2">
                            <Label htmlFor="name">{t('simulator.analogy.form.name')}</Label>
                            <Button type="button" variant="ghost" size="sm" onClick={suggestName}>
                                {t('simulator.analogy.form.suggestName.button')}
                            </Button>
                        </div>
                        <Input
                            id="name"
                            name="name"
                            type="text"
                            maxLength={255}
                            placeholder={t('simulator.analogy.form.namePlaceholder')}
                            value={data.name}
                            aria-invalid={Boolean(errors.name)}
                            onChange={(e) => {
                                setData('name', e.target.value);
                                clearErrors('name');
                            }}
                        />
                        {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">{SHARED_FIELD_ORDER.map(renderSharedField)}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center gap-2">
                    <span className="font-mono text-xs text-brand">02</span>
                    <h2 className="text-base font-semibold tracking-tight">
                        {t('simulator.analogy.form.sections.scenarios')}
                    </h2>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <ScenarioColumn
                        side="A"
                        accountTypes={accountTypes}
                        accountType={data.accountTypeA}
                        label={data.labelA}
                        accountTypeError={errors.accountTypeA}
                        labelError={errors.labelA}
                        onAccountTypeChange={(accountType: AccountType) => setData('accountTypeA', accountType)}
                        onLabelChange={(label) => setData('labelA', label)}
                    />
                    <ScenarioColumn
                        side="B"
                        accountTypes={accountTypes}
                        accountType={data.accountTypeB}
                        label={data.labelB}
                        accountTypeError={errors.accountTypeB}
                        labelError={errors.labelB}
                        onAccountTypeChange={(accountType: AccountType) => setData('accountTypeB', accountType)}
                        onLabelChange={(label) => setData('labelB', label)}
                    />
                </CardContent>
            </Card>

            <div className="flex flex-col items-center gap-3">
                <Button type="submit" variant="brand" size="lg" disabled={processing}>
                    {t('simulator.analogy.form.submit')}
                </Button>
                <p className="text-center text-xs text-muted-foreground">
                    {t('simulator.analogy.form.retentionNote')}
                </p>
            </div>
        </form>
    );
}
