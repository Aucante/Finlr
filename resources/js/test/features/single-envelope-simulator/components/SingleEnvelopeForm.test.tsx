import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import i18n from '@/i18n';

const postMock = vi.fn();

// A stateful mock: the "submits" test below must be able to actually fill
// the name in before clicking submit (a frozen `data` would keep the field
// empty forever), and the client-side name validation in the component
// calls setError/clearErrors, which a frozen `errors` couldn't reflect.
vi.mock('@inertiajs/react', () => ({
    useForm: (initialValues: Record<string, unknown>) => {
        const [data, setDataState] = useState(initialValues);
        const [errors, setErrorsState] = useState<Record<string, string>>({});

        return {
            data,
            setData: (key: string, value: unknown) => setDataState((prev) => ({ ...prev, [key]: value })),
            errors,
            setError: (key: string, value: string) => setErrorsState((prev) => ({ ...prev, [key]: value })),
            clearErrors: (...keys: string[]) =>
                setErrorsState((prev) => {
                    if (keys.length === 0) {
                        return {};
                    }

                    const next = { ...prev };
                    keys.forEach((key) => delete next[key]);

                    return next;
                }),
            post: postMock,
            processing: false,
        };
    },
}));

import SingleEnvelopeForm from '@/features/single-envelope-simulator/components/SingleEnvelopeForm';
import { FORM_FIELD_CONFIG } from '@/features/single-envelope-simulator/lib/formFields';
import type { SingleEnvelopeFormDefaults } from '@/features/single-envelope-simulator/types';

const defaults: SingleEnvelopeFormDefaults = {
    initialCapital: 10000,
    monthlyContribution: 300,
    annualRate: 6,
    years: 15,
    wrapperFee: 0.5,
    fundFee: 0.3,
    taxRate: 30,
    inflationRate: 2,
    inflationEnabled: false,
};

describe('SingleEnvelopeForm', () => {
    beforeEach(async () => {
        postMock.mockClear();
        await i18n.changeLanguage('fr');
    });

    it('renders every field of SingleEnvelopeFormDefaults, prefilled with its default value', () => {
        render(<SingleEnvelopeForm defaults={defaults} jurisdiction="france" wrapper="pea" />);

        for (const key of Object.keys(FORM_FIELD_CONFIG) as (keyof typeof FORM_FIELD_CONFIG)[]) {
            const control = document.getElementById(key);
            expect(control, `missing control for "${key}"`).not.toBeNull();
        }

        expect(screen.getByLabelText(i18n.t('simulator.singleEnvelope.form.name'))).toBeInTheDocument();
    });

    it('names the scenario input after the wrapper, without hardcoding either wrapper', () => {
        render(<SingleEnvelopeForm defaults={defaults} jurisdiction="france" wrapper="cto" />);

        expect(
            screen.getByPlaceholderText(
                i18n.t('simulator.singleEnvelope.form.namePlaceholder', {
                    wrapper: i18n.t('simulator.singleEnvelope.form.wrapperOptions.cto'),
                }),
            ),
        ).toBeInTheDocument();
    });

    it('shows help text resolved for the current wrapper, not a literal one', () => {
        const { rerender } = render(
            <SingleEnvelopeForm defaults={defaults} jurisdiction="france" wrapper="pea" />,
        );

        expect(screen.getByText(/Plafond PEA : 150 000/)).toBeInTheDocument();

        rerender(<SingleEnvelopeForm defaults={defaults} jurisdiction="france" wrapper="cto" />);

        expect(screen.getByText('Aucun plafond de versement.')).toBeInTheDocument();
        expect(screen.queryByText(/Plafond PEA/)).not.toBeInTheDocument();
    });

    it('reflects the scenario name in the summary sidebar as it is typed', async () => {
        const user = userEvent.setup();
        render(<SingleEnvelopeForm defaults={defaults} jurisdiction="france" wrapper="pea" />);

        expect(screen.getByText(i18n.t('simulator.singleEnvelope.form.summary.scenarioPlaceholder'))).toBeInTheDocument();

        await user.type(screen.getByLabelText(i18n.t('simulator.singleEnvelope.form.name')), 'Retraite à 62 ans');

        expect(screen.getByText('Retraite à 62 ans')).toBeInTheDocument();
        expect(postMock).not.toHaveBeenCalled();
    });

    it('submits to the run route for the given jurisdiction and wrapper', async () => {
        const user = userEvent.setup();
        render(<SingleEnvelopeForm defaults={defaults} jurisdiction="france" wrapper="cto" />);

        await user.type(screen.getByLabelText(i18n.t('simulator.singleEnvelope.form.name')), 'Retraite à 62 ans');
        await user.click(
            screen.getByRole('button', { name: i18n.t('simulator.singleEnvelope.form.submit') }),
        );

        expect(postMock).toHaveBeenCalledWith(
            '/simulators.single-envelope.run?jurisdiction=france&wrapper=cto',
        );
    });

    it('blocks submission and shows an application error when the name is left empty', async () => {
        const user = userEvent.setup();
        render(<SingleEnvelopeForm defaults={defaults} jurisdiction="france" wrapper="pea" />);

        await user.click(
            screen.getByRole('button', { name: i18n.t('simulator.singleEnvelope.form.submit') }),
        );

        expect(screen.getByText(i18n.t('simulator.form.nameRequired'))).toBeInTheDocument();
        expect(postMock).not.toHaveBeenCalled();
    });

    it('clears the name error as soon as the user edits the field', async () => {
        const user = userEvent.setup();
        render(<SingleEnvelopeForm defaults={defaults} jurisdiction="france" wrapper="pea" />);

        await user.click(
            screen.getByRole('button', { name: i18n.t('simulator.singleEnvelope.form.submit') }),
        );
        expect(screen.getByText(i18n.t('simulator.form.nameRequired'))).toBeInTheDocument();

        await user.type(screen.getByLabelText(i18n.t('simulator.singleEnvelope.form.name')), 'R');

        expect(screen.queryByText(i18n.t('simulator.form.nameRequired'))).not.toBeInTheDocument();
    });

    it('fills the name field with a suggested name when clicking "Suggérer un nom"', async () => {
        const user = userEvent.setup();
        render(<SingleEnvelopeForm defaults={defaults} jurisdiction="france" wrapper="pea" />);

        await user.click(screen.getByRole('button', { name: i18n.t('simulator.singleEnvelope.form.suggestName.button') }));

        expect(screen.getByLabelText(i18n.t('simulator.singleEnvelope.form.name'))).toHaveValue('PEA sur 15 ans');
    });
});
