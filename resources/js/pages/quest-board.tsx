import { Head, Link, router } from '@inertiajs/react';
import { CirclePlus, SlidersHorizontal, Tag } from 'lucide-react';
import { useRef, useState } from 'react';
import { EmptyState } from '@/components/game/empty-state';
import { FilterChips } from '@/components/game/filter-chips';
import { QuestBanner } from '@/components/game/quest-banner';
import { QuestCard } from '@/components/game/quest-card';
import { QuestGrid } from '@/components/game/quest-grid';
import { RewardDialog } from '@/components/game/reward-dialog';
import { Button } from '@/components/ui/button';
import { categoryIcons, statusIcons } from '@/lib/quest-icons';
import { board, hallOfFame, me } from '@/routes';
import { create, show } from '@/routes/quests';
import { seen } from '@/routes/rewards';
import type {
    QuestBoardPageProps,
    QuestCategory,
    QuestStatus,
} from '@/types/quests';

export default function QuestBoard({
    quests,
    filters,
    statuses,
    categories,
    latestCompletion,
    rewards,
}: QuestBoardPageProps): React.JSX.Element {
    const [closingRewardKey, setClosingRewardKey] = useState<string | null>(
        null,
    );
    const seenPostInFlight = useRef(false);

    const reward =
        rewards.mode === 'single'
            ? (rewards.items[0] ?? null)
            : rewards.mode === 'summary'
              ? rewards.summary
              : null;
    const rewardKey =
        rewards.mode === 'single'
            ? (rewards.items[0]?.id.toString() ?? null)
            : rewards.mode === 'summary'
              ? rewards.items.join(',')
              : null;

    // `after` runs once the seen post has finished, so a link click cannot cancel it.
    const closeReward = (after?: () => void): void => {
        if (seenPostInFlight.current || rewardKey === null) {
            return;
        }

        const ids =
            rewards.mode === 'single'
                ? rewards.items[0]
                    ? [rewards.items[0].id]
                    : []
                : rewards.mode === 'summary'
                  ? rewards.items
                  : [];

        if (ids.length === 0) {
            return;
        }

        seenPostInFlight.current = true;
        setClosingRewardKey(rewardKey);

        router.post(
            seen(),
            { ids },
            {
                preserveScroll: true,
                onError: () => setClosingRewardKey(null),
                onCancel: () => setClosingRewardKey(null),
                onFinish: () => {
                    seenPostInFlight.current = false;
                    after?.();
                },
            },
        );
    };

    // Each change is a visit with the new query string, so filters survive a reload and can be linked.
    const visit = (next: Partial<typeof filters>): void => {
        const query = { ...filters, ...next };

        router.visit(
            board({
                query: {
                    ...(query.status && { status: query.status }),
                    ...(query.category && { category: query.category }),
                },
            }),
            { preserveScroll: true, preserveState: true },
        );
    };

    return (
        <>
            {/* No page title: the tab shows the app name alone. */}
            <Head />

            <div className="grid gap-[18px] pb-8">
                {latestCompletion && latestCompletion.xp !== null && (
                    <QuestBanner
                        name={latestCompletion.adventurer.name}
                        questTitle={latestCompletion.title}
                        questHref={show(latestCompletion.id)}
                        xp={latestCompletion.xp}
                        fameHref={hallOfFame()}
                    />
                )}

                <section
                    aria-label="Filters"
                    className="flex flex-wrap items-center gap-x-[18px] gap-y-2.5"
                >
                    <FilterChips
                        label="Status"
                        icon={SlidersHorizontal}
                        allLabel="All"
                        value={filters.status}
                        onValueChange={(status) =>
                            visit({ status: status as QuestStatus | null })
                        }
                        options={statuses.map((status) => ({
                            value: status.value,
                            label: status.label,
                            icon: statusIcons[status.value],
                        }))}
                    />
                    <span
                        aria-hidden
                        className="h-[30px] w-px bg-line max-[520px]:hidden"
                    />
                    <FilterChips
                        label="Category"
                        icon={Tag}
                        clearable
                        value={filters.category}
                        onValueChange={(category) =>
                            visit({
                                category: category as QuestCategory | null,
                            })
                        }
                        options={categories.map((category) => ({
                            value: category.value,
                            label: category.label,
                            icon: categoryIcons[category.value],
                            color: `var(--color-${category.value})`,
                        }))}
                    />
                    <Button
                        asChild
                        size="lg"
                        className="ml-auto max-[1100px]:ml-0 max-[520px]:w-full"
                    >
                        <Link href={create()}>
                            <CirclePlus aria-hidden />
                            Post a quest
                        </Link>
                    </Button>
                </section>

                <section aria-label="Quests">
                    <QuestGrid
                        empty={
                            <EmptyState
                                title="No quests match these filters."
                                message="Clear a filter, or post a quest."
                            />
                        }
                    >
                        {quests.map((quest) => (
                            <QuestCard
                                key={quest.id}
                                title={quest.title}
                                description={quest.summary}
                                category={quest.category}
                                difficulty={quest.difficulty}
                                effort={quest.effort}
                                xp={quest.xp}
                                status={quest.status}
                                owner={{
                                    name: quest.poster.name,
                                    portrait:
                                        quest.poster.portrait_url ?? undefined,
                                }}
                                claimant={
                                    quest.adventurer
                                        ? {
                                              name: quest.adventurer.name,
                                              portrait:
                                                  quest.adventurer
                                                      .portrait_url ??
                                                  undefined,
                                          }
                                        : undefined
                                }
                                href={show(quest.id)}
                            />
                        ))}
                    </QuestGrid>
                </section>
            </div>

            <RewardDialog
                key={rewardKey ?? 'none'}
                reward={reward}
                open={reward !== null && rewardKey !== closingRewardKey}
                onClose={() => closeReward()}
                onNavigate={(href) =>
                    closeReward(() => {
                        if (href !== undefined) {
                            router.visit(href);
                        }
                    })
                }
                profileHref={me()}
                boardHref={board()}
            />
        </>
    );
}
