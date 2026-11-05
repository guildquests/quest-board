import { Lock } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { CSSProperties } from 'react';
import { cn } from '@/lib/utils';

type AchievementBadgeProps = {
    name: string;
    description: string;
    icon: LucideIcon;
    unlocked: boolean;
    /** CSS colour (e.g. "var(--color-research)") for the earned glow and icon. */
    colour?: string;
    /** Locked progress: the text replaces the description, with a thin bar. */
    progress?: { current: number; target: number; text: string };
    className?: string;
};

export function AchievementBadge({
    name,
    description,
    icon: Icon,
    unlocked,
    colour,
    progress,
    className,
}: AchievementBadgeProps): React.JSX.Element {
    const showProgress = !unlocked && progress !== undefined;
    const percent =
        progress && progress.target > 0
            ? Math.min(
                  100,
                  Math.round((progress.current / progress.target) * 100),
              )
            : 0;

    return (
        <div
            className={cn(
                'flex w-36 flex-col items-center gap-2 text-center',
                className,
            )}
            style={
                {
                    '--c': colour ?? 'var(--color-primary-soft)',
                } as CSSProperties
            }
        >
            <div
                className={cn(
                    'relative grid size-16 place-items-center rounded-full p-[3px]',
                    unlocked
                        ? 'quest-emblem shadow-[0_0_18px_color-mix(in_srgb,var(--c)_40%,transparent)]'
                        : progress
                          ? 'border-2 border-dashed border-line bg-panel-2'
                          : 'bg-line',
                )}
            >
                <span
                    className={cn(
                        'grid size-full place-items-center rounded-full',
                        unlocked
                            ? 'bg-panel text-[var(--c)]'
                            : 'bg-panel-2 text-muted-foreground',
                    )}
                >
                    <Icon
                        aria-hidden
                        className={cn(
                            'size-7',
                            unlocked &&
                                'drop-shadow-[0_0_6px_color-mix(in_srgb,var(--c)_60%,transparent)]',
                            !unlocked && 'opacity-60',
                        )}
                    />
                </span>
                {!unlocked && (
                    <span className="absolute -right-1 -bottom-1 grid size-6 place-items-center rounded-full border border-line bg-panel text-muted-foreground">
                        <Lock aria-hidden className="size-3.5" />
                    </span>
                )}
            </div>
            <div className="w-full">
                <p
                    className={cn(
                        'text-sm font-semibold',
                        unlocked ? 'text-foreground' : 'text-muted-foreground',
                    )}
                >
                    {name}
                    {!unlocked && <span className="sr-only"> (locked)</span>}
                </p>
                <p className="text-xs text-muted-foreground">
                    {showProgress ? progress.text : description}
                </p>
                {showProgress && (
                    <div
                        role="progressbar"
                        aria-label={`${name} progress`}
                        aria-valuemin={0}
                        aria-valuemax={progress.target}
                        aria-valuenow={Math.min(
                            progress.current,
                            progress.target,
                        )}
                        aria-valuetext={progress.text}
                        className="mx-auto mt-1 h-[5px] w-[70%] overflow-hidden rounded-full bg-white/10"
                    >
                        <span
                            className="block h-full rounded-full bg-[var(--c)]"
                            style={{ width: `${percent}%` }}
                        />
                    </div>
                )}
            </div>
        </div>
    );
}
