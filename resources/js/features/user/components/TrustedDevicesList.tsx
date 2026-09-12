import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';
import { formatDate } from '@/features/dashboard/lib/format';
import type { TrustedDevice } from '@/features/user/types';
import { FALLBACK_LOCALE } from '@/lib/currency';

interface TrustedDevicesListProps {
    devices: TrustedDevice[];
}

interface TrustedDeviceItemProps {
    device: TrustedDevice;
    locale: string;
}

/**
 * Same "· "-separated shape DeriveTrustedDeviceLabelAction produces
 * ("Chrome · Windows") — takes the first letter of each part, same
 * pattern as AccountStateCard's initials() for the account avatar.
 */
function initials(label: string): string {
    const parts = label.split('·').map((part) => part.trim()).filter(Boolean);

    if (parts.length === 0) {
        return '?';
    }

    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }

    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function TrustedDeviceItem({ device, locale }: TrustedDeviceItemProps) {
    const { t } = useTranslation();
    const { delete: destroy, processing } = useForm({});

    const label =
        device.label ??
        t('settings.security.twoFactor.trustedDevices.unknownDevice');

    const forget = () => {
        destroy(
            route('two-factor.trusted-devices.forget-one', {
                trustedDevice: String(device.id),
            }),
            { preserveScroll: true },
        );
    };

    return (
        <li className="flex items-center justify-between gap-3 rounded-lg border border-border bg-muted/30 p-3">
            <div className="flex min-w-0 items-center gap-3">
                <span
                    aria-hidden
                    className="flex size-9 shrink-0 items-center justify-center rounded-md bg-linear-to-b from-brand/80 to-brand font-mono text-xs text-brand-foreground"
                >
                    {initials(label)}
                </span>
                <div className="flex min-w-0 flex-col gap-1">
                    <span className="truncate text-sm font-medium text-foreground">
                        {label}
                    </span>
                    {device.isCurrent && (
                        <span className="w-fit rounded-full bg-brand/15 px-2 py-0.5 text-[10px] font-semibold tracking-wide text-brand uppercase">
                            {t(
                                'settings.security.twoFactor.trustedDevices.currentDevice',
                            )}
                        </span>
                    )}
                    <span className="font-mono text-xs text-muted-foreground">
                        {t(
                            'settings.security.twoFactor.trustedDevices.dates',
                            {
                                added: formatDate(device.createdAt, locale),
                                expires: formatDate(device.expiresAt, locale),
                            },
                        )}
                    </span>
                </div>
            </div>

            <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={forget}
                disabled={processing}
                aria-label={t(
                    'settings.security.twoFactor.trustedDevices.forgetOneLabel',
                    { device: label },
                )}
            >
                {t('settings.security.twoFactor.trustedDevices.forgetOne')}
            </Button>
        </li>
    );
}

export default function TrustedDevicesList({ devices }: TrustedDevicesListProps) {
    const { t, i18n } = useTranslation();
    const locale = i18n.resolvedLanguage ?? FALLBACK_LOCALE;

    return (
        <div className="flex flex-col gap-2">
            <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                {t('settings.security.twoFactor.trustedDevices.listTitle')}
            </span>

            {devices.length === 0 ? (
                <p className="py-2 text-sm text-muted-foreground">
                    {t('settings.security.twoFactor.trustedDevices.empty')}
                </p>
            ) : (
                <ul className="flex flex-col gap-2">
                    {devices.map((device) => (
                        <TrustedDeviceItem
                            key={device.id}
                            device={device}
                            locale={locale}
                        />
                    ))}
                </ul>
            )}
        </div>
    );
}
