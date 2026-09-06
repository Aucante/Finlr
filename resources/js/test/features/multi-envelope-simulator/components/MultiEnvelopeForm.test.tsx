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

import MultiEnvelopeForm from '@/features/multi-envelope-simulator/components/MultiEnvelopeForm';
import type { AccountType } from '@/features/multi-envelope-simulator/types';

const accountTypes: AccountType[] = ['PEA', 'PEA_PME', 'CTO', 'ASSURANCE_VIE', 'CAT', 'LIVRET_A', 'LDDS', 'COMPTE_COURANT'];

const defaults = {
    initialAmount: 0,
    monthlyContribution: 300,
    durationYears: 15,
    annualReturnRate: 6,
    managementFeeRate: 0.5,
    inflationRate: 2,
};

describe('MultiEnvelopeForm', () => {
    beforeEach(async () => {
        postMock.mockClear();
        await i18n.changeLanguage('fr');
    });

    it('starts with two envelope rows, prefilled from the given defaults', () => {
        render(<MultiEnvelopeForm defaults={defaults} accountTypes={accountTypes} />);

        expect(screen.getByText(i18n.t('simulator.multiEnvelope.form.envelopeLabel', { index: 1 }))).toBeInTheDocument();
        expect(screen.getByText(i18n.t('simulator.multiEnvelope.form.envelopeLabel', { index: 2 }))).toBeInTheDocument();
        expect(screen.queryByText(i18n.t('simulator.multiEnvelope.form.envelopeLabel', { index: 3 }))).not.toBeInTheDocument();

        const monthlyContributionFields = screen.getAllByLabelText(
            i18n.t('simulator.multiEnvelope.form.fields.monthlyContribution.label'),
        );
        expect(monthlyContributionFields).toHaveLength(2);
        expect(monthlyContributionFields[0]).toHaveValue(300);
    });

    it('renders the shared inflation field once, not per envelope', () => {
        render(<MultiEnvelopeForm defaults={defaults} accountTypes={accountTypes} />);

        const inflationField = screen.getByLabelText(i18n.t('simulator.multiEnvelope.form.fields.inflationRate.label'));
        expect(inflationField).toHaveValue(2);
        expect(inflationField).toHaveAttribute('max', '50');
    });

    it('renders the add-envelope button below the envelope list, in brand green', () => {
        render(<MultiEnvelopeForm defaults={defaults} accountTypes={accountTypes} />);

        const addButton = screen.getByRole('button', { name: i18n.t('simulator.multiEnvelope.form.addEnvelope') });
        expect(addButton).toHaveAttribute('data-variant', 'brand');

        const envelopeTwoLabel = screen.getByText(i18n.t('simulator.multiEnvelope.form.envelopeLabel', { index: 2 }));
        expect(envelopeTwoLabel.compareDocumentPosition(addButton) & Node.DOCUMENT_POSITION_FOLLOWING).toBeTruthy();
    });

    it('renders the remove-envelope icon in the destructive (red) variant', () => {
        render(<MultiEnvelopeForm defaults={defaults} accountTypes={accountTypes} />);

        const removeButton = screen.getByRole('button', {
            name: i18n.t('simulator.multiEnvelope.form.removeEnvelope', { index: 1 }),
        });
        expect(removeButton).toHaveAttribute('data-variant', 'destructive');
    });

    it('reflects the scenario name placeholder in the summary sidebar', () => {
        render(<MultiEnvelopeForm defaults={defaults} accountTypes={accountTypes} />);

        expect(
            screen.getByText(i18n.t('simulator.multiEnvelope.form.summary.scenarioPlaceholder')),
        ).toBeInTheDocument();
    });

    it('submits to the multi-envelope run route', async () => {
        const user = userEvent.setup();
        render(<MultiEnvelopeForm defaults={defaults} accountTypes={accountTypes} />);

        await user.type(screen.getByLabelText(i18n.t('simulator.multiEnvelope.form.name')), 'Cascade PEA + CTO');
        await user.click(screen.getByRole('button', { name: i18n.t('simulator.multiEnvelope.form.submit') }));

        expect(postMock).toHaveBeenCalledWith('/simulators.multi-envelope.run');
    });
});
