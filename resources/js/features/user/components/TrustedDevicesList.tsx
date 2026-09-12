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
        <li className="flex items-center justify-between gap-3 py-3">
            <div className="flex min-w-0 flex-col gap-0.5">
                <div className="flex flex-wrap items-center gap-2">
                    <span className="truncate text-sm font-medium text-foreground">
                        {label}
                    </span>
                    {device.isCurrent && (
                        <span className="rounded-full bg-brand/15 px-2 py-0.5 text-[10px] font-medium tracking-wide text-brand uppercase">
                            {t(
                                'settings.security.twoFactor.trustedDevices.currentDevice',
                            )}
                        </span>
                    )}
                </div>
                <span className="text-xs text-muted-foreground">
                    {t('settings.security.twoFactor.trustedDevices.dates', {
                        added: formatDate(device.createdAt, locale),
                        expires: formatDate(device.expiresAt, locale),
                    })}
                </span>
            </div>

            <Button
                type="button"
                variant="ghost"
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
        <div className="flex flex-col gap-1">
            <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                {t('settings.security.twoFactor.trustedDevices.listTitle')}
            </span>

            {devices.length === 0 ? (
                <p className="py-2 text-sm text-muted-foreground">
                    {t('settings.security.twoFactor.trustedDevices.empty')}
                </p>
            ) : (
                <ul className="flex flex-col divide-y divide-border">
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
