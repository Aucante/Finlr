import { Transition } from '@headlessui/react';
import { Link, useForm, usePage } from '@inertiajs/react';
import type { SubmitEvent } from 'react';
import { useTranslation } from 'react-i18next';

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
import { formatDate } from '@/features/dashboard/lib/format';
import { FALLBACK_LOCALE } from '@/lib/currency';
import type { AuthenticatedPageProps } from '@/types';

interface UpdateProfileInformationProps {
    mustVerifyEmail: boolean;
    status?: string | null;
    profileUpdatedAt: string;
}

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    profileUpdatedAt,
}: UpdateProfileInformationProps) {
    const { t, i18n } = useTranslation();
    const user = usePage<AuthenticatedPageProps>().props.auth.user;
    const locale = i18n.resolvedLanguage ?? FALLBACK_LOCALE;

    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            name: user.name,
            email: user.email,
        });

    const submit = (e: SubmitEvent) => {
        e.preventDefault();

        patch(route('settings.update'));
    };

    return (
        <Card className="gap-0 py-0">
            <CardHeader className="gap-1.5 border-b border-border py-5">
                <div className="flex items-baseline gap-2">
                    <span aria-hidden className="font-mono text-xs text-brand">
                        01
                    </span>
                    <CardTitle className="text-base">
                        {t('settings.information.title')}
                    </CardTitle>
                </div>
                <CardDescription>
                    {t('settings.information.description')}
                </CardDescription>
            </CardHeader>

            <CardContent className="py-6">
                <form onSubmit={submit} className="flex flex-col gap-5">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="flex flex-col gap-2">
                            <Label htmlFor="name">
                                {t('settings.information.name')}
                            </Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                                autoComplete="name"
                                aria-invalid={Boolean(errors.name)}
                            />
                            {errors.name && (
                                <p className="text-xs text-destructive">
                                    {errors.name}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-2">
                            <Label htmlFor="email">
                                {t('settings.information.email')}
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                required
                                autoComplete="username"
                                aria-invalid={Boolean(errors.email)}
                            />
                            {errors.email && (
                                <p className="text-xs text-destructive">
                                    {errors.email}
                                </p>
                            )}
                        </div>
                    </div>

                    {mustVerifyEmail &&
                        (user.email_verified_at === null ? (
                            <div className="flex flex-col gap-1 text-sm text-muted-foreground">
                                <p>
                                    {t('settings.information.unverified')}{' '}
                                    <Link
                                        href={route('verification.send')}
                                        method="post"
                                        as="button"
                                        className="font-medium text-brand hover:underline"
                                    >
                                        {t('settings.information.resendLink')}
                                    </Link>
                                </p>

                                {status === 'verification-link-sent' && (
                                    <p className="font-medium text-brand">
                                        {t(
                                            'settings.information.verificationSent',
                                        )}
                                    </p>
                                )}
                            </div>
                        ) : (
                            <div className="flex flex-wrap items-center gap-3 rounded-lg border border-dashed border-border p-3">
                                <span className="inline-flex w-fit items-center rounded-full bg-brand/10 px-2.5 py-1 text-xs font-medium tracking-wide text-brand uppercase">
                                    {t('settings.information.emailVerified')}
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    {t('settings.information.emailVerifiedHint')}
                                </span>
                            </div>
                        ))}

                    <div className="flex flex-col items-center gap-2">
                        <Button
                            type="submit"
                            variant="brand"
                            size="lg"
                            disabled={processing}
                        >
                            {t('settings.information.save')}
                        </Button>

                        <div className="flex flex-wrap items-center justify-center gap-3">
                            <span className="text-xs text-muted-foreground">
                                {t('settings.information.lastUpdated', {
                                    date: formatDate(profileUpdatedAt, locale),
                                })}
                            </span>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-muted-foreground">
                                    {t('settings.information.saved')}
                                </p>
                            </Transition>
                        </div>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
