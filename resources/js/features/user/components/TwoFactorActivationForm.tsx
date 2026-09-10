import { useForm } from '@inertiajs/react';
import { useRef, useState, type SubmitEvent } from 'react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import useResendCooldown from '@/hooks/useResendCooldown';

/**
 * Activation is deliberately two steps (CONCEPTION.md, section 5):
 * `two_factor_enabled_at` stays null until the confirmation code is
 * verified, so a user can never lock themselves out with a mistyped or
 * unreachable email address. The confirmation step below is shown
 * optimistically as soon as the request is fired, rather than waiting on
 * a server round trip — the email is already in flight either way.
 */
export default function TwoFactorActivationForm() {
    const { t } = useTranslation();
    const [confirming, setConfirming] = useState(false);
    const codeInput = useRef<HTMLInputElement>(null);

    const requestForm = useForm({});
    const confirmForm = useForm({ code: '' });

    const { cooldownSeconds, resending, justSent, resend } =
        useResendCooldown(route('two-factor.enable'));

    const startActivation = () => {
        setConfirming(true);
        requestForm.post(route('two-factor.enable'), {
            preserveScroll: true,
        });
    };

    const cancel = () => {
        setConfirming(false);
        confirmForm.reset();
        confirmForm.clearErrors();
    };

    const confirm = (e: SubmitEvent) => {
        e.preventDefault();

        confirmForm.post(route('two-factor.confirm'), {
            preserveScroll: true,
            onError: () => {
                confirmForm.reset('code');
                codeInput.current?.focus();
            },
        });
    };

    if (!confirming) {
        return (
            <div className="flex items-center rounded-lg border border-dashed border-border p-4">
                <Button
                    type="button"
                    variant="brand"
                    onClick={startActivation}
                >
                    {t('settings.security.twoFactor.enableButton')}
                </Button>
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-4 rounded-lg border border-dashed border-border p-4">
            <p className="text-sm text-muted-foreground">
                {t('settings.security.twoFactor.activation.description')}
            </p>

            <form onSubmit={confirm} className="flex flex-col gap-4">
                <div className="flex flex-col gap-2">
                    <Label htmlFor="two_factor_activation_code">
                        {t(
                            'settings.security.twoFactor.activation.codeLabel',
                        )}
                    </Label>
                    <Input
                        id="two_factor_activation_code"
                        ref={codeInput}
                        type="text"
                        inputMode="numeric"
                        pattern="[0-9]*"
                        autoComplete="one-time-code"
                        maxLength={6}
                        autoFocus
                        value={confirmForm.data.code}
                        aria-invalid={Boolean(confirmForm.errors.code)}
                        onChange={(e) =>
                            confirmForm.setData(
                                'code',
                                e.target.value
                                    .replace(/\D/g, '')
                                    .slice(0, 6),
                            )
                        }
                    />
                    {confirmForm.errors.code && (
                        <p className="text-xs text-destructive">
                            {confirmForm.errors.code}
                        </p>
                    )}
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <Button
                        type="submit"
                        variant="brand"
                        disabled={confirmForm.processing}
                    >
                        {t(
                            'settings.security.twoFactor.activation.confirmButton',
                        )}
                    </Button>
                    <Button type="button" variant="outline" onClick={cancel}>
                        {t(
                            'settings.security.twoFactor.activation.cancelButton',
                        )}
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        disabled={resending || cooldownSeconds > 0}
                        onClick={resend}
                    >
                        {cooldownSeconds > 0
                            ? t(
                                  'settings.security.twoFactor.activation.resendCooldown',
                                  { seconds: cooldownSeconds },
                              )
                            : t(
                                  'settings.security.twoFactor.activation.resendButton',
                              )}
                    </Button>
                </div>

                {justSent && cooldownSeconds > 0 && (
                    <p className="text-xs text-muted-foreground">
                        {t(
                            'settings.security.twoFactor.activation.resendSent',
                        )}
                    </p>
                )}
            </form>
        </div>
    );
}
