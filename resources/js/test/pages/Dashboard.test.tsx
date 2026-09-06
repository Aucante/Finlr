import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import i18n from '@/i18n';

vi.mock('@inertiajs/react');

import * as inertia from '@inertiajs/react';
import Dashboard from '@/pages/Dashboard';
import type { ScenarioSummary } from '@/features/dashboard/types';
import type { Paginated } from '@/types';

function paginate(data: ScenarioSummary[]): Paginated<ScenarioSummary> {
    return { data, currentPage: 1, lastPage: 1, perPage: 10, total: data.length };
}

const emptyScenarios = paginate([]);

function mockAuth(permissions: string[]) {
    vi.spyOn(inertia, 'usePage').mockReturnValue({
        url: '/dashboard',
        props: {
            auth: {
                user: {
                    id: 1,
                    name: 'Jane Doe',
                    email: 'jane@example.com',
                    email_verified_at: null,
                },
                plan: 'pro_monthly',
                permissions,
            },
        },
    } as unknown as ReturnType<typeof inertia.usePage>);
}

describe('Dashboard page', () => {
    beforeEach(async () => {
        vi.restoreAllMocks();
        await i18n.changeLanguage('fr');
    });

    it('renders a personalized greeting and description', () => {
        mockAuth(['advanced_calculator']);

        render(<Dashboard scenarios={emptyScenarios} />);

        expect(
            screen.getByRole('heading', {
                name: i18n.t('dashboard.greeting', { name: 'Jane Doe' }),
            }),
        ).toBeInTheDocument();
        expect(screen.getByText(i18n.t('dashboard.description'))).toBeInTheDocument();
    });

    it('shows a single, always-active "new simulation" action button', () => {
        mockAuth([]);

        render(<Dashboard scenarios={emptyScenarios} />);

        const button = screen.getByRole('link', { name: i18n.t('dashboard.newSimulation') });
        expect(button).toHaveAttribute('href', route('simulators.index'));
        expect(button).toHaveAttribute('data-size', 'lg');
        expect(screen.queryByText(/importer un portefeuille/i)).not.toBeInTheDocument();
    });

    it('shows an active link to the single-envelope simulator when the user has the permission', () => {
        mockAuth(['advanced_calculator']);

        render(<Dashboard scenarios={emptyScenarios} />);

        expect(
            screen.getByRole('link', { name: new RegExp(i18n.t('dashboard.simulators.singleEnvelope.title')) }),
        ).toHaveAttribute('href', route('simulators.single-envelope.choose'));
    });

    it('shows a locked single-envelope card with no link when the user lacks the permission', () => {
        mockAuth([]);

        render(<Dashboard scenarios={emptyScenarios} />);

        expect(screen.getByText(i18n.t('dashboard.simulators.singleEnvelope.title'))).toBeInTheDocument();
        // Both cards are locked without the permission, so the badge appears twice.
        expect(screen.getAllByText(i18n.t('dashboard.simulatorCard.lockedBadge')).length).toBeGreaterThan(0);
        expect(
            screen.queryByRole('link', { name: new RegExp(i18n.t('dashboard.simulators.singleEnvelope.title')) }),
        ).not.toBeInTheDocument();
    });

    it('shows an active link to the multi-envelope simulator when the user has the permission', () => {
        mockAuth(['advanced_calculator']);

        render(<Dashboard scenarios={emptyScenarios} />);

        expect(
            screen.getByRole('link', { name: new RegExp(i18n.t('dashboard.simulators.multiEnvelope.title')) }),
        ).toHaveAttribute('href', route('simulators.multi-envelope.show'));
    });

    it('shows a locked multi-envelope card with no link when the user lacks the permission', () => {
        mockAuth([]);

        render(<Dashboard scenarios={emptyScenarios} />);

        expect(screen.getByText(i18n.t('dashboard.simulators.multiEnvelope.title'))).toBeInTheDocument();
        expect(screen.getAllByText(i18n.t('dashboard.simulatorCard.lockedBadge')).length).toBeGreaterThan(0);
        expect(
            screen.queryByRole('link', { name: new RegExp(i18n.t('dashboard.simulators.multiEnvelope.title')) }),
        ).not.toBeInTheDocument();
    });

    it('shows an active link to the analogy simulator when the user has the permission', () => {
        mockAuth(['advanced_calculator']);

        render(<Dashboard scenarios={emptyScenarios} />);

        expect(
            screen.getByRole('link', { name: new RegExp(i18n.t('dashboard.simulators.analogy.title')) }),
        ).toHaveAttribute('href', route('simulators.analogy.show'));
    });

    it('shows a locked analogy card with no link when the user lacks the permission', () => {
        mockAuth([]);

        render(<Dashboard scenarios={emptyScenarios} />);

        expect(screen.getByText(i18n.t('dashboard.simulators.analogy.title'))).toBeInTheDocument();
        expect(screen.getAllByText(i18n.t('dashboard.simulatorCard.lockedBadge')).length).toBeGreaterThan(0);
        expect(
            screen.queryByRole('link', { name: new RegExp(i18n.t('dashboard.simulators.analogy.title')) }),
        ).not.toBeInTheDocument();
    });

    it('shows an active link to the fire simulator when the user has the permission', () => {
        mockAuth(['advanced_calculator']);

        render(<Dashboard scenarios={emptyScenarios} />);

        expect(
            screen.getByRole('link', { name: new RegExp(i18n.t('dashboard.simulators.fire.title')) }),
        ).toHaveAttribute('href', route('simulators.fire.show'));
    });

    it('shows a locked fire card with no link when the user lacks the permission', () => {
        mockAuth([]);

        render(<Dashboard scenarios={emptyScenarios} />);

        expect(screen.getByText(i18n.t('dashboard.simulators.fire.title'))).toBeInTheDocument();
        expect(screen.getAllByText(i18n.t('dashboard.simulatorCard.lockedBadge')).length).toBeGreaterThan(0);
        expect(
            screen.queryByRole('link', { name: new RegExp(i18n.t('dashboard.simulators.fire.title')) }),
        ).not.toBeInTheDocument();
    });

    it('renders the scenarios received in props, falling back to the generic label when unnamed', () => {
        mockAuth(['advanced_calculator']);
        const scenarios: ScenarioSummary[] = [
            {
                id: 1,
                calculatorType: 'single_envelope',
                typeLabel: 'dashboard.scenarioList.calculatorTypes.single_envelope',
                headlineFigure: 31234.56,
                createdAt: '2026-01-15T10:00:00.000000Z',
                wrapper: 'pea',
                years: 15,
                name: null,
            },
        ];

        render(<Dashboard scenarios={paginate(scenarios)} />);

        expect(screen.getByText(i18n.t('dashboard.scenarioList.genericLabel'))).toBeInTheDocument();
    });

    it('never renders a euro amount in the promo block', () => {
        mockAuth(['advanced_calculator']);

        render(<Dashboard scenarios={emptyScenarios} />);

        expect(screen.getByText(i18n.t('dashboard.promo.title'))).toBeInTheDocument();
        expect(screen.getByRole('button', { name: i18n.t('dashboard.promo.cta') })).toBeDisabled();
        expect(screen.queryByText(/€/)).not.toBeInTheDocument();
    });

    it('renders the promo "coming soon" badge as plain text, not a second button', () => {
        mockAuth(['advanced_calculator']);

        render(<Dashboard scenarios={emptyScenarios} />);

        const badges = screen.getAllByText(i18n.t('dashboard.simulatorCard.comingSoonBadge'));
        expect(badges.some((badge) => badge.tagName === 'SPAN')).toBe(true);
        expect(
            screen.queryByRole('button', { name: i18n.t('dashboard.simulatorCard.comingSoonBadge') }),
        ).not.toBeInTheDocument();
    });
});
