import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { GameCard } from '@/components/game/game-card';
import { Portrait } from '@/components/game/portrait';
import { cn } from '@/lib/utils';
import type { FamePerson } from '@/types/hall-of-fame';

type HighlightAccent = 'primary' | 'accent' | 'experiment';

type HighlightCardProps = {
    icon: LucideIcon;
    label: string;
    accent: HighlightAccent;
    person: FamePerson | null;
    children: ReactNode;
    className?: string;
};

const accentClasses: Record<
    HighlightAccent,
    { border: string; glow: string; icon: string; wash: string }
> = {
    primary: {
        border: 'border-primary/45',
        glow: 'shadow-[0_0_28px_rgb(243_210_127/0.14)]',
        icon: 'text-primary-soft',
        wash: 'from-primary/10',
    },
    accent: {
        border: 'border-accent/45',
        glow: 'shadow-[0_0_28px_rgb(31_190_140/0.12)]',
        icon: 'text-accent',
        wash: 'from-accent/10',
    },
    experiment: {
        border: 'border-experiment/45',
        glow: 'shadow-[0_0_28px_rgb(108_210_221/0.12)]',
        icon: 'text-experiment',
        wash: 'from-experiment/10',
    },
};

export function HighlightCard({
    icon: Icon,
    label,
    accent,
    person,
    children,
    className,
}: HighlightCardProps): React.JSX.Element {
    const colors = accentClasses[accent];

    return (
        <GameCard
            className={cn(
                'flex min-h-[258px] flex-col items-center overflow-hidden bg-linear-to-br via-panel to-panel-2 text-center motion-safe:transition motion-safe:hover:-translate-y-0.5',
                colors.border,
                colors.glow,
                colors.wash,
                className,
            )}
        >
            <div
                className={cn(
                    'flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] uppercase',
                    colors.icon,
                )}
            >
                <Icon aria-hidden className="size-4" />
                {label}
            </div>

            {person ? (
                <>
                    <Portrait
                        src={person.portrait_url ?? undefined}
                        name={person.name}
                        size="xl"
                        className="mt-5"
                    />
                    <h3 className="mt-3 font-title text-2xl leading-tight font-bold text-text">
                        {person.name}
                    </h3>
                    <p className="mt-0.5 text-sm text-muted-foreground">
                        Level {person.level} · {person.level_title}
                    </p>
                    <p
                        className={cn(
                            'mt-3 text-sm font-semibold',
                            colors.icon,
                        )}
                    >
                        {children}
                    </p>
                </>
            ) : (
                <p className="mt-auto mb-auto max-w-[220px] pt-8 font-title text-xl leading-tight font-semibold text-muted-foreground">
                    Nobody yet this month. It could be you!
                </p>
            )}
        </GameCard>
    );
}
