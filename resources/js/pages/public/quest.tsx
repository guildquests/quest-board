import { Head, Link } from '@inertiajs/react';
import {
    Clock,
    ListChecks,
    PackageCheck,
    ScrollText,
    UserRound,
} from 'lucide-react';
import { DifficultyBadge } from '@/components/game/difficulty-badge';
import { GameCard } from '@/components/game/game-card';
import { ObjectiveList } from '@/components/game/objective-list';
import { Portrait } from '@/components/game/portrait';
import { QuestEmblem } from '@/components/game/quest-emblem';
import { StatusPill } from '@/components/game/status-pill';
import { XpBadge } from '@/components/game/xp-badge';
import { show } from '@/routes/quests';
import type { PublicQuestPageProps, PublicQuestPerson } from '@/types/quests';

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
        <section className="mt-7">
            <h2 className="mb-2.5 flex items-center gap-2.5 font-title text-[22px] font-bold text-primary-soft">
                <Icon aria-hidden className="size-[18px] text-primary" />
                {title}
            </h2>
            {children}
        </section>
    );
}

function Person({
    label,
    person,
}: {
    label: string;
    person: PublicQuestPerson;
}): React.JSX.Element {
    return (
        <div className="flex items-center gap-2.5">
            <Portrait
                src={person.portrait_url ?? undefined}
                name={person.name}
                size="sm"
            />
            <div className="min-w-0">
                <span className="block text-xs font-bold tracking-widest text-muted-foreground uppercase">
                    {label}
                </span>
                <b className="block [overflow-wrap:anywhere]">{person.name}</b>
                <small className="text-[13px] text-muted-foreground">
                    Level {person.level} · {person.level_title}
                </small>
            </div>
        </div>
    );
}

const panelClassName = 'rounded-2xl border border-line bg-panel p-5';

export default function PublicQuest({
    quest,
}: PublicQuestPageProps): React.JSX.Element {
    const categoryColor = 'var(--color-' + quest.category + ')';

    return (
        <>
            <Head title={quest.title}>
                <meta name="robots" content="noindex" />
            </Head>

            <div className="mx-auto grid max-w-[860px] gap-5 pb-8">
                <GameCard
                    accent={categoryColor}
                    className="border-accent/45 bg-linear-to-b from-panel-2 to-panel p-6 sm:p-8"
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
                            <h1 className="font-title text-[clamp(28px,6vw,44px)] leading-[1.1] font-bold text-balance [overflow-wrap:anywhere]">
                                {quest.title}
                            </h1>
                            <p className="mt-2 text-base [overflow-wrap:anywhere] text-muted-foreground">
                                {quest.summary}
                            </p>
                        </div>
                    </div>

                    <div className="mt-6 grid grid-cols-2 gap-3 border-y border-line py-4 text-sm font-semibold sm:grid-cols-4">
                        <div className="flex items-center justify-center gap-2 sm:border-r sm:border-line">
                            <DifficultyBadge difficulty={quest.difficulty} />
                        </div>
                        <div className="flex items-center justify-center gap-1.5 text-foreground sm:border-r sm:border-line">
                            <Clock
                                aria-hidden
                                className="size-4 text-muted-foreground"
                            />
                            {quest.effort}
                        </div>
                        <div
                            className="flex items-center justify-center text-(--c)"
                            style={
                                { '--c': categoryColor } as React.CSSProperties
                            }
                        >
                            {quest.category_label}
                        </div>
                        <div className="flex items-center justify-center">
                            <XpBadge amount={quest.xp} />
                        </div>
                    </div>

                    <Block title="Why it matters" icon={ScrollText}>
                        <p className="leading-relaxed [overflow-wrap:anywhere] whitespace-pre-line">
                            {quest.summary}
                        </p>
                    </Block>

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
                </GameCard>

                <div className="grid gap-4 sm:grid-cols-2">
                    <section className={panelClassName + ' grid gap-4'}>
                        <div className="flex items-center justify-between gap-3">
                            <span className="text-xs font-bold tracking-widest text-muted-foreground uppercase">
                                Status
                            </span>
                            <StatusPill status={quest.status} />
                        </div>
                        <Person label="Quest giver" person={quest.poster} />
                        {quest.completed_by && (
                            <Person
                                label="Completed by"
                                person={quest.completed_by}
                            />
                        )}
                    </section>

                    <section
                        className={
                            panelClassName + ' flex flex-col justify-center'
                        }
                    >
                        {quest.status === 'open' ? (
                            <>
                                <div className="flex items-start gap-3">
                                    <UserRound
                                        aria-hidden
                                        className="mt-0.5 size-5 shrink-0 text-accent"
                                    />
                                    <div>
                                        <h2 className="font-title text-xl font-bold text-primary-soft">
                                            Ready to take this quest?
                                        </h2>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            You&apos;ll come back here after you
                                            log in.
                                        </p>
                                    </div>
                                </div>
                                <Link
                                    href={show(quest.id)}
                                    className="mt-5 inline-flex min-h-11 items-center justify-center rounded-lg border border-accent/60 bg-accent-deep/55 px-4 py-2.5 text-sm font-semibold text-foreground transition-[border-color,box-shadow] hover:border-accent hover:shadow-[0_0_16px_color-mix(in_srgb,var(--color-accent)_28%,transparent)]"
                                >
                                    Log in to accept
                                </Link>
                            </>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                This quest is read-only while it is{' '}
                                {quest.status_label.toLowerCase()}.
                            </p>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}
