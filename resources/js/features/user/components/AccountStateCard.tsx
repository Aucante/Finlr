import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { FALLBACK_LOCALE } from '@/lib/currency';
import { cn } from '@/lib/utils';
import type { Plan, User } from '@/types';

interface AccountStateCardProps {
    user: User;
    plan: Plan | null;
    memberSince: string;
    scenariosCount: number;
    className?: string;
}

function initials(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean);

    if (parts.length === 0) {
        return '';
    }

    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }

    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

export default function AccountStateCard({
    user,
    plan,
    memberSince,
    scenariosCount,
    className,
}: AccountStateCardProps) {
    const { t, i18n } = useTranslation();
    const locale = i18n.resolvedLanguage ?? FALLBACK_LOCALE;

    const memberSinceLabel = new Intl.DateTimeFormat(locale, {
        month: 'long',
        year: 'numeric',
    }).format(new Date(memberSince));

    return (
        <Card className={cn(className)}>
            <CardContent className="flex flex-col gap-5">
                <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    {t('settings.account.title')}
                </span>

                <div className="flex items-center gap-3">
                    <span
                        aria-hidden
                        className="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand/15 font-mono text-sm text-brand"
                    >
                        {initials(user.name)}
                    </span>
                    <div className="flex flex-col overflow-hidden">
                        <span className="truncate font-medium text-foreground">
                            {user.name}
                        </span>
                        <span className="truncate text-xs text-muted-foreground">
                            {user.email}
                        </span>
                    </div>
                </div>

                <dl className="flex flex-col divide-y divide-border border-t border-border text-sm">
                    <div className="flex items-center justify-between py-2.5">
                        <dt className="text-muted-foreground">
                            {t('settings.account.plan')}
                        </dt>
                        <dd className="font-mono">
                            {t(`settings.account.plans.${plan ?? 'free'}`)}
                        </dd>
                    </div>
                    <div className="flex items-center justify-between py-2.5">
                        <dt className="text-muted-foreground">
                            {t('settings.account.memberSince')}
                        </dt>
                        <dd className="font-mono">{memberSinceLabel}</dd>
                    </div>
                    <div className="flex items-center justify-between py-2.5">
                        <dt className="text-muted-foreground">
                            {t('settings.account.scenariosSaved')}
                        </dt>
                        <dd className="font-mono">{scenariosCount}</dd>
                    </div>
                    <div className="flex items-center justify-between py-2.5">
                        <dt className="text-muted-foreground">
                            {t('settings.account.twoFactor')}
                        </dt>
                        <dd
                            className={cn(
                                'font-mono',
                                user.two_factor_enabled
                                    ? 'text-brand'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {t(
                                user.two_factor_enabled
                                    ? 'settings.account.twoFactorEnabled'
                                    : 'settings.account.twoFactorDisabled',
                            )}
                        </dd>
                    </div>
                </dl>

                <Button asChild variant="outline" size="lg" className="self-center">
                    <Link href={route('logout')} method="post" as="button">
                        {t('settings.account.logout')}
                    </Link>
                </Button>
            </CardContent>
        </Card>
    );
}
