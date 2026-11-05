import { Link } from '@inertiajs/react';
import { QuestEmblem } from '@/components/game/quest-emblem';
import { StatusPill } from '@/components/game/status-pill';
import { XpBadge } from '@/components/game/xp-badge';
import { cn } from '@/lib/utils';
import { show } from '@/routes/quests';
import type { ProfileQuestRow, QuestCategory } from '@/types/quests';

type QuestListRowProps = {
    quest: ProfileQuestRow;
    className?: string;
};

const categoryColours: Record<QuestCategory, string> = {
    research: 'var(--color-research)',
    tooling: 'var(--color-tooling)',
    docs: 'var(--color-docs)',
    experiment: 'var(--color-experiment)',
};

export function QuestListRow({
    quest,
    className,
}: QuestListRowProps): React.JSX.Element {
    return (
        <li className={cn('min-w-0', className)}>
            <Link
                href={show(quest.id)}
                style={
                    {
                        '--c': categoryColours[quest.category],
                    } as React.CSSProperties
                }
                className="grid min-w-0 grid-cols-[auto_minmax(0,1fr)_auto_auto] items-center gap-3.5 overflow-hidden rounded-xl border border-l-[3px] border-line border-l-(--c) bg-bg/55 px-3.5 py-3 text-inherit no-underline transition-[border-color,box-shadow] hover:border-(--c) hover:shadow-[0_0_16px_color-mix(in_srgb,var(--c)_22%,transparent)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary max-[640px]:grid-cols-[auto_minmax(0,1fr)_auto] max-[640px]:gap-2.5"
            >
                <QuestEmblem
                    category={quest.category}
                    size="sm"
                    className="max-[640px]:row-span-2"
                />
                <span className="min-w-0 max-[640px]:col-start-2 max-[640px]:row-start-1">
                    <strong className="block truncate font-title text-[19px] leading-tight font-semibold">
                        {quest.title}
                    </strong>
                    <small className="mt-0.5 block truncate text-[12.5px] text-muted-foreground">
                        {quest.detail}
                    </small>
                </span>
                <StatusPill
                    status={quest.status}
                    className="max-[640px]:col-start-2 max-[640px]:row-start-2 max-[640px]:justify-self-start"
                />
                {quest.xp !== null && (
                    <XpBadge
                        amount={quest.xp}
                        size="sm"
                        compact
                        className="min-w-16 justify-end rounded-full border border-primary/30 bg-primary/10 px-2 py-1 max-[640px]:col-start-3 max-[640px]:row-span-2 max-[640px]:row-start-1"
                    />
                )}
            </Link>
        </li>
    );
}
