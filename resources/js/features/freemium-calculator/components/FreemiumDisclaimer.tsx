import { Link } from '@inertiajs/react';
import { TriangleAlert } from 'lucide-react';
import { useTranslation } from 'react-i18next';

/**
 * This calculator computes 100% client-side, deliberately never calling
 * the real financial engine (see computeCompound()'s docblock for why) —
 * which means its result can silently drift from a fiscally exact one. A
 * hover-only tooltip is not enough to prevent that confusion, so this
 * disclaimer is rendered as a permanently visible banner, not tucked away
 * behind an icon.
 */
export default function FreemiumDisclaimer() {
    const { t } = useTranslation();

    return (
        <div className="flex items-start gap-3 rounded-lg border border-orange-500/30 bg-orange-500/5 px-4 py-3 text-sm dark:border-orange-400/30 dark:bg-orange-400/10">
            <TriangleAlert
                aria-hidden
                className="mt-0.5 size-4 shrink-0 text-orange-600 dark:text-orange-400"
            />
            <p className="text-orange-900 dark:text-orange-200">
                {t('disclaimer.text')}{' '}
                <Link
                    href={route('simulators.index')}
                    className="font-medium underline underline-offset-2 hover:no-underline"
                >
                    {t('disclaimer.cta')}
                </Link>
                .
            </p>
        </div>
    );
}
