import { cn } from '@/lib/utils';

type LevelBadgeProps = {
    level: number;
    title?: string;
    className?: string;
};

export function LevelBadge({
    level,
    title,
    className,
}: LevelBadgeProps): React.JSX.Element {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full border border-primary/50 bg-primary/10 px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap text-primary-soft',
                className,
            )}
        >
            <span className="font-bold">Lv {level}</span>
            {title && <span className="text-foreground">{title}</span>}
        </span>
    );
}
