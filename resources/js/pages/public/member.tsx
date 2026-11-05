import { Head } from '@inertiajs/react';
import { CalendarDays, Medal, Sparkles, Trophy } from 'lucide-react';
import { MemberCard } from '@/components/game/member-card';
import { badgeLook } from '@/lib/badges';
import { cn } from '@/lib/utils';
import type { MemberSummary } from '@/types/guild';

type PublicMemberProps = {
    member: MemberSummary;
    qr: string;
};

export default function Member({
    member,
    qr,
}: PublicMemberProps): React.JSX.Element {
    return (
        <>
            <Head title={`${member.name}'s Guild Profile`}>
                <meta name="robots" content="noindex" />
            </Head>

            <div className="grid items-start gap-8 lg:grid-cols-[minmax(0,380px)_minmax(0,1fr)] lg:gap-14">
                <section className="flex flex-col items-center gap-4">
                    <MemberCard member={member} qr={qr} />
                    <p className="max-w-[340px] text-center text-sm text-muted-foreground">
                        A read-only guild profile. The card flips to show the
                        profile QR code.
                    </p>
                </section>

                <section className="min-w-0 rounded-[20px] border border-primary/35 bg-linear-to-b from-panel-2 to-panel p-6 shadow-[0_0_30px_color-mix(in_srgb,var(--color-primary)_8%,transparent)] sm:p-8">
                    <p className="text-xs font-bold tracking-[0.16em] text-accent uppercase">
                        Guild profile
                    </p>
                    <h1 className="mt-2 font-brand text-[clamp(30px,5vw,48px)] leading-tight font-bold [overflow-wrap:anywhere] text-primary-soft [text-shadow:0_2px_0_var(--color-primary-shadow)]">
                        {member.name}
                    </h1>
                    <p className="mt-2 text-lg text-muted-foreground">
                        Level {member.level} · {member.level_title}
                    </p>

                    <dl className="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <ProfileStat
                            icon={Sparkles}
                            label="Total XP"
                            value={member.xp}
                            accent="text-primary-soft"
                        />
                        <ProfileStat
                            icon={Trophy}
                            label="Quests done"
                            value={member.quests_done}
                        />
                        <ProfileStat
                            icon={CalendarDays}
                            label="Joined"
                            value={member.joined_year}
                        />
                    </dl>

                    <section
                        className="mt-9 border-t border-line pt-7"
                        aria-labelledby="earned-badges"
                    >
                        <div className="flex items-center justify-between gap-3">
                            <h2
                                id="earned-badges"
                                className="flex items-center gap-2 font-title text-2xl font-bold text-primary-soft"
                            >
                                <Medal
                                    aria-hidden
                                    className="size-5 text-primary"
                                />
                                Earned badges
                            </h2>
                            <span className="text-sm text-muted-foreground">
                                {member.badges.length}
                            </span>
                        </div>

                        {member.badges.length > 0 ? (
                            <ul className="mt-4 grid gap-3 sm:grid-cols-2">
                                {member.badges.map((badge) => {
                                    const look = badgeLook[badge.key];
                                    const Icon = look.icon;

                                    return (
                                        <li
                                            key={badge.key}
                                            className="flex min-w-0 items-start gap-3 rounded-xl border border-line bg-bg/55 p-3"
                                        >
                                            <span
                                                className="grid size-10 shrink-0 place-items-center rounded-full border border-line bg-panel-2"
                                                style={{ color: look.colour }}
                                            >
                                                <Icon
                                                    aria-hidden
                                                    className="size-5"
                                                />
                                            </span>
                                            <span className="min-w-0">
                                                <span className="block font-semibold text-foreground">
                                                    {badge.label}
                                                </span>
                                                <span className="mt-0.5 block text-sm text-muted-foreground">
                                                    {badge.description}
                                                </span>
                                            </span>
                                        </li>
                                    );
                                })}
                            </ul>
                        ) : (
                            <p className="mt-4 rounded-xl border border-dashed border-line bg-bg/45 px-4 py-5 text-sm text-muted-foreground">
                                No badges yet
                            </p>
                        )}
                    </section>
                </section>
            </div>
        </>
    );
}

function ProfileStat({
    icon: Icon,
    label,
    value,
    accent,
}: {
    icon: typeof Sparkles;
    label: string;
    value: number;
    accent?: string;
}): React.JSX.Element {
    return (
        <div className="rounded-xl border border-line bg-bg/55 px-4 py-3">
            <dt className="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground">
                <Icon aria-hidden className="size-3.5" />
                {label}
            </dt>
            <dd
                className={cn(
                    'mt-1 font-title text-3xl font-bold tabular-nums',
                    accent,
                )}
            >
                {value}
            </dd>
        </div>
    );
}
