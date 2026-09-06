import { Link, router } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Pagination } from '@/components/ui/pagination';
import { formatDate } from '@/features/dashboard/lib/format';
import type { ScenarioSummary } from '@/features/dashboard/types';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

const GRID_COLUMNS = 'sm:grid-cols-[1.6fr_1fr_0.8fr_1fr_1.25rem]';

interface ScenarioListProps {
    scenarios: Paginated<ScenarioSummary>;
}

export default function ScenarioList({ scenarios }: ScenarioListProps) {
    const { t, i18n } = useTranslation();
    const locale = i18n.resolvedLanguage;

    const formatHorizon = (years: number): string =>
        years > 0 ? `${years} ${t('form.yearsUnit', { count: years })}` : '—';

    const goToPage = (page: number) => {
        router.get(
            route('dashboard'),
            { page },
            { preserveState: true, preserveScroll: true, only: ['scenarios'] },
        );
    };

    return (
        <Card className="gap-0 overflow-hidden rounded-2xl py-0">
            <CardHeader className="flex items-baseline gap-3 border-b border-border py-5">
                <CardTitle className="text-base">{t('dashboard.scenarioList.title')}</CardTitle>
                {scenarios.total > 0 && (
                    <span className="font-mono text-xs text-muted-foreground">
                        {t('dashboard.scenarioList.count', { count: scenarios.total })}
                    </span>
                )}
            </CardHeader>
            <CardContent className="p-0">
                {scenarios.data.length === 0 ? (
                    <p className="p-6 text-sm text-muted-foreground">{t('dashboard.scenarioList.empty')}</p>
                ) : (
                    <>
                        <div
                            className={cn(
                                'hidden gap-4 border-b-2 border-brand/20 px-6 py-3 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase sm:grid',
                                GRID_COLUMNS,
                            )}
                        >
                            <span>{t('dashboard.scenarioList.columns.name')}</span>
                            <span className="text-center">{t('dashboard.scenarioList.columns.type')}</span>
                            <span className="text-center">{t('dashboard.scenarioList.columns.horizon')}</span>
                            <span className="text-center">{t('dashboard.scenarioList.columns.date')}</span>
                            {/* No label: icon-only "open" column, described per-row via the link's aria-label. */}
                            <span aria-hidden />
                        </div>
                        <ul className="flex flex-col divide-y divide-border">
                            {scenarios.data.map((scenario) => {
                                const displayName = scenario.name ?? t('dashboard.scenarioList.genericLabel');
                                const typeLabel = t(scenario.typeLabel);
                                const horizonLabel = formatHorizon(scenario.years);
                                const dateLabel = formatDate(scenario.createdAt, locale);

                                return (
                                    <li key={scenario.id}>
                                        <Link
                                            href={route('scenarios.show', scenario.id)}
                                            aria-label={t('dashboard.scenarioList.openAriaLabel', { name: displayName })}
                                            className={cn(
                                                'group flex flex-col gap-1 px-6 py-4 text-sm transition-colors hover:bg-brand/5 sm:grid sm:items-center sm:gap-4',
                                                GRID_COLUMNS,
                                            )}
                                        >
                                            <span className="font-medium group-hover:text-brand sm:order-1">
                                                {displayName}
                                            </span>
                                            <span className="text-xs text-muted-foreground sm:hidden">
                                                {typeLabel} · {horizonLabel} · {dateLabel}
                                            </span>
                                            <span className="hidden text-center text-muted-foreground sm:order-2 sm:block">
                                                <span className="inline-flex items-center gap-1.5">
                                                    <span aria-hidden className="size-1.5 shrink-0 rounded-full bg-brand" />
                                                    {typeLabel}
                                                </span>
                                            </span>
                                            <span className="hidden text-center text-muted-foreground sm:order-3 sm:block">
                                                {horizonLabel}
                                            </span>
                                            <span className="hidden text-center text-muted-foreground sm:order-4 sm:block">
                                                {dateLabel}
                                            </span>
                                            <ArrowRight
                                                aria-hidden
                                                className="hidden size-4 shrink-0 text-muted-foreground transition-colors sm:order-5 sm:block sm:justify-self-end group-hover:text-brand"
                                            />
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                        <div className="border-t border-border px-6 py-4">
                            <Pagination
                                currentPage={scenarios.currentPage}
                                lastPage={scenarios.lastPage}
                                onPageChange={goToPage}
                                previousLabel={t('dashboard.scenarioList.pagination.previous')}
                                nextLabel={t('dashboard.scenarioList.pagination.next')}
                                pageIndicatorLabel={t('dashboard.scenarioList.pagination.pageIndicator', {
                                    currentPage: scenarios.currentPage,
                                    lastPage: scenarios.lastPage,
                                })}
                            />
                        </div>
                    </>
                )}
            </CardContent>
        </Card>
    );
}
