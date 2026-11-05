import { Shield } from 'lucide-react';
import type { ReactNode } from 'react';
import { Portrait } from '@/components/game/portrait';
import { XpBar } from '@/components/game/xp-bar';
import { cn } from '@/lib/utils';
import type { ProfilePageProps } from '@/types/quests';

export type ProfileHeroProps = {
    profile: ProfilePageProps['profile'];
    level: ProfilePageProps['level'];
    stats: ProfilePageProps['stats'];
    action?: ReactNode;
    className?: string;
};

export function ProfileHero({
    profile,
    level,
    stats,
    action,
    className,
}: ProfileHeroProps): React.JSX.Element {
    const statsTiles = (
        <dl className="m-0 grid grid-cols-2 gap-2.5 max-[1100px]:col-span-full max-[1100px]:grid-cols-4 max-[640px]:w-full max-[640px]:grid-cols-2 max-[640px]:text-left">
            <div className="rounded-xl border border-line bg-bg/60 px-4 py-3">
                <dt className="text-xs font-semibold text-muted-foreground">
                    Quests done
                </dt>
                <dd className="mt-0.5 font-title text-[26px] leading-tight font-bold tabular-nums">
                    {stats.quests_done}
                </dd>
            </div>
            <div className="rounded-xl border border-line bg-bg/60 px-4 py-3">
                <dt className="text-xs font-semibold text-muted-foreground">
                    Quests posted
                </dt>
                <dd className="mt-0.5 font-title text-[26px] leading-tight font-bold tabular-nums">
                    {stats.quests_posted}
                </dd>
            </div>
            <div className="rounded-xl border border-line bg-bg/60 px-4 py-3">
                <dt className="text-xs font-semibold text-muted-foreground">
                    Total XP
                </dt>
                <dd className="mt-0.5 font-title text-[26px] leading-tight font-bold text-primary-soft tabular-nums">
                    {stats.total_xp}
                </dd>
            </div>
            <div className="rounded-xl border border-line bg-bg/60 px-4 py-3">
                <dt className="text-xs font-semibold text-muted-foreground">
                    Badges
                </dt>
                <dd className="mt-0.5 font-title text-[26px] leading-tight font-bold tabular-nums">
                    {stats.badges_earned}{' '}
                    <small className="text-base text-muted-foreground">
                        / {stats.badges_total}
                    </small>
                </dd>
            </div>
        </dl>
    );

    return (
        <section
            aria-label={`${profile.name}'s profile`}
            className={cn(
                'grid min-w-0 grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-7 rounded-[18px] border border-primary/45 bg-[radial-gradient(80%_140%_at_0%_0%,color-mix(in_srgb,var(--color-accent)_14%,transparent),transparent_60%),linear-gradient(180deg,var(--color-panel-2),var(--color-panel))] p-7 shadow-[0_0_30px_color-mix(in_srgb,var(--color-primary)_10%,transparent)] max-[1100px]:grid-cols-[auto_minmax(0,1fr)] max-[640px]:grid-cols-1 max-[640px]:justify-items-center max-[640px]:gap-[18px] max-[640px]:p-[22px_16px] max-[640px]:text-center',
                className,
            )}
        >
            <Portrait
                src={profile.portrait_url ?? undefined}
                name={profile.name}
                size="xxl"
            />

            <div className="min-w-0">
                <span className="inline-flex items-center gap-1.5 rounded-full border border-accent/50 bg-accent/12 px-3 py-1 text-[13px] font-bold text-accent">
                    <Shield aria-hidden className="size-[15px]" />
                    Level {level.level} · {level.title}
                </span>
                <h2 className="mt-2 font-brand text-[clamp(28px,3.4vw,40px)] leading-[1.1] font-bold [overflow-wrap:anywhere] text-primary-soft [text-shadow:0_2px_0_var(--color-primary-shadow),0_6px_22px_rgb(0_0_0/0.6)]">
                    {profile.name}
                </h2>
                <p className="mt-1 text-muted-foreground">
                    {profile.role_label} · Adventuring since{' '}
                    {profile.member_since}
                </p>
                <XpBar
                    xp={level.xp}
                    levelFloor={level.floor}
                    nextLevelXp={level.next}
                    className="mt-3 max-w-[440px] text-[13px] max-[640px]:mx-auto [&_[data-slot=progress]]:h-3"
                />
                <p className="mt-1.5 text-[13.5px] text-muted-foreground">
                    {level.next !== null && level.next_level ? (
                        <>
                            {level.next - level.xp} XP to{' '}
                            <strong className="text-foreground">
                                Level {level.next_level.level} ·{' '}
                                {level.next_level.title}
                            </strong>
                        </>
                    ) : (
                        'Top level reached'
                    )}
                </p>
            </div>

            {action ? (
                <div className="min-w-0 max-[1100px]:col-span-full max-[640px]:w-full">
                    <div className="mb-2.5 flex justify-end max-[640px]:w-full">
                        {action}
                    </div>
                    {statsTiles}
                </div>
            ) : (
                statsTiles
            )}
        </section>
    );
}
