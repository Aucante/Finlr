import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import Footer from '@/components/Footer';
import Navbar from '@/components/Navbar';
import AccountStateCard from '@/features/user/components/AccountStateCard';
import DeleteUserForm from '@/features/user/components/DeleteUserForm';
import SimulationPreferencesCard from '@/features/user/components/SimulationPreferencesCard';
import TwoFactorForm from '@/features/user/components/TwoFactorForm';
import UpdatePasswordForm from '@/features/user/components/UpdatePasswordForm';
import UpdateProfileInformationForm from '@/features/user/components/UpdateProfileInformationForm';
import type { AuthenticatedPageProps } from '@/types';

interface EditProps {
    mustVerifyEmail: boolean;
    status?: string | null;
    memberSince: string;
    profileUpdatedAt: string;
    scenariosCount: number;
}

export default function Edit({
    mustVerifyEmail,
    status,
    memberSince,
    profileUpdatedAt,
    scenariosCount,
}: EditProps) {
    const { t } = useTranslation();
    const { auth } = usePage<AuthenticatedPageProps>().props;

    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <Head title={t('settings.title')} />

            <Navbar />

            <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 px-4 py-8 lg:px-8 lg:py-12">
                <header className="flex flex-col gap-2">
                    <span className="flex items-center gap-2 text-xs font-medium tracking-wide text-brand uppercase">
                        <span aria-hidden className="size-1.5 rounded-full bg-brand" />
                        {t('settings.eyebrow')}
                    </span>
                    <h1 className="text-2xl font-semibold tracking-tight text-balance lg:text-3xl">
                        {t('settings.title')}
                    </h1>
                    <p className="max-w-2xl text-sm text-pretty text-muted-foreground">
                        {t('settings.description')}
                    </p>
                </header>

                <div className="flex flex-col gap-6 lg:gap-8">
                    {/*
                        Row 1: same grid row as "Informations personnelles", so
                        "État du compte" naturally starts at the same top.
                        `items-stretch` also matches their height exactly —
                        which turns out to be required, not just cosmetic:
                        `position: sticky` on a grid item that's *shorter*
                        than its row (e.g. `items-start`) only gets a "stuck"
                        window as tall as the leftover slack in that row (here
                        ~7px — imperceptible), then gets pushed off by the
                        row's own trailing edge, verified by sampling scroll
                        offsets 1px apart. With the height matched there's no
                        slack to begin with, so both cards simply move in
                        lockstep at every scroll position (verified) —
                        visually indistinguishable from "stuck" since neither
                        one ever drifts from the other. `position: sticky`
                        also does NOT work at all on a grid item that spans
                        multiple row tracks (tested separately) — each sticky
                        card must stay in a single, non-spanning row.
                    */}
                    <div className="grid grid-cols-1 items-stretch gap-6 lg:grid-cols-2 lg:gap-8">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            profileUpdatedAt={profileUpdatedAt}
                        />

                        <AccountStateCard
                            user={auth.user}
                            plan={auth.plan}
                            memberSince={memberSince}
                            scenariosCount={scenariosCount}
                            className="lg:sticky lg:top-20"
                        />
                    </div>

                    {/*
                        Row 2+: the note starts as the first (and only) item on
                        this row's right cell, so its static top naturally lines
                        up with Sécurité's top — the row itself is tall (sized by
                        the 3-card left stack), giving the note's own single-row
                        grid area plenty of room to travel once it sticks.
                    */}
                    <div className="grid grid-cols-1 items-start gap-6 lg:grid-cols-2 lg:gap-8">
                        <div className="flex flex-col gap-6">
                            <UpdatePasswordForm status={status} />

                            <TwoFactorForm
                                enabled={auth.user.two_factor_enabled}
                                status={status}
                            />

                            <SimulationPreferencesCard />

                            <DeleteUserForm />
                        </div>

                        <div className="rounded-lg border border-dashed border-border bg-muted/30 p-4 text-xs text-muted-foreground lg:sticky lg:top-20">
                            {t('settings.account.dataNotice')}
                        </div>
                    </div>
                </div>
            </main>

            <Footer />
        </div>
    );
}
