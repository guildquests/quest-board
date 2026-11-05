import { Link } from '@inertiajs/react';
import { History } from 'lucide-react';
import { Portrait } from '@/components/game/portrait';
import { XpBadge } from '@/components/game/xp-badge';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { show } from '@/routes/quests';
import type { ReviewEvent } from '@/types/quests';

type ReviewedFeedProps = {
    events: ReviewEvent[];
    className?: string;
};

/** The latest approve and send-back events, shown below the queue. */
export function ReviewedFeed({
    events,
    className,
}: ReviewedFeedProps): React.JSX.Element | null {
    if (events.length === 0) {
        return null;
    }

    return (
        <section
            aria-labelledby="recently-reviewed"
            className={cn(
                'rounded-2xl border border-line bg-panel p-5 max-[520px]:p-4',
                className,
            )}
        >
            <h2
                id="recently-reviewed"
                className="flex items-center gap-2.5 font-title text-2xl font-bold text-primary-soft"
            >
                <History aria-hidden className="size-[18px] text-primary" />
                Recently reviewed
            </h2>
            <ul className="mt-2 divide-y divide-line">
                {events.map((event) => (
                    <li
                        key={event.id}
                        className="flex items-center gap-3 py-3.5 first:pt-3 last:pb-0"
                    >
                        {event.adventurer !== null && (
                            <Portrait
                                src={event.adventurer.portrait_url ?? undefined}
                                name={event.adventurer.name}
                                size="sm"
                            />
                        )}
                        <div className="min-w-0 flex-1 text-sm">
                            <p className="truncate">
                                <b className="font-semibold">
                                    {event.adventurer?.name ??
                                        'No longer claimed'}
                                </b>{' '}
                                ·{' '}
                                <Link
                                    href={show(event.quest.id)}
                                    className="font-semibold text-foreground hover:text-primary-soft"
                                >
                                    {event.quest.title}
                                </Link>
                            </p>
                            <p className="mt-0.5 text-[13px] text-muted-foreground">
                                {event.event === 'approved' ? (
                                    <>
                                        Approved {event.when} by{' '}
                                        {event.reviewer?.name ?? 'a reviewer'}
                                    </>
                                ) : (
                                    <>
                                        Sent back {event.when}:{' '}
                                        <span className="italic">
                                            “{event.note}”
                                        </span>
                                    </>
                                )}
                            </p>
                        </div>
                        {event.event === 'approved' &&
                        event.quest.xp !== null ? (
                            <XpBadge amount={event.quest.xp} size="sm" />
                        ) : (
                            <Badge
                                variant="outline"
                                className="shrink-0 border-status-review/40 bg-status-review/10 text-status-review"
                            >
                                Sent back
                            </Badge>
                        )}
                    </li>
                ))}
            </ul>
        </section>
    );
}
