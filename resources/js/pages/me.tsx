import { Head, Link } from '@inertiajs/react';
import { Medal, ScrollText, UserRound } from 'lucide-react';
import { useState } from 'react';
import { AchievementBadge } from '@/components/game/achievement-badge';
import { EmptyState } from '@/components/game/empty-state';
import { GameCard } from '@/components/game/game-card';
import { MemberCardDialog } from '@/components/game/member-card-dialog';
import { ProfileHero } from '@/components/game/profile-hero';
import { QuestListRow } from '@/components/game/quest-list-row';
import { SectionHeading } from '@/components/game/section-heading';
import { Button } from '@/components/ui/button';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { badgeLook, progressText } from '@/lib/badges';
import { board } from '@/routes';
import { create } from '@/routes/quests';
import type { ProfilePageProps } from '@/types/quests';

type QuestList = keyof ProfilePageProps['quests'];

const questLists: QuestList[] = ['active', 'completed', 'posted'];

const questListLabels: Record<QuestList, string> = {
    active: 'Active',
    completed: 'Completed',
    posted: 'Posted',
};

const questTabClassName =
    'h-auto rounded-full first:rounded-full last:rounded-full border border-line bg-transparent px-3 py-1.5 text-sm font-semibold text-muted-foreground transition-[border-color,background-color,color,box-shadow] hover:border-accent/60 hover:bg-accent/10 hover:text-foreground data-[state=on]:border-accent data-[state=on]:bg-accent-deep data-[state=on]:text-foreground data-[state=on]:shadow-[0_0_12px_color-mix(in_srgb,var(--color-accent)_25%,transparent)] max-[520px]:min-w-0 max-[520px]:flex-1 max-[520px]:px-2';

export default function Me({
    profile,
    level,
    stats,
    quests,
    badges,
    poster_xp,
    card,
}: ProfilePageProps): React.JSX.Element {
    const [selectedList, setSelectedList] = useState<QuestList>('active');
    const selectedQuests = quests[selectedList];

    return (
        <>
            <Head title="Status Window" />

            <div className="grid min-w-0 gap-[22px] pb-8">
                <ProfileHero
                    profile={profile}
                    level={level}
                    stats={stats}
                    action={
                        <MemberCardDialog
                            member={card.member}
                            publicUrl={card.public_url}
                            qr={card.qr}
                            sharingEnabled={card.sharing_enabled}
                            trigger={
                                <Button
                                    variant="outline"
                                    className="shrink-0 max-[640px]:w-full"
                                >
                                    <UserRound aria-hidden />
                                    Guild ID
                                </Button>
                            }
                        />
                    }
                />

                <div className="grid min-w-0 grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] items-start gap-[22px] max-[1100px]:grid-cols-1">
                    <GameCard className="min-w-0 bg-panel max-[520px]:p-4">
                        <section aria-label="My quests">
                            <SectionHeading
                                title="My quests"
                                icon={ScrollText}
                                className="mb-3.5"
                                action={
                                    <ToggleGroup
                                        type="single"
                                        aria-label="My quest list"
                                        value={selectedList}
                                        onValueChange={(value) => {
                                            if (value) {
                                                setSelectedList(
                                                    value as QuestList,
                                                );
                                            }
                                        }}
                                        className="gap-2 max-[520px]:w-full max-[520px]:gap-1.5"
                                    >
                                        {questLists.map((list) => (
                                            <ToggleGroupItem
                                                key={list}
                                                value={list}
                                                className={questTabClassName}
                                            >
                                                {questListLabels[list]}{' '}
                                                <span className="rounded-full bg-white/8 px-1.5 text-xs text-muted-foreground data-[state=on]:bg-bg/50 data-[state=on]:text-foreground">
                                                    {quests[list].length}
                                                </span>
                                            </ToggleGroupItem>
                                        ))}
                                    </ToggleGroup>
                                }
                            />

                            {selectedQuests.length > 0 ? (
                                <ul className="grid min-w-0 gap-2">
                                    {selectedQuests.map((quest) => (
                                        <QuestListRow
                                            key={quest.id}
                                            quest={quest}
                                        />
                                    ))}
                                </ul>
                            ) : (
                                <ProfileEmptyState list={selectedList} />
                            )}

                            <p className="mt-3 text-[13px] text-muted-foreground">
                                You get{' '}
                                <strong className="text-primary-soft">
                                    +{poster_xp} XP
                                </strong>{' '}
                                when a quest you posted is completed.
                            </p>
                        </section>
                    </GameCard>

                    <GameCard className="min-w-0 bg-panel max-[520px]:p-4">
                        <section aria-label="Badges">
                            <SectionHeading
                                title="Badges"
                                icon={Medal}
                                action={
                                    <span className="text-[13.5px] text-muted-foreground">
                                        {stats.badges_earned} of{' '}
                                        {stats.badges_total} earned
                                    </span>
                                }
                                className="mb-4"
                            />

                            <ul className="grid grid-cols-3 gap-x-2.5 gap-y-3.5 sm:grid-cols-5">
                                {badges.map((badge) => (
                                    <li key={badge.key} className="min-w-0">
                                        <AchievementBadge
                                            className="w-full"
                                            name={badge.label}
                                            description={badge.description}
                                            icon={badgeLook[badge.key].icon}
                                            colour={badgeLook[badge.key].colour}
                                            unlocked={badge.earned}
                                            progress={{
                                                current: badge.current,
                                                target: badge.target,
                                                text: progressText(badge),
                                            }}
                                        />
                                    </li>
                                ))}
                            </ul>
                        </section>
                    </GameCard>
                </div>
            </div>
        </>
    );
}

function ProfileEmptyState({ list }: { list: QuestList }): React.JSX.Element {
    if (list === 'active') {
        return (
            <EmptyState
                title="No active quest."
                action={
                    <Link
                        href={board()}
                        className="font-semibold text-accent underline decoration-accent/50 underline-offset-2 hover:text-accent/80"
                    >
                        Pick one from the board.
                    </Link>
                }
            />
        );
    }

    if (list === 'completed') {
        return (
            <EmptyState
                title="No completed quests yet."
                message="Your first one is waiting on the board."
            />
        );
    }

    return (
        <EmptyState
            title="You haven't posted a quest yet."
            action={
                <Link
                    href={create()}
                    className="font-semibold text-accent underline decoration-accent/50 underline-offset-2 hover:text-accent/80"
                >
                    Post a quest.
                </Link>
            }
        />
    );
}
