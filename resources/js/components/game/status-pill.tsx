import { statusIcons } from '@/lib/quest-icons';
import { cn } from '@/lib/utils';
import type { QuestStatus } from '@/types/quests';

type StatusPillProps = {
    status: QuestStatus;
    className?: string;
};

const statuses: Record<QuestStatus, { label: string; classes: string }> = {
    open: {
        label: 'Open',
        classes: 'border-status-open-line bg-accent/8 text-status-open',
    },
    in_progress: {
        label: 'In progress',
        classes: 'border-accent bg-accent/14 text-accent',
    },
    in_review: {
        label: 'In review',
        classes: 'border-status-review bg-status-review/12 text-status-review',
    },
    done: {
        label: 'Done',
        classes:
            'border-primary bg-linear-to-b from-primary-soft to-primary text-on-primary',
    },
};

export function StatusPill({
    status,
    className,
}: StatusPillProps): React.JSX.Element {
    const { label, classes } = statuses[status];
    const Icon = statusIcons[status];

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-[13px] font-semibold whitespace-nowrap',
                classes,
                className,
            )}
        >
            <Icon aria-hidden className="size-[15px]" />
            {label}
        </span>
    );
}
