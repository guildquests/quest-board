import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { Clock } from 'lucide-react';
import type { ReactNode } from 'react';
import { DifficultyBadge } from '@/components/game/difficulty-badge';
import { GameCard } from '@/components/game/game-card';
import { Portrait } from '@/components/game/portrait';
import { QuestEmblem } from '@/components/game/quest-emblem';
import { StatusPill } from '@/components/game/status-pill';
import { XpBadge } from '@/components/game/xp-badge';
import { cn } from '@/lib/utils';
import type {
    QuestCategory,
    QuestDifficulty,
    QuestStatus,
} from '@/types/quests';

export type QuestPerson = {
    name: string;
    portrait?: string;
};

export type QuestCardProps = {
    title: string;
    description: string;
    category: QuestCategory;
    difficulty: QuestDifficulty;
    /** Effort label, e.g. "~1 hr". */
    effort: string;
    xp: number;
    status: QuestStatus;
    owner: QuestPerson;
    claimant?: QuestPerson;
    /** Already formatted in the display timezone. */
    dateLabel?: string;
    action?: ReactNode;
    href?: InertiaLinkProps['href'];
    className?: string;
};

const categoryLabels: Record<QuestCategory, string> = {
    research: 'Research',
    tooling: 'Tooling',
    docs: 'Docs',
    experiment: 'Experiment',
};

function shownPerson(
    status: QuestStatus,
    owner: QuestPerson,
    claimant?: QuestPerson,
): { label: string; person: QuestPerson } {
    if (status === 'open' || !claimant) {
        return { label: 'Posted by', person: owner };
    }

    return {
        label: status === 'done' ? 'Completed by' : 'Claimed by',
        person: claimant,
    };
}

export function QuestCard({
    title,
    description,
    category,
    difficulty,
    effort,
    xp,
    status,
    owner,
    claimant,
    dateLabel,
    action,
    href,
    className,
}: QuestCardProps): React.JSX.Element {
    const done = status === 'done';
    const { label, person } = shownPerson(status, owner, claimant);

    return (
        <GameCard
            variant={done ? 'completed' : 'interactive'}
            accent={done ? undefined : `var(--color-${category})`}
            className={cn('grid grid-rows-[1fr_auto_auto] gap-4', className)}
        >
            <div className="flex items-start gap-4">
                <QuestEmblem category={category} />
                <div className="min-w-0">
                    <span
                        className={cn(
                            'mb-1 inline-block text-[11px] font-bold tracking-[0.12em] uppercase',
                            done ? 'text-experiment' : 'text-(--c)',
                        )}
                    >
                        {categoryLabels[category]}
                    </span>
                    <h3 className="quest-card-title text-[25px] leading-[1.15] font-bold text-balance [overflow-wrap:anywhere]">
                        {href ? (
                            <Link
                                href={href}
                                className="outline-none after:absolute after:inset-0 after:rounded-[14px] focus-visible:after:ring-2 focus-visible:after:ring-ring"
                            >
                                {title}
                            </Link>
                        ) : (
                            title
                        )}
                    </h3>
                    <p className="mt-1.5 line-clamp-3 text-[14.5px] [overflow-wrap:anywhere] text-muted-foreground">
                        {description}
                    </p>
                </div>
            </div>

            <div className="grid grid-cols-3 border-y border-line py-3 text-sm font-semibold tabular-nums [&>*]:flex [&>*]:items-center [&>*]:justify-center [&>*+*]:border-l [&>*+*]:border-line">
                <div>
                    <DifficultyBadge difficulty={difficulty} />
                </div>
                <div className="gap-1.5 font-medium text-foreground">
                    <Clock
                        aria-hidden
                        className="size-4 text-muted-foreground"
                    />
                    {effort}
                </div>
                <div>
                    <XpBadge amount={xp} />
                </div>
            </div>

            <div className="flex flex-wrap items-center gap-2.5">
                <Portrait src={person.portrait} name={person.name} size="sm" />
                <span className="min-w-0 flex-1 truncate text-[13.5px] text-muted-foreground">
                    {label}{' '}
                    <b className="font-semibold text-foreground">
                        {person.name}
                    </b>
                    {dateLabel && <span className="block">{dateLabel}</span>}
                </span>
                <StatusPill status={status} className="quest-card-status" />
                {action && <div className="relative z-10 w-full">{action}</div>}
            </div>
        </GameCard>
    );
}
