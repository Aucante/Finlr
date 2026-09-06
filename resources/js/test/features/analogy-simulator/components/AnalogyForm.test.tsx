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

import AnalogyForm from '@/features/analogy-simulator/components/AnalogyForm';
import type { AccountType, AnalogySharedFormValues } from '@/features/analogy-simulator/types';

const accountTypes: AccountType[] = ['PEA', 'PEA_PME', 'CTO', 'ASSURANCE_VIE', 'CAT', 'LIVRET_A', 'LDDS', 'COMPTE_COURANT'];

const defaults: AnalogySharedFormValues = {
    initialAmount: 0,
    monthlyContribution: 1000,
    durationYears: 20,
    annualReturnRate: 6,
    managementFeeRate: 0,
    inflationRate: 2,
};

describe('AnalogyForm', () => {
    beforeEach(async () => {
        postMock.mockClear();
        await i18n.changeLanguage('fr');
    });

    it('renders both scenario columns, defaulting to different account types', () => {
        render(<AnalogyForm defaults={defaults} accountTypes={accountTypes} />);

        expect(screen.getByText(i18n.t('simulator.analogy.form.scenarioA'))).toBeInTheDocument();
        expect(screen.getByText(i18n.t('simulator.analogy.form.scenarioB'))).toBeInTheDocument();
        // Radix's Select renders a hidden native <select> fallback listing
        // every option (not just the selected one), so a bare getByText
        // would also match those hidden <option> nodes — restricted to the
        // trigger's visible value span.
        expect(
            screen.getByText(i18n.t('simulator.analogy.accountTypes.PEA'), { selector: '[data-slot="select-value"]' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(i18n.t('simulator.analogy.accountTypes.CTO'), { selector: '[data-slot="select-value"]' }),
        ).toBeInTheDocument();
    });

    it('renders the shared block fields once, prefilled from the given defaults', () => {
        render(<AnalogyForm defaults={defaults} accountTypes={accountTypes} />);

        expect(
            screen.getByLabelText(i18n.t('simulator.analogy.form.fields.monthlyContribution.label')),
        ).toHaveValue(1000);
        expect(
            screen.getAllByLabelText(i18n.t('simulator.analogy.form.fields.monthlyContribution.label')),
        ).toHaveLength(1);
    });

    it('submits to the analogy run route', async () => {
        const user = userEvent.setup();
        render(<AnalogyForm defaults={defaults} accountTypes={accountTypes} />);

        await user.type(screen.getByLabelText(i18n.t('simulator.analogy.form.name')), 'PEA vs CTO à 20 ans');
        await user.click(screen.getByRole('button', { name: i18n.t('simulator.analogy.form.submit') }));

        expect(postMock).toHaveBeenCalledWith('/simulators.analogy.run');
    });

    it('blocks submission and shows an application error when the name is left empty', async () => {
        const user = userEvent.setup();
        render(<AnalogyForm defaults={defaults} accountTypes={accountTypes} />);

        await user.click(screen.getByRole('button', { name: i18n.t('simulator.analogy.form.submit') }));

        expect(screen.getByText(i18n.t('simulator.form.nameRequired'))).toBeInTheDocument();
        expect(postMock).not.toHaveBeenCalled();
    });

    it('clears the name error as soon as the user edits the field', async () => {
        const user = userEvent.setup();
        render(<AnalogyForm defaults={defaults} accountTypes={accountTypes} />);

        await user.click(screen.getByRole('button', { name: i18n.t('simulator.analogy.form.submit') }));
        expect(screen.getByText(i18n.t('simulator.form.nameRequired'))).toBeInTheDocument();

        await user.type(screen.getByLabelText(i18n.t('simulator.analogy.form.name')), 'P');

        expect(screen.queryByText(i18n.t('simulator.form.nameRequired'))).not.toBeInTheDocument();
    });

    it('fills the name field with a suggested name when clicking "Suggérer un nom"', async () => {
        const user = userEvent.setup();
        render(<AnalogyForm defaults={defaults} accountTypes={accountTypes} />);

        await user.click(screen.getByRole('button', { name: i18n.t('simulator.analogy.form.suggestName.button') }));

        expect(screen.getByLabelText(i18n.t('simulator.analogy.form.name'))).toHaveValue('PEA vs CTO');
    });
});
