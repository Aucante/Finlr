import { useForm } from '@inertiajs/react';
import { useRef, type SubmitEvent } from 'react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import useResendCooldown from '@/hooks/useResendCooldown';

export default function TwoFactorChallengeForm() {
    const { t } = useTranslation();
    const codeInput = useRef<HTMLInputElement>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
        remember_device: false,
    });

    const { cooldownSeconds, resending, justSent, resend } =
        useResendCooldown(route('two-factor.resend'));

    const submit = (e: SubmitEvent) => {
        e.preventDefault();

        post(route('two-factor.verify'), {
            onError: () => {
                reset('code');
                codeInput.current?.focus();
            },
        });
    };

    return (
        <Card className="w-full max-w-md gap-0 py-0">
            <CardContent className="flex flex-col gap-5 py-6">
                <form onSubmit={submit} className="flex flex-col gap-5">
                    <div className="flex flex-col gap-2">
                        <Label htmlFor="code">
                            {t('auth.twoFactorChallenge.codeLabel')}
                        </Label>
                        <Input
                            id="code"
                            ref={codeInput}
                            type="text"
                            inputMode="numeric"
                            pattern="[0-9]*"
                            autoComplete="one-time-code"
                            maxLength={6}
                            autoFocus
                            value={data.code}
                            aria-invalid={Boolean(errors.code)}
                            onChange={(e) =>
                                setData(
                                    'code',
                                    e.target.value
                                        .replace(/\D/g, '')
                                        .slice(0, 6),
                                )
                            }
                        />
                        {errors.code && (
                            <p className="text-xs text-destructive">
                                {errors.code}
                            </p>
                        )}
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="remember_device"
                            checked={data.remember_device}
                            onCheckedChange={(checked) =>
                                setData(
                                    'remember_device',
                                    checked === true,
                                )
                            }
                        />
                        <Label
                            htmlFor="remember_device"
                            className="font-normal text-muted-foreground"
                        >
                            {t('auth.twoFactorChallenge.rememberDevice')}
                        </Label>
                    </div>

                    <Button
                        type="submit"
                        variant="brand"
                        size="lg"
                        className="w-full"
                        disabled={processing}
                    >
                        {t('auth.twoFactorChallenge.submit')}
                    </Button>
                </form>

                <div className="flex flex-col items-center gap-1.5 text-center text-sm">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        disabled={resending || cooldownSeconds > 0}
                        onClick={resend}
                    >
                        {cooldownSeconds > 0
                            ? t('auth.twoFactorChallenge.resendCooldown', {
                                  seconds: cooldownSeconds,
                              })
                            : t('auth.twoFactorChallenge.resend')}
                    </Button>
                    {justSent && cooldownSeconds > 0 && (
                        <p className="text-xs text-muted-foreground">
                            {t('auth.twoFactorChallenge.resendSent')}
                        </p>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
