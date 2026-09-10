import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import i18n from '@/i18n';

vi.mock('@inertiajs/react');

import * as inertia from '@inertiajs/react';
import Edit from '@/pages/Settings/Edit';

const user = {
    id: 1,
    name: 'Jane Doe',
    email: 'jane@example.com',
    email_verified_at: '2024-01-01T00:00:00.000Z',
    two_factor_enabled: false,
};

const defaultProps = {
    mustVerifyEmail: true,
    status: null,
    memberSince: '2024-01-01T00:00:00.000Z',
    profileUpdatedAt: '2024-06-01T00:00:00.000Z',
    scenariosCount: 3,
};

describe('Settings Edit page', () => {
    beforeEach(async () => {
        await i18n.changeLanguage('fr');
        vi.spyOn(inertia, 'usePage').mockReturnValue({
            url: '/settings',
            props: { auth: { user, plan: 'free', permissions: [] } },
        } as unknown as ReturnType<typeof inertia.usePage>);
    });

    it('renders the personal information, security, simulation preferences and danger zone sections', () => {
        render(<Edit {...defaultProps} />);

        expect(
            screen.getByText(i18n.t('settings.information.title')),
        ).toBeInTheDocument();
        expect(
            screen.getByText(i18n.t('settings.security.title')),
        ).toBeInTheDocument();
        expect(
            screen.getByText(i18n.t('settings.simulationPreferences.title')),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', {
                name: i18n.t('settings.dangerZone.button'),
            }),
        ).toBeInTheDocument();
    });

    it('renders the two-factor authentication section with its current status', () => {
        render(<Edit {...defaultProps} />);

        expect(
            screen.getByText(
                i18n.t('settings.security.twoFactor.title'),
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                i18n.t('settings.security.twoFactor.statusDisabled'),
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', {
                name: i18n.t('settings.security.twoFactor.enableButton'),
            }),
        ).toBeInTheDocument();
    });

    it('renders the account state card with plan, member since and scenario count', () => {
        render(<Edit {...defaultProps} />);

        expect(
            screen.getByText(i18n.t('settings.account.title')),
        ).toBeInTheDocument();
        expect(screen.getAllByText('Jane Doe').length).toBeGreaterThan(0);
        expect(screen.getByText('jane@example.com')).toBeInTheDocument();
        expect(screen.getByText(i18n.t('settings.account.plans.free'))).toBeInTheDocument();
        expect(screen.getByText('3')).toBeInTheDocument();
    });

    it('reflects a disabled two-factor status in the account state card', () => {
        render(<Edit {...defaultProps} />);

        expect(
            screen.getByText(i18n.t('settings.account.twoFactorDisabled')),
        ).toBeInTheDocument();
    });

    it('reflects an enabled two-factor status in the account state card', () => {
        vi.spyOn(inertia, 'usePage').mockReturnValue({
            url: '/settings',
            props: {
                auth: {
                    user: { ...user, two_factor_enabled: true },
                    plan: 'free',
                    permissions: [],
                },
            },
        } as unknown as ReturnType<typeof inertia.usePage>);

        render(<Edit {...defaultProps} />);

        expect(
            screen.getByText(i18n.t('settings.account.twoFactorEnabled')),
        ).toBeInTheDocument();
    });

    it('renders a logout link inside the account state card', () => {
        render(<Edit {...defaultProps} />);

        expect(
            screen.getByRole('link', { name: i18n.t('settings.account.logout') }),
        ).toHaveAttribute('href', '/logout');
    });

    it('prefills the name and email fields from the current user', () => {
        render(<Edit {...defaultProps} />);

        expect(
            screen.getByLabelText(i18n.t('settings.information.name')),
        ).toHaveValue('Jane Doe');
        expect(
            screen.getByLabelText(i18n.t('settings.information.email')),
        ).toHaveValue('jane@example.com');
    });

    it('opens the delete account confirmation modal', async () => {
        const testUser = userEvent.setup();
        render(<Edit {...defaultProps} />);

        await testUser.click(
            screen.getByRole('button', {
                name: i18n.t('settings.dangerZone.button'),
            }),
        );

        expect(
            screen.getByText(i18n.t('settings.dangerZone.confirmTitle')),
        ).toBeInTheDocument();
    });
});
