import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Clock,
    History,
    ListChecks,
    PackageCheck,
    Share2,
    ScrollText,
} from 'lucide-react';
import { useState } from 'react';
import { ActivityLog } from '@/components/game/activity-log';
import { DifficultyBadge } from '@/components/game/difficulty-badge';
import { GameCard } from '@/components/game/game-card';
import { ObjectiveList } from '@/components/game/objective-list';
import { Portrait } from '@/components/game/portrait';
import { QuestEmblem } from '@/components/game/quest-emblem';
import type { QuestShareCardQuest } from '@/components/game/quest-share-card';
import { QuestShareDialog } from '@/components/game/quest-share-dialog';
import { QuestActionPanel } from '@/components/quests/quest-action-panel';
import { Button } from '@/components/ui/button';
import { board } from '@/routes';
import type { LeveledPerson, QuestShowPageProps } from '@/types/quests';

function Block({
    title,
    icon: Icon,
    children,
}: {
    title: string;
    icon: typeof ScrollText;
    children: React.ReactNode;
}): React.JSX.Element {
    return (
        <section className="mt-6">
            <h3 className="mb-2.5 flex items-center gap-2.5 font-title text-[22px] font-bold text-primary-soft">
                <Icon aria-hidden className="size-[18px] text-primary" />
                {title}
            </h3>
            {children}
        </section>
    );
}

function Person({ person }: { person: LeveledPerson }): React.JSX.Element {
    return (
        <div className="flex items-center gap-2.5">
            <Portrait
                src={person.portrait_url ?? undefined}
                name={person.name}
                size="sm"
            />
            <div className="min-w-0">
                <b className="block font-semibold [overflow-wrap:anywhere]">
                    {person.name}
                </b>
                <small className="text-[13px] text-muted-foreground">
                    Level {person.level} · {person.level_title}
                </small>
            </div>
        </div>
    );
}

const panelClassName = 'rounded-2xl border border-line bg-panel p-5';
const labelClassName =
    'mb-2 block text-xs font-bold tracking-widest text-muted-foreground uppercase';

export default function QuestShow({
    quest,
    events,
    can,
    acceptBlockedReason,
    claimDays,
    share,
}: QuestShowPageProps): React.JSX.Element {
    const [shareOpen, setShareOpen] = useState(false);
    const shareQuest: QuestShareCardQuest = {
        title: quest.title,
        summary: quest.summary,
        category: quest.category,
        category_label: quest.category_label,
        difficulty: quest.difficulty,
        difficulty_label: quest.difficulty_label,
        xp: quest.reward_xp,
        poster: quest.poster,
    };

    const categoryColor = `var(--color-${quest.category})`;

    return (
        <>
            <Head title={quest.title} />

            <div className="grid gap-[18px] pb-8">
                <Link
                    href={board()}
                    className="inline-flex items-center gap-2 justify-self-start text-sm font-semibold text-muted-foreground no-underline hover:text-primary-soft"
                >
                    <ArrowLeft aria-hidden className="size-4" />
                    Back to the board
                </Link>

                <div className="grid grid-cols-12 items-start gap-[22px] max-[960px]:grid-cols-1 max-[960px]:gap-4">
                    <GameCard
                        accent="var(--color-accent)"
                        className="col-span-8 min-w-0 p-6 max-[960px]:col-span-1"
                    >
                        <div className="flex items-start gap-4">
                            <QuestEmblem category={quest.category} size="lg" />
                            <div className="min-w-0">
                                <span
                                    className="mb-1 inline-block text-[11px] font-bold tracking-[0.12em] uppercase"
                                    style={{ color: categoryColor }}
                                >
                                    {quest.category_label}
                                </span>
                                <h2 className="font-title text-[clamp(28px,4vw,38px)] leading-[1.1] font-bold text-balance [overflow-wrap:anywhere]">
                                    {quest.title}
                                </h2>
                                <p className="mt-2 text-base [overflow-wrap:anywhere] text-muted-foreground">
                                    {quest.summary}
                                </p>
                            </div>
                        </div>

                        <Block title="The brief" icon={ScrollText}>
                            <p className="leading-relaxed [overflow-wrap:anywhere] whitespace-pre-line">
                                {quest.brief}
                            </p>
                        </Block>

                        <Block title="Objectives" icon={ListChecks}>
                            <ObjectiveList
                                objectives={quest.objectives}
                                ticked={
                                    quest.status === 'in_review' ||
                                    quest.status === 'done'
                                }
                            />
                        </Block>

                        <Block title="Proof of completion" icon={PackageCheck}>
                            <p className="leading-relaxed [overflow-wrap:anywhere]">
                                {quest.proof}
                            </p>
                        </Block>

                        <Block title="Activity" icon={History}>
                            <ActivityLog events={events} />
                        </Block>
                    </GameCard>

                    {/* On phones the side column dissolves so the action panel can sit above the brief. */}
                    <aside className="col-span-4 grid gap-4 max-[960px]:col-span-1 max-[960px]:contents">
                        <QuestActionPanel
                            quest={quest}
                            can={can}
                            acceptBlockedReason={acceptBlockedReason}
                            claimDays={claimDays}
                            className="max-[960px]:order-first"
                        />

                        <Button
                            type="button"
                            variant="outline"
                            className="w-full max-[960px]:order-first"
                            onClick={() => setShareOpen(true)}
                        >
                            <Share2 aria-hidden />
                            Share quest
                        </Button>

                        <QuestShareDialog
                            quest={shareQuest}
                            qr={share.qr}
                            publicUrl={share.public_url}
                            open={shareOpen}
                            onOpenChange={setShareOpen}
                        />

                        <div className={panelClassName}>
                            <dl className="grid gap-3 text-[14.5px] [&_dd]:m-0 [&_dd]:inline-flex [&_dd]:items-center [&_dd]:gap-[7px] [&_dd]:font-semibold [&_dt]:text-muted-foreground [&>div]:flex [&>div]:items-center [&>div]:justify-between [&>div]:gap-3">
                                <div>
                                    <dt>Difficulty</dt>
                                    <dd>
                                        <DifficultyBadge
                                            difficulty={quest.difficulty}
                                        />
                                    </dd>
                                </div>
                                <div>
                                    <dt>Effort</dt>
                                    <dd>
                                        <Clock
                                            aria-hidden
                                            className="size-4 text-muted-foreground"
                                        />
                                        {quest.effort}
                                    </dd>
                                </div>
                                <div>
                                    <dt>Category</dt>
                                    <dd style={{ color: categoryColor }}>
                                        {quest.category_label}
                                    </dd>
                                </div>
                                <div>
                                    <dt>Status</dt>
                                    <dd>{quest.status_label}</dd>
                                </div>
                            </dl>
                        </div>

                        <div className={`${panelClassName} grid gap-4`}>
                            <div>
                                <span className={labelClassName}>
                                    Quest giver
                                </span>
                                <Person person={quest.poster} />
                            </div>
                            <div>
                                <span className={labelClassName}>
                                    Adventurer
                                </span>
                                {quest.adventurer ? (
                                    <Person person={quest.adventurer} />
                                ) : (
                                    <span className="text-muted-foreground">
                                        Not claimed yet
                                    </span>
                                )}
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </>
    );
}
