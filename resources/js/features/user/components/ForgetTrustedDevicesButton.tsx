import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';

/**
 * Independent from disabling 2FA (CONCEPTION.md, section 5): purges every
 * trusted-device row without touching `two_factor_enabled_at`, and the
 * backend also expires the current browser's cookie.
 */
export default function ForgetTrustedDevicesButton() {
    const { t } = useTranslation();
    const { delete: destroy, processing } = useForm({});

    const forget = () => {
        destroy(route('two-factor.trusted-devices.forget'), {
            preserveScroll: true,
        });
    };

    return (
        <Button
            type="button"
            variant="link"
            className="h-auto self-start p-0 text-base font-medium text-foreground no-underline hover:text-foreground hover:no-underline"
            onClick={forget}
            disabled={processing}
        >
            {t('settings.security.twoFactor.trustedDevices.button')}
        </Button>
    );
}
