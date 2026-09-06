import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import i18n from '@/i18n';

const postMock = vi.fn();

// A stateful mock: name is required now (native `required` attribute), so
// the "submits" test below must be able to actually fill it in before
// clicking submit — a frozen `data` would keep the field empty forever.
vi.mock('@inertiajs/react', () => ({
    useForm: (initialValues: Record<string, unknown>) => {
        const [data, setDataState] = useState(initialValues);

        return {
            data,
            setData: (key: string, value: unknown) => setDataState((prev) => ({ ...prev, [key]: value })),
            post: postMock,
            processing: false,
            errors: {},
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
});
