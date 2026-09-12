import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import Footer from '@/components/Footer';
import Navbar from '@/components/Navbar';
import TwoFactorChallengeForm from '@/features/auth/components/TwoFactorChallengeForm';

export default function TwoFactorChallenge() {
    const { t } = useTranslation();

    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <Head title={t('auth.twoFactorChallenge.title')} />

            <Navbar />

            <main className="mx-auto flex w-full max-w-7xl flex-1 flex-col items-center justify-center gap-8 px-4 py-12 lg:px-8">
                <header className="flex flex-col items-center gap-2 text-center">
                    <span className="flex items-center gap-2 text-xs font-medium tracking-wide text-brand uppercase">
                        <span aria-hidden className="size-1.5 rounded-full bg-brand" />
                        {t('auth.twoFactorChallenge.eyebrow')}
                    </span>
                    <h1 className="text-2xl font-semibold tracking-tight lg:text-3xl">
                        {t('auth.twoFactorChallenge.title')}
                    </h1>
                    <p className="max-w-sm text-sm text-pretty text-muted-foreground">
                        {t('auth.twoFactorChallenge.description')}
                    </p>
                </header>

                <TwoFactorChallengeForm />
            </main>

            <Footer />
        </div>
    );
}
