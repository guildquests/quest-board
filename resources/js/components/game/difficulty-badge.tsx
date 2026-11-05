import { difficultyIcons } from '@/lib/quest-icons';
import { cn } from '@/lib/utils';
import type { QuestDifficulty } from '@/types/quests';

type DifficultyBadgeProps = {
    difficulty: QuestDifficulty;
    className?: string;
};

const difficulties: Record<
    QuestDifficulty,
    { label: string; classes: string }
> = {
    easy: { label: 'Easy', classes: 'text-easy' },
    medium: { label: 'Medium', classes: 'text-medium' },
    hard: { label: 'Hard', classes: 'text-hard' },
};

export function DifficultyBadge({
    difficulty,
    className,
}: DifficultyBadgeProps): React.JSX.Element {
    const { label, classes } = difficulties[difficulty];
    const Icon = difficultyIcons[difficulty];

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 text-sm font-semibold whitespace-nowrap',
                classes,
                className,
            )}
        >
            <Icon aria-hidden className="size-4" />
            {label}
        </span>
    );
}
