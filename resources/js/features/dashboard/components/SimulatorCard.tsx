import { Link } from '@inertiajs/react';
import { ArrowRight, type LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

export type SimulatorCardState = 'active' | 'locked' | 'comingSoon';

interface SimulatorCardProps {
    icon: LucideIcon;
    title: string;
    description: string;
    state: SimulatorCardState;
    href?: string;
    note?: string;
}

// Plain informational badge — never a button (no interactive role, no
// pointer cursor, no disabled state). Shared so the dashboard's promo
// block reuses the exact same "coming soon" styling instead of a second,
// slightly-divergent copy.
export function DashboardBadge({ children }: { children: ReactNode }) {
    return (
        <span className="inline-flex w-fit shrink-0 items-center rounded-full border border-muted-foreground/35 px-2.5 py-1 text-xs font-medium text-muted-foreground">
            {children}
        </span>
    );
}

export default function SimulatorCard({
    icon: Icon,
    title,
    description,
    state,
    href,
    note,
}: SimulatorCardProps) {
    const { t } = useTranslation();

    const badgeLabel =
        state === 'locked'
            ? t('dashboard.simulatorCard.lockedBadge')
            : state === 'comingSoon'
              ? t('dashboard.simulatorCard.comingSoonBadge')
              : null;

    const card = (
        <Card
            className={cn(
                'h-full justify-between gap-6 rounded-2xl py-7',
                state === 'active' &&
                    'transition-[border-color,transform] duration-200 hover:-translate-y-0.5 hover:border-brand/50',
                state !== 'active' && 'border-dashed opacity-75',
            )}
        >
            <CardHeader>
                <div className="mb-3.5 flex items-center gap-3">
                    <span
                        className={cn(
                            'inline-flex size-9 shrink-0 items-center justify-center rounded-lg',
                            state === 'active'
                                ? 'bg-brand/15 text-brand shadow-[0_0_12px_-2px_var(--brand)]'
                                : 'bg-muted text-muted-foreground',
                        )}
                    >
                        <Icon aria-hidden className="size-5" />
                    </span>
                    <CardTitle
                        className={cn(
                            'flex w-full items-start justify-between gap-2 text-lg',
                            state !== 'active' && 'text-muted-foreground',
                        )}
                    >
                        <span>{title}</span>
                        {badgeLabel && <DashboardBadge>{badgeLabel}</DashboardBadge>}
                    </CardTitle>
                </div>
            </CardHeader>
            <CardContent className="flex flex-col gap-2">
                <p className="max-w-[40ch] text-sm text-muted-foreground">{description}</p>
                {note && <p className="text-xs text-muted-foreground">{note}</p>}
                {state === 'active' && (
                    <span className="mt-4 inline-flex items-center gap-2 text-base font-semibold text-brand [text-shadow:0_0_40px_var(--brand)]">
                        {t('dashboard.simulatorCard.cta')}
                        <ArrowRight aria-hidden className="size-5" />
                    </span>
                )}
            </CardContent>
        </Card>
    );

    if (state === 'active' && href) {
        return (
            <Link href={href} className="block">
                {card}
            </Link>
        );
    }

    return card;
}
