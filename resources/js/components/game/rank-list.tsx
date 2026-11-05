import { EmptyState } from '@/components/game/empty-state';
import { Portrait } from '@/components/game/portrait';
import { cn } from '@/lib/utils';
import type { RankedAdventurer } from '@/types/hall-of-fame';

type RankListProps = {
    adventurers: RankedAdventurer[];
    className?: string;
};

const rankClasses: Record<number, string> = {
    1: 'text-primary-soft',
    2: 'text-[#c8cdd8]',
    3: 'text-[#c48762]',
};

export function RankList({
    adventurers,
    className,
}: RankListProps): React.JSX.Element {
    if (adventurers.length === 0) {
        return (
            <EmptyState
                title="No one has earned XP yet. Finish a quest to get on the board."
                className={className}
            />
        );
    }

    const firstXp = adventurers[0]?.xp ?? 1;

    return (
        <ol className={cn('space-y-2', className)}>
            {adventurers.map((adventurer) => {
                const rankColor =
                    rankClasses[adventurer.rank] ?? 'text-muted-foreground';
                const questsLabel =
                    adventurer.quests_done === 1 ? 'quest' : 'quests';
                const meterWidth = Math.min(
                    100,
                    (adventurer.xp / firstXp) * 100,
                );

                return (
                    <li
                        key={adventurer.person.id}
                        className={cn(
                            'flex min-w-0 items-center gap-3 rounded-xl border border-line bg-panel-2/45 px-3 py-2.5',
                            adventurer.rank === 1 &&
                                'border-primary/55 bg-primary/5',
                        )}
                    >
                        <span
                            className={cn(
                                'w-5 shrink-0 text-center font-title text-xl font-bold tabular-nums',
                                rankColor,
                            )}
                            aria-label={`Rank ${adventurer.rank}`}
                        >
                            {adventurer.rank}
                        </span>
                        <Portrait
                            src={adventurer.person.portrait_url ?? undefined}
                            name={adventurer.person.name}
                            size="sm"
                        />
                        <div className="min-w-0 flex-1">
                            <p className="truncate font-semibold text-text">
                                {adventurer.person.name}
                            </p>
                            <p className="truncate text-[13px] text-muted-foreground">
                                Level {adventurer.person.level} ·{' '}
                                {adventurer.person.level_title}
                            </p>
                        </div>
                        <div className="w-28 shrink-0 max-[640px]:hidden">
                            <p className="text-right text-xs text-muted-foreground">
                                {adventurer.quests_done} {questsLabel}
                            </p>
                            <div
                                role="meter"
                                aria-label={`${adventurer.person.name} XP compared with the top adventurer`}
                                aria-valuemin={0}
                                aria-valuemax={firstXp}
                                aria-valuenow={adventurer.xp}
                                className="mt-1 h-1.5 overflow-hidden rounded-full bg-line"
                            >
                                <span
                                    className="block h-full rounded-full bg-primary-soft"
                                    style={{ width: `${meterWidth}%` }}
                                />
                            </div>
                        </div>
                        <span className="shrink-0 text-sm font-bold text-primary-soft tabular-nums">
                            {adventurer.xp} XP
                        </span>
                    </li>
                );
            })}
        </ol>
    );
}
