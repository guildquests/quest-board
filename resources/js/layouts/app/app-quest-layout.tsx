import { usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { AppNav } from '@/components/game/app-nav';
import { DemoBanner } from '@/components/game/demo-banner';
import { ImpersonationBanner } from '@/components/game/impersonation-banner';
import { PageHero } from '@/components/game/page-hero';
import { PlayerCard } from '@/components/game/player-card';

export default function AppQuestLayout({
    children,
}: PropsWithChildren): React.JSX.Element {
    const { auth, branding, progress, reviewCount, impersonation, demo } =
        usePage().props;

    return (
        <div className="min-h-svh bg-bg">
            {impersonation !== null && (
                <ImpersonationBanner name={auth.user.name} />
            )}
            {demo !== null && <DemoBanner resetTime={demo.reset_time} />}
            <PageHero
                subtitle={branding.tagline}
                aside={
                    progress && (
                        <PlayerCard
                            name={auth.user.name}
                            portrait={auth.user.portrait_url ?? undefined}
                            level={progress.level}
                            title={progress.title}
                            xp={progress.xp}
                            levelFloor={progress.floor}
                            nextLevelXp={progress.next}
                            className="max-[520px]:w-full"
                        />
                    )
                }
            >
                <AppNav reviewCount={reviewCount ?? undefined} />
            </PageHero>

            <main className="mx-auto max-w-[1440px] px-4 pt-6 pb-12 min-[700px]:px-6">
                {children}
            </main>
        </div>
    );
}
