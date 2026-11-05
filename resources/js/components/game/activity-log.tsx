import { Hourglass, UserX } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Portrait } from '@/components/game/portrait';
import { cn } from '@/lib/utils';
import type { ActivityEvent, QuestEventItem } from '@/types/quests';

type ActivityLogProps = {
    /** Oldest first, as the server sends them. */
    events: QuestEventItem[];
    className?: string;
};

/** Icon for events with no person behind them. */
const systemIcons: Partial<Record<ActivityEvent, LucideIcon>> = {
    expired: Hourglass,
    released: UserX,
};

/** A quest's history: who did what and when; send-back notes sit under their entry. */
export function ActivityLog({
    events,
    className,
}: ActivityLogProps): React.JSX.Element {
    return (
        <ol className={cn('grid gap-3.5', className)}>
            {events.map((entry) => {
                const SystemIcon = systemIcons[entry.event] ?? Hourglass;

                return (
                    <li
                        key={entry.id}
                        className="flex items-start gap-3 text-[14.5px] text-[#c9d8d0]"
                    >
                        {entry.user ? (
                            <Portrait
                                src={entry.user.portrait_url ?? undefined}
                                name={entry.user.name}
                                size="sm"
                            />
                        ) : (
                            <span
                                aria-hidden
                                className="grid size-[34px] flex-none place-items-center rounded-full border border-line bg-panel-2 text-muted-foreground"
                            >
                                <SystemIcon className="size-4" />
                            </span>
                        )}
                        <div className="min-w-0 pt-px">
                            <p className="[overflow-wrap:anywhere]">
                                {entry.user && (
                                    <>
                                        <b className="font-semibold text-foreground">
                                            {entry.user.name}
                                        </b>{' '}
                                    </>
                                )}
                                {entry.phrase}
                            </p>
                            {entry.note && (
                                <blockquote className="mt-1.5 border-l-2 border-primary/60 pl-3 text-[14px] [overflow-wrap:anywhere] text-muted-foreground italic">
                                    {entry.note}
                                </blockquote>
                            )}
                            <time className="block text-[12.5px] text-muted-foreground">
                                {entry.at}
                            </time>
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}
