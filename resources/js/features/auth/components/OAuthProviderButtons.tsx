import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const OAUTH_PROVIDERS = ['google'] as const;
type OAuthProviderKey = (typeof OAUTH_PROVIDERS)[number];

/**
 * A real browser navigation, not an Inertia visit: the flow leaves the SPA
 * for the provider's own domain and comes back through a plain (non-XHR)
 * callback redirect, so these are `<a>` tags rather than Inertia `<Link>`s.
 */
export default function OAuthProviderButtons() {
    const { t } = useTranslation();
    const [redirectingTo, setRedirectingTo] = useState<OAuthProviderKey | null>(
        null,
    );
    const pending = redirectingTo !== null;

    return (
        <>
            <div className="flex items-center gap-3">
                <span aria-hidden className="h-px flex-1 bg-border" />
                <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    {t('auth.oauth.or')}
                </span>
                <span aria-hidden className="h-px flex-1 bg-border" />
            </div>

            <div className="grid grid-cols-1 gap-3">
                {OAUTH_PROVIDERS.map((provider) => (
                    <Button
                        key={provider}
                        asChild
                        variant="outline"
                        className={cn(
                            pending && 'pointer-events-none opacity-50',
                        )}
                    >
                        <a
                            href={route('oauth.redirect', { provider })}
                            aria-disabled={pending}
                            onClick={() => setRedirectingTo(provider)}
                        >
                            {t(`auth.oauth.${provider}`)}
                        </a>
                    </Button>
                ))}
            </div>
        </>
    );
}
