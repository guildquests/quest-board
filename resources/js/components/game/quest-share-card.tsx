import { usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { KeyboardEvent, Ref } from 'react';
import { DifficultyBadge } from '@/components/game/difficulty-badge';
import { Portrait } from '@/components/game/portrait';
import { QuestEmblem } from '@/components/game/quest-emblem';
import { XpBadge } from '@/components/game/xp-badge';
import { flipSide, reducedMotion } from '@/lib/card-flip';
import type { CardSide } from '@/lib/card-flip';
import { cn } from '@/lib/utils';
import type { QuestCategory, QuestDifficulty } from '@/types/quests';

export type QuestShareCardSide = CardSide;

export type QuestShareCardQuest = {
    title: string;
    /** Why it matters. */
    summary: string;
    category: QuestCategory;
    category_label: string;
    difficulty: QuestDifficulty;
    difficulty_label: string;
    xp: number;
    poster: {
        name: string;
        portrait_url: string | null;
        level: number;
        level_title: string;
    };
};

export type QuestShareCardProps = {
    quest: QuestShareCardQuest;
    qr: string;
    side?: QuestShareCardSide;
    onSideChange?: (side: QuestShareCardSide) => void;
    className?: string;
    ref?: Ref<HTMLElement>;
};

function questTitleClass(title: string): string {
    if (title.length <= 28) {
        return 'text-[22px]';
    }

    if (title.length <= 56) {
        return 'text-lg';
    }

    return 'text-[15px]';
}

function QuestShareCardFront({
    quest,
}: {
    quest: QuestShareCardQuest;
}): React.JSX.Element {
    return (
        <section className="flex h-full min-h-0 flex-col text-left">
            <ul
                className="flex shrink-0 flex-wrap items-center justify-center gap-1.5"
                aria-label="Quest details"
            >
                <li className="rounded-full border border-line bg-bg/60 px-2.5 py-1 text-xs font-semibold">
                    {quest.category_label}
                </li>
                <li className="rounded-full border border-line bg-bg/60 px-2.5 py-1">
                    <DifficultyBadge
                        difficulty={quest.difficulty}
                        className="text-xs [&_svg]:size-3.5"
                    />
                </li>
                <li className="rounded-full border border-primary/30 bg-bg/60 px-2.5 py-1">
                    <XpBadge amount={quest.xp} size="sm" />
                </li>
            </ul>

            <div className="mt-3 min-h-0 flex-1">
                <h3 className="text-[11px] font-bold tracking-[0.12em] text-primary-soft uppercase">
                    Why it matters
                </h3>
                <p className="mt-1 line-clamp-4 text-xs leading-relaxed [overflow-wrap:anywhere] text-muted-foreground">
                    {quest.summary}
                </p>
            </div>

            <div className="mt-2 flex shrink-0 items-center gap-2.5 rounded-lg border border-line bg-bg/60 p-2">
                <Portrait
                    src={quest.poster.portrait_url ?? undefined}
                    name={quest.poster.name}
                    size="sm"
                />
                <div className="min-w-0">
                    <p className="text-[10px] font-semibold text-muted-foreground">
                        Quest giver
                    </p>
                    <p
                        title={quest.poster.name}
                        className="truncate text-sm leading-tight font-bold"
                    >
                        {quest.poster.name}
                    </p>
                    <p className="truncate text-[11px] text-primary">
                        Level {quest.poster.level} · {quest.poster.level_title}
                    </p>
                </div>
            </div>
        </section>
    );
}

function QuestShareCardBack({ qr }: { qr: string }): React.JSX.Element {
    return (
        <section className="flex h-full min-h-0 flex-col items-center justify-center text-center">
            <div className="rounded-xl bg-white p-2 shadow-[0_8px_20px_rgb(0_0_0/0.25)]">
                <img
                    src={qr}
                    alt="QR code for this quest"
                    className="size-[180px] max-w-full"
                />
            </div>
            <p className="mt-3 text-xs text-muted-foreground">
                Scan to view this quest
            </p>
        </section>
    );
}

export function QuestShareCard({
    quest,
    qr,
    side,
    onSideChange,
    className,
    ref,
}: QuestShareCardProps): React.JSX.Element {
    const { branding } = usePage().props;
    const [uncontrolledSide, setUncontrolledSide] =
        useState<QuestShareCardSide>('front');
    const currentSide = side ?? uncontrolledSide;
    const [displaySide, setDisplaySide] =
        useState<QuestShareCardSide>(currentSide);
    const displaySideRef = useRef<QuestShareCardSide>(displaySide);
    const isTurningRef = useRef(false);
    const [isTurning, setIsTurning] = useState(false);

    useEffect(() => {
        if (currentSide === displaySideRef.current) {
            if (isTurningRef.current) {
                isTurningRef.current = false;
                setIsTurning(false);
            }

            return;
        }

        const timers: number[] = [];

        if (reducedMotion()) {
            timers.push(
                window.setTimeout(() => {
                    displaySideRef.current = currentSide;
                    setDisplaySide(currentSide);
                }, 0),
            );
        } else {
            isTurningRef.current = true;
            timers.push(window.setTimeout(() => setIsTurning(true), 0));
            timers.push(
                window.setTimeout(() => {
                    displaySideRef.current = currentSide;
                    setDisplaySide(currentSide);
                    timers.push(
                        window.setTimeout(() => {
                            isTurningRef.current = false;
                            setIsTurning(false);
                        }, 250),
                    );
                }, 250),
            );
        }

        return () => timers.forEach((timer) => window.clearTimeout(timer));
    }, [currentSide]);

    const canFlip = side === undefined || onSideChange !== undefined;

    const changeSide = (nextSide: QuestShareCardSide): void => {
        if (!canFlip || isTurningRef.current) {
            return;
        }

        if (side === undefined) {
            setUncontrolledSide(nextSide);
        }

        onSideChange?.(nextSide);
    };

    const handleKeyDown = (event: KeyboardEvent<HTMLElement>): void => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            changeSide(flipSide(currentSide));
        }
    };

    const flipProps = canFlip
        ? {
              role: 'button',
              tabIndex: 0,
              'aria-label':
                  currentSide === 'front'
                      ? `Show the QR code for ${quest.title}`
                      : `Show the details for ${quest.title}`,
              onClick: () => changeSide(flipSide(currentSide)),
              onKeyDown: handleKeyDown,
          }
        : { 'aria-label': `${quest.title} quest card` };

    return (
        <div className="w-[340px] max-w-full">
            <article
                ref={ref}
                {...flipProps}
                className={cn(
                    'relative flex aspect-[2/3] w-full min-w-0 flex-col overflow-hidden rounded-[22px] border border-primary/55 bg-[radial-gradient(100%_70%_at_50%_0%,color-mix(in_srgb,var(--color-accent)_24%,transparent),transparent_68%),linear-gradient(160deg,var(--color-panel-2),var(--color-bg))] p-4 text-center shadow-[0_0_34px_color-mix(in_srgb,var(--color-primary)_16%,transparent),inset_0_0_0_1px_color-mix(in_srgb,var(--color-primary)_12%,transparent)]',
                    canFlip &&
                        'cursor-pointer focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-bg focus-visible:outline-none',
                    className,
                )}
            >
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-2 rounded-[17px] border border-primary/15"
                />

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

                <div className="relative mt-3 flex shrink-0 flex-col items-center">
                    <QuestEmblem category={quest.category} size="lg" />
                    <h2
                        title={quest.title}
                        className={cn(
                            'mt-2.5 line-clamp-3 max-w-full font-title leading-[1.1] font-bold [overflow-wrap:anywhere] text-primary-soft [text-shadow:0_2px_0_var(--color-primary-shadow)]',
                            questTitleClass(quest.title),
                        )}
                    >
                        {quest.title}
                    </h2>
                </div>

                <div
                    className={cn(
                        'relative mt-3 min-h-0 flex-1 transition-transform duration-250 [transform-style:preserve-3d] motion-reduce:transition-none',
                        isTurning && '[transform:rotateY(90deg)]',
                    )}
                >
                    {displaySide === 'front' ? (
                        <QuestShareCardFront quest={quest} />
                    ) : (
                        <QuestShareCardBack qr={qr} />
                    )}
                </div>

                {canFlip ? (
                    <p
                        className="mt-2 shrink-0 text-[11px] text-muted-foreground"
                        data-export-hide
                    >
                        Tap the card to flip
                    </p>
                ) : null}
            </article>
        </div>
    );
}
