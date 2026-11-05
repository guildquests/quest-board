import { Star } from 'lucide-react';
import { cn } from '@/lib/utils';

type XpBadgeProps = {
    amount: number;
    size?: 'sm' | 'md' | 'lg';
    compact?: boolean;
    className?: string;
};

const sizeClasses = {
    sm: 'text-xs [&_svg]:size-3.5',
    md: 'text-sm [&_svg]:size-4',
    lg: 'text-2xl [&_svg]:size-6',
} as const;

/** The only way XP rewards are shown. */
export function XpBadge({
    amount,
    size = 'md',
    compact = false,
    className,
}: XpBadgeProps): React.JSX.Element {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 font-bold whitespace-nowrap text-primary-soft tabular-nums',
                sizeClasses[size],
                className,
            )}
        >
            <Star aria-hidden className="fill-primary text-primary" />+{amount}
            {!compact && ' XP'}
        </span>
    );
}
