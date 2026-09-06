import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import i18n from '@/i18n';

vi.mock('@inertiajs/react');

import { router } from '@inertiajs/react';

import ScenarioList from '@/features/dashboard/components/ScenarioList';
import type { ScenarioSummary } from '@/features/dashboard/types';
import type { Paginated } from '@/types';

function paginate(
    data: ScenarioSummary[],
    overrides: Partial<Omit<Paginated<ScenarioSummary>, 'data'>> = {},
): Paginated<ScenarioSummary> {
    return {
        data,
        currentPage: 1,
        lastPage: 1,
        perPage: 10,
        total: data.length,
        ...overrides,
    };
}

describe('ScenarioList', () => {
    beforeEach(async () => {
        vi.mocked(router.get).mockClear();
        await i18n.changeLanguage('fr');
    });

    it('shows an explicit empty state when there are no scenarios', () => {
        render(<ScenarioList scenarios={paginate([], { total: 0 })} />);

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

        render(<ScenarioList scenarios={paginate(scenarios)} />);

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

        render(<ScenarioList scenarios={paginate(scenarios)} />);

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

        render(<ScenarioList scenarios={paginate(scenarios)} />);

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

        render(<ScenarioList scenarios={paginate(scenarios)} />);

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

        render(<ScenarioList scenarios={paginate(scenarios)} />);

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

        render(<ScenarioList scenarios={paginate(scenarios)} />);

        expect(screen.queryByText(i18n.t('dashboard.scenarioList.columns.wrapper'))).not.toBeInTheDocument();
        expect(screen.queryByText(i18n.t('dashboard.scenarioList.columns.amount'))).not.toBeInTheDocument();
        expect(screen.getByText(i18n.t('dashboard.scenarioList.columns.type'))).toBeInTheDocument();
        expect(screen.getByText(i18n.t('dashboard.scenarioList.columns.horizon'))).toBeInTheDocument();
    });

    it('shows the overall total, not just the current page size, in the count badge', () => {
        const scenarios: ScenarioSummary[] = [
            {
                id: 1,
                calculatorType: 'fire',
                typeLabel: 'dashboard.scenarioList.calculatorTypes.fire',
                headlineFigure: 1,
                createdAt: '2026-01-15T10:00:00.000000Z',
                wrapper: '',
                years: 1,
                name: 'A',
            },
        ];

        render(<ScenarioList scenarios={paginate(scenarios, { currentPage: 1, lastPage: 3, total: 25 })} />);

        expect(screen.getByText(i18n.t('dashboard.scenarioList.count', { count: 25 }))).toBeInTheDocument();
    });

    it('does not render pagination controls when there is only one page', () => {
        const scenarios: ScenarioSummary[] = [
            {
                id: 1,
                calculatorType: 'fire',
                typeLabel: 'dashboard.scenarioList.calculatorTypes.fire',
                headlineFigure: 1,
                createdAt: '2026-01-15T10:00:00.000000Z',
                wrapper: '',
                years: 1,
                name: 'A',
            },
        ];

        render(<ScenarioList scenarios={paginate(scenarios)} />);

        expect(screen.queryByRole('navigation')).not.toBeInTheDocument();
    });

    it('shows the page indicator and disables "previous" on the first page', () => {
        const scenarios: ScenarioSummary[] = [
            {
                id: 1,
                calculatorType: 'fire',
                typeLabel: 'dashboard.scenarioList.calculatorTypes.fire',
                headlineFigure: 1,
                createdAt: '2026-01-15T10:00:00.000000Z',
                wrapper: '',
                years: 1,
                name: 'A',
            },
        ];

        render(<ScenarioList scenarios={paginate(scenarios, { currentPage: 1, lastPage: 3, total: 25 })} />);

        expect(screen.getByText(i18n.t('dashboard.scenarioList.pagination.pageIndicator', { currentPage: 1, lastPage: 3 }))).toBeInTheDocument();
        expect(screen.getByRole('button', { name: i18n.t('dashboard.scenarioList.pagination.previous') })).toBeDisabled();
        expect(screen.getByRole('button', { name: i18n.t('dashboard.scenarioList.pagination.next') })).toBeEnabled();
    });

    it('requests the next page via an Inertia partial reload when "next" is clicked', async () => {
        const user = userEvent.setup();
        const scenarios: ScenarioSummary[] = [
            {
                id: 1,
                calculatorType: 'fire',
                typeLabel: 'dashboard.scenarioList.calculatorTypes.fire',
                headlineFigure: 1,
                createdAt: '2026-01-15T10:00:00.000000Z',
                wrapper: '',
                years: 1,
                name: 'A',
            },
        ];

        render(<ScenarioList scenarios={paginate(scenarios, { currentPage: 1, lastPage: 3, total: 25 })} />);

        await user.click(screen.getByRole('button', { name: i18n.t('dashboard.scenarioList.pagination.next') }));

        expect(router.get).toHaveBeenCalledWith(
            route('dashboard'),
            { page: 2 },
            expect.objectContaining({ preserveState: true, preserveScroll: true, only: ['scenarios'] }),
        );
    });
});
