import { cva } from 'class-variance-authority';
import type { VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

// Hover, glow and shine effects live in app.css (.game-card*), as in the prototype .quest card.
const gameCardVariants = cva(
    'game-card relative rounded-[14px] border-[1.5px] p-5',
    {
        variants: {
            variant: {
                default: 'border-line',
                interactive: 'game-card-interactive',
                completed: 'game-card-completed',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    },
);

export type GameCardProps = React.ComponentProps<'div'> &
    VariantProps<typeof gameCardVariants> & {
        /** Accent color for border and glow (CSS color); defaults to accent. */
        accent?: string;
    };

export function GameCard({
    className,
    variant,
    accent,
    style,
    ...props
}: GameCardProps): React.JSX.Element {
    return (
        <div
            data-slot="game-card"
            className={cn(gameCardVariants({ variant }), className)}
            style={
                accent
                    ? ({ '--c': accent, ...style } as React.CSSProperties)
                    : style
            }
            {...props}
        />
    );
}

export { gameCardVariants };
