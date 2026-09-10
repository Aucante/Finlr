import { useTranslation } from 'react-i18next';

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import ForgetTrustedDevicesButton from '@/features/user/components/ForgetTrustedDevicesButton';
import TwoFactorActivationForm from '@/features/user/components/TwoFactorActivationForm';
import TwoFactorDisableDialog from '@/features/user/components/TwoFactorDisableDialog';
import { cn } from '@/lib/utils';

interface TwoFactorFormProps {
    enabled: boolean;
    status?: string | null;
}

export default function TwoFactorForm({ enabled, status }: TwoFactorFormProps) {
    const { t } = useTranslation();

    return (
        <Card className="gap-0 py-0">
            <CardHeader className="gap-1.5 border-b border-border py-5">
                <div className="flex items-baseline justify-between gap-2">
                    <div className="flex items-baseline gap-2">
                        <span
                            aria-hidden
                            className="font-mono text-xs text-brand"
                        >
                            03
                        </span>
                        <CardTitle className="text-base">
                            {t('settings.security.twoFactor.title')}
                        </CardTitle>
                    </div>
                    <span
                        className={cn(
                            'rounded-full px-2 py-0.5 text-[10px] font-medium tracking-wide uppercase',
                            enabled
                                ? 'bg-brand/15 text-brand'
                                : 'bg-muted text-muted-foreground',
                        )}
                    >
                        {t(
                            enabled
                                ? 'settings.security.twoFactor.statusEnabled'
                                : 'settings.security.twoFactor.statusDisabled',
                        )}
                    </span>
                </div>
                <CardDescription>
                    {t('settings.security.twoFactor.description')}
                </CardDescription>
            </CardHeader>

            <CardContent className="flex flex-col gap-4 py-6">
                {status === 'two-factor-confirmation-sent' && (
                    <div className="w-full rounded-lg border border-brand/30 bg-brand/5 px-4 py-3 text-center text-sm font-medium text-brand">
                        {t(
                            'settings.security.twoFactor.confirmationSentMessage',
                        )}
                    </div>
                )}
                {status === 'two-factor-enabled' && (
                    <div className="w-full rounded-lg border border-brand/30 bg-brand/5 px-4 py-3 text-center text-sm font-medium text-brand">
                        {t('settings.security.twoFactor.enabledMessage')}
                    </div>
                )}
                {status === 'two-factor-disabled' && (
                    <div className="w-full rounded-lg border border-border bg-muted/30 px-4 py-3 text-center text-sm font-medium text-muted-foreground">
                        {t('settings.security.twoFactor.disabledMessage')}
                    </div>
                )}
                {status === 'two-factor-trusted-devices-forgotten' && (
                    <div className="w-full rounded-lg border border-brand/30 bg-brand/5 px-4 py-3 text-center text-sm font-medium text-brand">
                        {t(
                            'settings.security.twoFactor.trustedDevicesForgottenMessage',
                        )}
                    </div>
                )}

                {enabled ? (
                    <div className="flex flex-col gap-3 rounded-lg border border-dashed border-border p-4">
                        <div className="flex flex-wrap items-center gap-3">
                            <TwoFactorDisableDialog />
                            <ForgetTrustedDevicesButton />
                        </div>
                        <p className="text-xs text-muted-foreground">
                            {t(
                                'settings.security.twoFactor.trustedDevices.description',
                            )}
                        </p>
                    </div>
                ) : (
                    <TwoFactorActivationForm />
                )}
            </CardContent>
        </Card>
    );
}
