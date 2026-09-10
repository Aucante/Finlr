import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import i18n from '@/i18n';

vi.mock('@inertiajs/react');
vi.mock('axios', () => ({
    default: {
        post: vi.fn(),
        isAxiosError: (error: unknown) =>
            typeof error === 'object' && error !== null && 'response' in error,
    },
}));

import axios from 'axios';
import TwoFactorChallenge from '@/pages/Auth/TwoFactorChallenge';

describe('TwoFactorChallenge page', () => {
    beforeEach(async () => {
        await i18n.changeLanguage('fr');
        vi.mocked(axios.post).mockReset();
    });

    it('renders the code field, remember-device checkbox and submit button', () => {
        render(<TwoFactorChallenge />);

        expect(
            screen.getByLabelText(
                i18n.t('auth.twoFactorChallenge.codeLabel'),
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByLabelText(
                i18n.t('auth.twoFactorChallenge.rememberDevice'),
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', {
                name: i18n.t('auth.twoFactorChallenge.submit'),
            }),
        ).toBeInTheDocument();
    });

    it('only accepts digits in the code field, capped at 6 characters', async () => {
        const user = userEvent.setup();
        render(<TwoFactorChallenge />);

        const codeField = screen.getByLabelText(
            i18n.t('auth.twoFactorChallenge.codeLabel'),
        );
        await user.type(codeField, 'a1b2c3d4e5f6g7');

        expect(codeField).toHaveValue('123456');
    });

    it('disables the resend button and shows a cooldown after a 429 response', async () => {
        const user = userEvent.setup();
        vi.mocked(axios.post).mockRejectedValueOnce({
            response: { status: 429, headers: { 'retry-after': '42' } },
        });

        render(<TwoFactorChallenge />);

        const resendButton = screen.getByRole('button', {
            name: i18n.t('auth.twoFactorChallenge.resend'),
        });
        await user.click(resendButton);

        expect(
            await screen.findByRole('button', {
                name: i18n.t('auth.twoFactorChallenge.resendCooldown', {
                    seconds: 42,
                }),
            }),
        ).toBeDisabled();
    });

    it('shows a confirmation message after a successful resend', async () => {
        const user = userEvent.setup();
        vi.mocked(axios.post).mockResolvedValueOnce({ status: 200 });

        render(<TwoFactorChallenge />);

        await user.click(
            screen.getByRole('button', {
                name: i18n.t('auth.twoFactorChallenge.resend'),
            }),
        );

        expect(
            await screen.findByText(
                i18n.t('auth.twoFactorChallenge.resendSent'),
            ),
        ).toBeInTheDocument();
    });
});
