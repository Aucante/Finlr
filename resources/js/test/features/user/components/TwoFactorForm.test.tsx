import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import i18n from '@/i18n';

vi.mock('@inertiajs/react');
vi.mock('axios', () => ({
    default: {
        post: vi.fn(),
        isAxiosError: () => false,
    },
}));

import TwoFactorForm from '@/features/user/components/TwoFactorForm';

describe('TwoFactorForm', () => {
    beforeEach(async () => {
        await i18n.changeLanguage('fr');
    });

    it('shows the disabled status and an enable button when inactive', () => {
        render(<TwoFactorForm enabled={false} status={null} trustedDevices={[]} />);

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

    it('shows the enabled status, a disable button and a distinct forget-devices button when active', () => {
        render(<TwoFactorForm enabled={true} status={null} trustedDevices={[]} />);

        expect(
            screen.getByText(
                i18n.t('settings.security.twoFactor.statusEnabled'),
            ),
        ).toBeInTheDocument();

        const disableButton = screen.getByRole('button', {
            name: i18n.t('settings.security.twoFactor.disable.button'),
        });
        const forgetButton = screen.getByRole('button', {
            name: i18n.t(
                'settings.security.twoFactor.trustedDevices.button',
            ),
        });

        expect(disableButton).toBeInTheDocument();
        expect(forgetButton).toBeInTheDocument();
        expect(disableButton).not.toBe(forgetButton);
    });

    it('reveals the confirmation code step when activation is requested, without switching the displayed status to enabled', async () => {
        const user = userEvent.setup();
        render(<TwoFactorForm enabled={false} status={null} trustedDevices={[]} />);

        await user.click(
            screen.getByRole('button', {
                name: i18n.t('settings.security.twoFactor.enableButton'),
            }),
        );

        expect(
            screen.getByLabelText(
                i18n.t('settings.security.twoFactor.activation.codeLabel'),
            ),
        ).toBeInTheDocument();
        // The parent's `enabled` prop only flips once the backend confirms
        // the code (a real page visit refreshes it) — this component never
        // flips it on its own just because the confirmation step opened.
        expect(
            screen.getByText(
                i18n.t('settings.security.twoFactor.statusDisabled'),
            ),
        ).toBeInTheDocument();
    });

    it('shows the disabled message inside the activation frame, next to the enable button, when two-factor was just disabled', () => {
        render(
            <TwoFactorForm
                enabled={false}
                status="two-factor-disabled"
                trustedDevices={[]}
            />,
        );

        expect(
            screen.getByText(
                i18n.t('settings.security.twoFactor.disabledMessage'),
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', {
                name: i18n.t('settings.security.twoFactor.enableButton'),
            }),
        ).toBeInTheDocument();
    });

    it('opens the disable confirmation modal requiring the current password', async () => {
        const user = userEvent.setup();
        render(<TwoFactorForm enabled={true} status={null} trustedDevices={[]} />);

        await user.click(
            screen.getByRole('button', {
                name: i18n.t('settings.security.twoFactor.disable.button'),
            }),
        );

        expect(
            screen.getByText(
                i18n.t('settings.security.twoFactor.disable.modalTitle'),
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByPlaceholderText(
                i18n.t('settings.security.twoFactor.disable.password'),
            ),
        ).toBeInTheDocument();
    });
});
