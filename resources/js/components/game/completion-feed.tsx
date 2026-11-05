import { Link } from '@inertiajs/react';
import { Portrait } from '@/components/game/portrait';
import { XpBadge } from '@/components/game/xp-badge';
import { cn } from '@/lib/utils';
import { show } from '@/routes/quests';
import type { RecentCompletion } from '@/types/hall-of-fame';
import { EmptyState } from './empty-state';

type CompletionFeedProps = {
    completions: RecentCompletion[];
    className?: string;
};

export function CompletionFeed({
    completions,
    className,
}: CompletionFeedProps): React.JSX.Element {
    if (completions.length === 0) {
        return (
            <EmptyState
                title="No quests finished yet. The first one could be yours."
                className={className}
            />
        );
    }

    return (
        <ul className={cn('divide-y divide-line', className)}>
            {completions.map((completion) => (
                <li
                    key={completion.id}
                    className="flex min-w-0 items-center gap-3 py-3.5 first:pt-0 last:pb-0"
                >
                    <Portrait
                        src={completion.adventurer.portrait_url ?? undefined}
                        name={completion.adventurer.name}
                        size="sm"
                    />
                    <div className="min-w-0 flex-1 text-sm">
                        <p className="break-words">
                            <b className="font-semibold">
                                {completion.adventurer.name}
                            </b>{' '}
                            completed{' '}
                            <Link
                                href={show(completion.id)}
                                className="font-semibold text-text underline decoration-primary/40 underline-offset-2 hover:text-primary-soft"
                            >
                                {completion.title}
                            </Link>
                        </p>
                        <time
                            dateTime={completion.approved_at}
                            className="mt-0.5 block text-[13px] text-muted-foreground"
                        >
                            {completion.when}
                        </time>
                    </div>
                    <XpBadge
                        amount={completion.xp}
                        size="sm"
                        compact
                        className="shrink-0 rounded-full border border-primary/30 bg-primary/10 px-2 py-1"
                    />
                </li>
            ))}
        </ul>
    );
}
