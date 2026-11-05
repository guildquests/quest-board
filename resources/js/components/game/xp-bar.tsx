import { Progress } from '@/components/ui/progress';
import { cn } from '@/lib/utils';

type XpBarProps = {
    xp: number;
    levelFloor: number;
    /** XP needed for the next level; null at the max level. */
    nextLevelXp: number | null;
    className?: string;
};

export function xpProgressPercent(
    xp: number,
    levelFloor: number,
    nextLevelXp: number | null,
): number {
    if (nextLevelXp === null || nextLevelXp <= levelFloor) {
        return 100;
    }

    const percent = ((xp - levelFloor) / (nextLevelXp - levelFloor)) * 100;

    return Math.min(100, Math.max(0, percent));
}

export function XpBar({
    xp,
    levelFloor,
    nextLevelXp,
    className,
}: XpBarProps): React.JSX.Element {
    const label =
        nextLevelXp === null
            ? `${xp} XP · Max level`
            : `${xp} / ${nextLevelXp} XP`;

    return (
        <div
            className={cn(
                'flex items-center gap-2.5 text-xs text-muted-foreground tabular-nums',
                className,
            )}
        >
            <Progress
                value={xpProgressPercent(xp, levelFloor, nextLevelXp)}
                aria-label="Progress to next level"
                className="flex-1"
            />
            <span className="whitespace-nowrap">{label}</span>
        </div>
    );
}
