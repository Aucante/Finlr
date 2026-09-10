import { useForm } from '@inertiajs/react';
import { Eye, EyeOff } from 'lucide-react';
import { useMemo, useRef, useState, type SubmitEvent } from 'react';
import { useTranslation } from 'react-i18next';

import PasswordRequirementsChecklist from '@/components/PasswordRequirementsChecklist';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { meetsPasswordPolicy } from '@/lib/passwordPolicy';

type PasswordStrength = 'empty' | 'weak' | 'medium' | 'strong';

function computeStrength(password: string): PasswordStrength {
    if (!password) {
        return 'empty';
    }

    if (!meetsPasswordPolicy(password)) {
        return 'weak';
    }

    return password.length >= 12 ? 'strong' : 'medium';
}

interface UpdatePasswordFormProps {
    status?: string | null;
}

export default function UpdatePasswordForm({ status }: UpdatePasswordFormProps) {
    const { t } = useTranslation();
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);
    const [showCurrentPassword, setShowCurrentPassword] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] =
        useState(false);

    const { data, setData, errors, put, reset, processing } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const strength = useMemo(() => computeStrength(data.password), [data.password]);

    const updatePassword = (e: SubmitEvent) => {
        e.preventDefault();

        put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (errors) => {
                if (errors.password) {
                    reset('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }

                if (errors.current_password) {
                    reset('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <Card className="gap-0 py-0">
            <CardHeader className="gap-1.5 border-b border-border py-5">
                <div className="flex items-baseline gap-2">
                    <span aria-hidden className="font-mono text-xs text-brand">
                        02
                    </span>
                    <CardTitle className="text-base">
                        {t('settings.security.title')}
                    </CardTitle>
                </div>
                <CardDescription>
                    {t('settings.security.description')}
                </CardDescription>
            </CardHeader>

            <CardContent className="flex flex-col gap-5 py-6">
                {status === 'password-updated' && (
                    <div className="w-full rounded-lg border border-brand/30 bg-brand/5 px-4 py-3 text-center text-sm font-medium text-brand">
                        {t('settings.security.passwordUpdated')}
                    </div>
                )}

                <form
                    onSubmit={updatePassword}
                    className="flex flex-col gap-5"
                >
                    <div className="flex flex-col gap-2">
                        <Label htmlFor="current_password">
                            {t('settings.security.currentPassword')}
                        </Label>
                        <div className="relative">
                            <Input
                                id="current_password"
                                ref={currentPasswordInput}
                                type={
                                    showCurrentPassword ? 'text' : 'password'
                                }
                                value={data.current_password}
                                onChange={(e) =>
                                    setData(
                                        'current_password',
                                        e.target.value,
                                    )
                                }
                                autoComplete="current-password"
                                className="pr-10"
                                aria-invalid={Boolean(
                                    errors.current_password,
                                )}
                            />
                            <button
                                type="button"
                                onClick={() =>
                                    setShowCurrentPassword((v) => !v)
                                }
                                aria-label={
                                    showCurrentPassword
                                        ? t('settings.security.hidePassword')
                                        : t('settings.security.showPassword')
                                }
                                className={cn(
                                    'absolute inset-y-0 right-0 flex items-center px-3',
                                    'text-muted-foreground hover:text-foreground',
                                )}
                            >
                                {showCurrentPassword ? (
                                    <EyeOff className="size-4" />
                                ) : (
                                    <Eye className="size-4" />
                                )}
                            </button>
                        </div>
                        {errors.current_password && (
                            <p className="text-xs text-destructive">
                                {errors.current_password}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-2">
                        <Label htmlFor="password">
                            {t('settings.security.newPassword')}
                        </Label>
                        <div className="relative">
                            <Input
                                id="password"
                                ref={passwordInput}
                                type={showPassword ? 'text' : 'password'}
                                value={data.password}
                                onChange={(e) =>
                                    setData('password', e.target.value)
                                }
                                autoComplete="new-password"
                                className="pr-10"
                                aria-invalid={Boolean(errors.password)}
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword((v) => !v)}
                                aria-label={
                                    showPassword
                                        ? t('settings.security.hidePassword')
                                        : t('settings.security.showPassword')
                                }
                                className={cn(
                                    'absolute inset-y-0 right-0 flex items-center px-3',
                                    'text-muted-foreground hover:text-foreground',
                                )}
                            >
                                {showPassword ? (
                                    <EyeOff className="size-4" />
                                ) : (
                                    <Eye className="size-4" />
                                )}
                            </button>
                        </div>
                        {errors.password && (
                            <p className="text-xs text-destructive">
                                {errors.password}
                            </p>
                        )}
                        <PasswordRequirementsChecklist
                            password={data.password}
                        />
                    </div>

                    <div className="flex flex-col gap-2">
                        <Label htmlFor="password_confirmation">
                            {t('settings.security.passwordConfirmation')}
                        </Label>
                        <div className="relative">
                            <Input
                                id="password_confirmation"
                                type={
                                    showPasswordConfirmation
                                        ? 'text'
                                        : 'password'
                                }
                                value={data.password_confirmation}
                                onChange={(e) =>
                                    setData(
                                        'password_confirmation',
                                        e.target.value,
                                    )
                                }
                                autoComplete="new-password"
                                className="pr-10"
                                aria-invalid={Boolean(
                                    errors.password_confirmation,
                                )}
                            />
                            <button
                                type="button"
                                onClick={() =>
                                    setShowPasswordConfirmation((v) => !v)
                                }
                                aria-label={
                                    showPasswordConfirmation
                                        ? t('settings.security.hidePassword')
                                        : t('settings.security.showPassword')
                                }
                                className={cn(
                                    'absolute inset-y-0 right-0 flex items-center px-3',
                                    'text-muted-foreground hover:text-foreground',
                                )}
                            >
                                {showPasswordConfirmation ? (
                                    <EyeOff className="size-4" />
                                ) : (
                                    <Eye className="size-4" />
                                )}
                            </button>
                        </div>
                        {errors.password_confirmation && (
                            <p className="text-xs text-destructive">
                                {errors.password_confirmation}
                            </p>
                        )}
                    </div>

                    <div className="flex items-center justify-between text-sm">
                        <span className="text-muted-foreground">
                            {t('settings.security.strength.label')}
                        </span>
                        <span
                            className={cn(
                                'font-mono text-xs',
                                strength === 'strong' && 'text-brand',
                                strength === 'weak' && 'text-destructive',
                                strength === 'empty' &&
                                    'text-muted-foreground',
                            )}
                        >
                            {t(`settings.security.strength.${strength}`)}
                        </span>
                    </div>

                    <div className="flex items-center gap-4">
                        <Button
                            type="submit"
                            variant="brand"
                            disabled={processing}
                        >
                            {t('settings.security.save')}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
