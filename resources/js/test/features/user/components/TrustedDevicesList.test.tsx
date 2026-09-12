import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import i18n from '@/i18n';

vi.mock('@inertiajs/react');

import * as inertia from '@inertiajs/react';
import TrustedDevicesList from '@/features/user/components/TrustedDevicesList';
import type { TrustedDevice } from '@/features/user/types';

const devices: TrustedDevice[] = [
    {
        id: 11,
        label: 'Chrome · Windows',
        createdAt: '2026-09-01T10:00:00.000Z',
        expiresAt: '2026-10-01T10:00:00.000Z',
        isCurrent: true,
    },
    {
        id: 12,
        label: null,
        createdAt: '2026-08-20T10:00:00.000Z',
        expiresAt: '2026-09-19T10:00:00.000Z',
        isCurrent: false,
    },
];

describe('TrustedDevicesList', () => {
    beforeEach(async () => {
        await i18n.changeLanguage('fr');
        vi.restoreAllMocks();
    });

    it('renders an empty state when there is no trusted device', () => {
        render(<TrustedDevicesList devices={[]} />);

        expect(
            screen.getByText(
                i18n.t('settings.security.twoFactor.trustedDevices.empty'),
            ),
        ).toBeInTheDocument();
        expect(screen.queryByRole('listitem')).not.toBeInTheDocument();
    });

    it('renders each device label, its dates, and a translated fallback for a missing label', () => {
        render(<TrustedDevicesList devices={devices} />);

        const items = screen.getAllByRole('listitem');
        expect(items).toHaveLength(2);

        expect(within(items[0]).getByText('Chrome · Windows')).toBeInTheDocument();
        expect(
            within(items[1]).getByText(
                i18n.t(
                    'settings.security.twoFactor.trustedDevices.unknownDevice',
                ),
            ),
        ).toBeInTheDocument();

        const formatter = new Intl.DateTimeFormat('fr', { dateStyle: 'long' });
        expect(
            within(items[0]).getByText(
                i18n.t('settings.security.twoFactor.trustedDevices.dates', {
                    added: formatter.format(new Date(devices[0].createdAt!)),
                    expires: formatter.format(new Date(devices[0].expiresAt)),
                }),
            ),
        ).toBeInTheDocument();
    });

    it('flags only the current browser device as "this device"', () => {
        render(<TrustedDevicesList devices={devices} />);

        const [current, other] = screen.getAllByRole('listitem');
        const badge = i18n.t(
            'settings.security.twoFactor.trustedDevices.currentDevice',
        );

        expect(within(current).getByText(badge)).toBeInTheDocument();
        expect(within(other).queryByText(badge)).not.toBeInTheDocument();
    });

    it('renders one forget button per device, targeting that device only', async () => {
        const destroy = vi.fn();
        vi.spyOn(inertia, 'useForm').mockReturnValue({
            delete: destroy,
            processing: false,
        } as unknown as ReturnType<typeof inertia.useForm>);

        const user = userEvent.setup();
        render(<TrustedDevicesList devices={devices} />);

        const buttons = screen.getAllByRole('button', {
            name: new RegExp(
                i18n.t('settings.security.twoFactor.trustedDevices.forgetOne'),
            ),
        });
        expect(buttons).toHaveLength(2);

        await user.click(
            screen.getByRole('button', {
                name: i18n.t(
                    'settings.security.twoFactor.trustedDevices.forgetOneLabel',
                    {
                        device: i18n.t(
                            'settings.security.twoFactor.trustedDevices.unknownDevice',
                        ),
                    },
                ),
            }),
        );

        expect(destroy).toHaveBeenCalledTimes(1);
        expect(destroy).toHaveBeenCalledWith(
            '/two-factor.trusted-devices.forget-one?trustedDevice=12',
            expect.objectContaining({ preserveScroll: true }),
        );
    });
});
