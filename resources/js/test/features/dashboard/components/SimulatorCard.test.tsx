import { render, screen } from '@testing-library/react';
import { PiggyBank } from 'lucide-react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import i18n from '@/i18n';

vi.mock('@inertiajs/react');

import SimulatorCard from '@/features/dashboard/components/SimulatorCard';

describe('SimulatorCard', () => {
    beforeEach(async () => {
        await i18n.changeLanguage('fr');
    });

    it('renders as a link to href when active', () => {
        render(
            <SimulatorCard
                icon={PiggyBank}
                title="Simulateur mono-enveloppe"
                description="Une description"
                state="active"
                href="/simulators/single-envelope"
            />,
        );

        expect(
            screen.getByRole('link', { name: /Simulateur mono-enveloppe/ }),
        ).toHaveAttribute('href', '/simulators/single-envelope');
    });

    it('renders the given icon', () => {
        const { container } = render(
            <SimulatorCard
                icon={PiggyBank}
                title="Simulateur mono-enveloppe"
                description="Une description"
                state="active"
                href="/simulators/single-envelope"
            />,
        );

        expect(container.querySelector('svg.lucide-piggy-bank')).toBeInTheDocument();
    });

    it('shows a locked badge and note, with no link, when locked', () => {
        render(
            <SimulatorCard
                icon={PiggyBank}
                title="Simulateur mono-enveloppe"
                description="Une description"
                state="locked"
                note="Passez premium"
            />,
        );

        expect(screen.getByText(i18n.t('dashboard.simulatorCard.lockedBadge'))).toBeInTheDocument();
        expect(screen.getByText('Passez premium')).toBeInTheDocument();
        expect(screen.queryByRole('link')).not.toBeInTheDocument();
    });

    it('shows a coming-soon badge, with no link, when comingSoon', () => {
        render(
            <SimulatorCard
                icon={PiggyBank}
                title="Simulateur multi-enveloppe"
                description="Une description"
                state="comingSoon"
            />,
        );

        expect(screen.getByText(i18n.t('dashboard.simulatorCard.comingSoonBadge'))).toBeInTheDocument();
        expect(screen.queryByRole('link')).not.toBeInTheDocument();
    });

    it('renders the coming-soon badge as plain informational text, not a button', () => {
        render(
            <SimulatorCard
                icon={PiggyBank}
                title="Simulateur multi-enveloppe"
                description="Une description"
                state="comingSoon"
            />,
        );

        const badge = screen.getByText(i18n.t('dashboard.simulatorCard.comingSoonBadge'));

        expect(badge.tagName).toBe('SPAN');
        expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });
});
