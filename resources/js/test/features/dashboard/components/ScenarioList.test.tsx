import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import i18n from '@/i18n';

vi.mock('@inertiajs/react');

import ScenarioList from '@/features/dashboard/components/ScenarioList';
import type { ScenarioSummary } from '@/features/dashboard/types';

describe('ScenarioList', () => {
    beforeEach(async () => {
        await i18n.changeLanguage('fr');
    });

    it('shows an explicit empty state when there are no scenarios', () => {
        render(<ScenarioList scenarios={[]} />);

        expect(screen.getByText(i18n.t('dashboard.scenarioList.empty'))).toBeInTheDocument();
        expect(screen.queryByRole('list')).not.toBeInTheDocument();
        expect(screen.queryByRole('link')).not.toBeInTheDocument();
    });

    it('renders one row per scenario, linking to its detail page', () => {
        const scenarios: ScenarioSummary[] = [
            {
                id: 42,
                calculatorType: 'single_envelope',
                typeLabel: 'dashboard.scenarioList.calculatorTypes.single_envelope',
                headlineFigure: 31234.56,
                createdAt: '2026-01-15T10:00:00.000000Z',
                wrapper: 'pea',
                years: 15,
                name: 'Retraite à 62 ans',
            },
        ];

        render(<ScenarioList scenarios={scenarios} />);

        expect(screen.getByText('Retraite à 62 ans')).toBeInTheDocument();
        expect(screen.getByRole('link')).toHaveAttribute('href', route('scenarios.show', 42));
        expect(screen.queryByText(i18n.t('dashboard.scenarioList.empty'))).not.toBeInTheDocument();
    });

    it('describes the open action via an aria-label instead of a text column', () => {
        const scenarios: ScenarioSummary[] = [
            {
                id: 42,
                calculatorType: 'single_envelope',
                typeLabel: 'dashboard.scenarioList.calculatorTypes.single_envelope',
                headlineFigure: 31234.56,
                createdAt: '2026-01-15T10:00:00.000000Z',
                wrapper: 'pea',
                years: 15,
                name: 'Retraite à 62 ans',
            },
        ];

        render(<ScenarioList scenarios={scenarios} />);

        expect(
            screen.getByRole('link', {
                name: i18n.t('dashboard.scenarioList.openAriaLabel', { name: 'Retraite à 62 ans' }),
            }),
        ).toBeInTheDocument();
        expect(screen.queryByText('Ouvrir')).not.toBeInTheDocument();
    });

    it('falls back to the generic label when the scenario has no name', () => {
        const scenarios: ScenarioSummary[] = [
            {
                id: 42,
                calculatorType: 'single_envelope',
                typeLabel: 'dashboard.scenarioList.calculatorTypes.single_envelope',
                headlineFigure: 31234.56,
                createdAt: '2026-01-15T10:00:00.000000Z',
                wrapper: 'pea',
                years: 15,
                name: null,
            },
        ];

        render(<ScenarioList scenarios={scenarios} />);

        expect(screen.getByText(i18n.t('dashboard.scenarioList.genericLabel'))).toBeInTheDocument();
    });

    it('shows the translated simulator type and the horizon in years', () => {
        const scenarios: ScenarioSummary[] = [
            {
                id: 42,
                calculatorType: 'fire',
                typeLabel: 'dashboard.scenarioList.calculatorTypes.fire',
                headlineFigure: 31234.56,
                createdAt: '2026-01-15T10:00:00.000000Z',
                wrapper: '',
                years: 8,
                name: 'Achat résidence principale',
            },
        ];

        render(<ScenarioList scenarios={scenarios} />);

        expect(screen.getAllByText(i18n.t('dashboard.scenarioList.calculatorTypes.fire')).length).toBeGreaterThan(0);
        expect(screen.getAllByText('8 ans').length).toBeGreaterThan(0);
    });

    it('shows a dash for a horizon of zero years', () => {
        const scenarios: ScenarioSummary[] = [
            {
                id: 42,
                calculatorType: 'single_envelope',
                typeLabel: 'dashboard.scenarioList.calculatorTypes.single_envelope',
                headlineFigure: 31234.56,
                createdAt: '2026-01-15T10:00:00.000000Z',
                wrapper: '',
                years: 0,
                name: null,
            },
        ];

        render(<ScenarioList scenarios={scenarios} />);

        expect(screen.getAllByText('—').length).toBeGreaterThan(0);
    });

    it('does not render the Montant/Enveloppe columns anymore', () => {
        const scenarios: ScenarioSummary[] = [
            {
                id: 43,
                calculatorType: 'analogy',
                typeLabel: 'dashboard.scenarioList.calculatorTypes.analogy',
                headlineFigure: 12345.67,
                createdAt: '2026-01-15T10:00:00.000000Z',
                wrapper: 'PEA vs CTO',
                years: 15,
                name: null,
            },
        ];

        render(<ScenarioList scenarios={scenarios} />);

        expect(screen.queryByText(i18n.t('dashboard.scenarioList.columns.wrapper'))).not.toBeInTheDocument();
        expect(screen.queryByText(i18n.t('dashboard.scenarioList.columns.amount'))).not.toBeInTheDocument();
        expect(screen.getByText(i18n.t('dashboard.scenarioList.columns.type'))).toBeInTheDocument();
        expect(screen.getByText(i18n.t('dashboard.scenarioList.columns.horizon'))).toBeInTheDocument();
    });
});
