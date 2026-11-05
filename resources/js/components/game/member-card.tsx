import { usePage } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { Ref } from 'react';
import { Portrait } from '@/components/game/portrait';
import { badgeLook } from '@/lib/badges';
import { flipSide, reducedMotion } from '@/lib/card-flip';
import type { CardSide } from '@/lib/card-flip';
import { cn } from '@/lib/utils';
import type { MemberSummary } from '@/types/guild';

export type MemberCardSide = CardSide;

export type MemberCardProps = {
    member: MemberSummary;
    qr: string | null;
    side?: MemberCardSide;
    onSideChange?: (side: MemberCardSide) => void;
    className?: string;
    ref?: Ref<HTMLElement>;
};

function memberNameClass(name: string): string {
    if (name.length <= 14) {
        return 'text-2xl';
    }

    if (name.length <= 24) {
        return 'text-[19px]';
    }

    return 'text-base';
}

function MemberCardHeader({
    branding,
}: {
    branding: { logo_url: string; name: string };
}): React.JSX.Element {
    return (
        <header className="relative flex shrink-0 items-center justify-center gap-2 border-b border-primary/20 pb-2.5">
            <img
                src={branding.logo_url}
                alt=""
                className="size-7 shrink-0 object-contain"
            />
            <p className="font-brand text-xs font-bold tracking-[0.06em] text-primary-soft">
                {branding.name}
            </p>
        </header>
    );
}

function MemberCardFront({
    member,
}: {
    member: MemberSummary;
}): React.JSX.Element {
    const totalBadges = Object.keys(badgeLook).length;

    return (
        <section className="flex h-full min-h-0 flex-col text-left">
            <div className="grid shrink-0 grid-cols-3 gap-1.5">
                <div className="rounded-lg border border-line bg-bg/60 px-2 py-2">
                    <p className="text-[10px] font-semibold text-muted-foreground">
                        Total XP
                    </p>
                    <p className="mt-1 text-lg leading-none font-bold text-primary-soft tabular-nums">
                        {member.xp}
                    </p>
                </div>
                <div className="rounded-lg border border-line bg-bg/60 px-2 py-2">
                    <p className="text-[10px] font-semibold text-muted-foreground">
                        Quests done
                    </p>
                    <p className="mt-1 text-lg leading-none font-bold tabular-nums">
                        {member.quests_done}
                    </p>
                </div>
                <div className="rounded-lg border border-line bg-bg/60 px-2 py-2">
                    <p className="text-[10px] font-semibold text-muted-foreground">
                        Member since
                    </p>
                    <p className="mt-1 text-lg leading-none font-bold tabular-nums">
                        {member.joined_year}
                    </p>
                </div>
            </div>

            <section className="mt-3 min-h-0 flex-1">
                <div className="mb-2 flex items-center justify-between gap-2">
                    <h3 className="text-xs font-bold tracking-[0.12em] text-primary-soft uppercase">
                        Badges
                    </h3>
                    {member.badges.length > 0 ? (
                        <p className="text-[11px] text-muted-foreground">
                            {member.badges.length} of {totalBadges}
                        </p>
                    ) : null}
                </div>

                {member.badges.length > 0 ? (
                    <ul
                        className="grid grid-cols-3 gap-1.5"
                        aria-label="Earned badges"
                    >
                        {member.badges.map((badge) => {
                            const look = badgeLook[badge.key];
                            const Icon = look.icon;
                            const accessibleName = `${badge.label}: ${badge.description}`;

                            return (
                                <li
                                    key={badge.key}
                                    title={accessibleName}
                                    aria-label={accessibleName}
                                    className="flex min-h-[58px] min-w-0 flex-col items-center justify-center rounded-md border border-primary/25 bg-bg/55 px-1 py-1 text-center"
                                >
                                    <Icon
                                        aria-hidden
                                        className="size-5 shrink-0"
                                        style={{ color: look.colour }}
                                    />
                                    <span className="mt-1 min-h-6 text-[11px] leading-tight [overflow-wrap:anywhere] text-muted-foreground">
                                        {badge.label}
                                    </span>
                                </li>
                            );
                        })}
                    </ul>
                ) : (
                    <p className="rounded-md border border-dashed border-line bg-bg/45 px-3 py-3 text-center text-xs text-muted-foreground">
                        No badges yet
                    </p>
                )}
            </section>
        </section>
    );
}

function MemberCardBack({ qr }: { qr: string | null }): React.JSX.Element {
    return (
        <section className="flex h-full min-h-0 flex-col items-center justify-center text-center">
            {qr ? (
                <div className="rounded-xl bg-white p-2 shadow-[0_8px_20px_rgb(0_0_0/0.25)]">
                    <img
                        src={qr}
                        alt="QR code for this guild profile"
                        className="size-[172px] max-w-full"
                    />
                </div>
            ) : (
                <div className="grid size-[188px] max-w-full place-items-center rounded-xl border-2 border-dashed border-primary/45 bg-bg/60 p-6 text-sm font-semibold text-primary-soft">
                    <div className="space-y-2">
                        <Lock
                            aria-hidden
                            className="mx-auto size-7 text-primary"
                        />
                        <p>Turn on sharing to get your QR</p>
                    </div>
                </div>
            )}
            <p className="mt-3 text-xs text-muted-foreground">
                Scan to view my guild profile
            </p>
        </section>
    );
}

export function MemberCard({
    member,
    qr,
    side,
    onSideChange,
    className,
    ref,
}: MemberCardProps): React.JSX.Element {
    const { branding } = usePage().props;
    const [uncontrolledSide, setUncontrolledSide] =
        useState<MemberCardSide>('front');
    const currentSide = side ?? uncontrolledSide;
    const [displaySide, setDisplaySide] = useState<MemberCardSide>(currentSide);
    const displaySideRef = useRef<MemberCardSide>(displaySide);
    const isTurningRef = useRef(false);
    const [isTurning, setIsTurning] = useState(false);
    const turnTimers = useRef<number[]>([]);

    useEffect(() => {
        if (currentSide === displaySideRef.current) {
            if (isTurningRef.current) {
                isTurningRef.current = false;
                setIsTurning(false);
            }

            return;
        }

        if (reducedMotion()) {
            const instantTimer = window.setTimeout(() => {
                displaySideRef.current = currentSide;
                setDisplaySide(currentSide);
            }, 0);
            turnTimers.current.push(instantTimer);

            return;
        }

        isTurningRef.current = true;
        const turnStartTimer = window.setTimeout(() => {
            setIsTurning(true);
        }, 0);
        const swapTimer = window.setTimeout(() => {
            displaySideRef.current = currentSide;
            setDisplaySide(currentSide);
            const settleTimer = window.setTimeout(() => {
                isTurningRef.current = false;
                setIsTurning(false);
            }, 250);
            turnTimers.current.push(settleTimer);
        }, 250);
        turnTimers.current.push(turnStartTimer);
        turnTimers.current.push(swapTimer);

        return () => {
            turnTimers.current.forEach((timer) => window.clearTimeout(timer));
            turnTimers.current = [];
        };
    }, [currentSide]);

    const canFlip = side === undefined || onSideChange !== undefined;
    const changeSide = (nextSide: MemberCardSide): void => {
        if (!canFlip || isTurningRef.current) {
            return;
        }

        if (side === undefined) {
            setUncontrolledSide(nextSide);
        }

        onSideChange?.(nextSide);
    };

    const portrait = (
        <Portrait
            src={member.portrait_url ?? undefined}
            name={member.name}
            size="xl"
            className="size-[92px]"
        />
    );

    return (
        <div className="w-[340px] max-w-full">
            <article
                ref={ref}
                aria-label={`${member.name}'s Guild ID`}
                className={cn(
                    'relative flex aspect-[2/3] w-full min-w-0 flex-col overflow-hidden rounded-[22px] border border-primary/55 bg-[radial-gradient(100%_70%_at_50%_0%,color-mix(in_srgb,var(--color-accent)_24%,transparent),transparent_68%),linear-gradient(160deg,var(--color-panel-2),var(--color-bg))] p-4 text-center shadow-[0_0_34px_color-mix(in_srgb,var(--color-primary)_16%,transparent),inset_0_0_0_1px_color-mix(in_srgb,var(--color-primary)_12%,transparent)]',
                    className,
                )}
            >
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-2 rounded-[17px] border border-primary/15"
                />

                <MemberCardHeader branding={branding} />

                <div className="relative mt-2.5 flex shrink-0 flex-col items-center">
                    {canFlip ? (
                        <button
                            type="button"
                            className="rounded-full transition-shadow hover:ring-2 hover:ring-primary focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-bg focus-visible:outline-none"
                            aria-label={
                                currentSide === 'front'
                                    ? 'Show my QR'
                                    : 'Show my stats'
                            }
                            onClick={() => changeSide(flipSide(currentSide))}
                        >
                            {portrait}
                        </button>
                    ) : (
                        portrait
                    )}
                    <h2
                        title={member.name}
                        className={cn(
                            'mt-2 line-clamp-2 max-w-full font-brand leading-[1.08] font-bold [overflow-wrap:anywhere] text-primary-soft [text-shadow:0_2px_0_var(--color-primary-shadow)]',
                            memberNameClass(member.name),
                        )}
                    >
                        {member.name}
                    </h2>
                    <p className="mt-1 text-[11px] font-bold tracking-[0.14em] text-primary uppercase">
                        Level {member.level} · {member.level_title}
                    </p>
                </div>

                <div
                    className={cn(
                        'relative mt-3 min-h-0 flex-1 transition-transform duration-250 [transform-style:preserve-3d] motion-reduce:transition-none',
                        isTurning && '[transform:rotateY(90deg)]',
                    )}
                >
                    {displaySide === 'front' ? (
                        <MemberCardFront member={member} />
                    ) : (
                        <MemberCardBack qr={qr} />
                    )}
                </div>

                {canFlip ? (
                    <p
                        className="mt-2 shrink-0 text-[11px] text-muted-foreground"
                        data-export-hide
                    >
                        Tap the portrait to flip
                    </p>
                ) : null}
            </article>
        </div>
    );
}
