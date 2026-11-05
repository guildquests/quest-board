import { categoryIcons } from '@/lib/quest-icons';
import { cn } from '@/lib/utils';
import type { QuestCategory } from '@/types/quests';

type QuestEmblemProps = {
    category: QuestCategory;
    size?: 'sm' | 'md' | 'lg';
    className?: string;
};

const categories: Record<QuestCategory, { label: string; color: string }> = {
    research: { label: 'Research', color: 'var(--color-research)' },
    tooling: { label: 'Tooling', color: 'var(--color-tooling)' },
    docs: { label: 'Docs', color: 'var(--color-docs)' },
    experiment: { label: 'Experiment', color: 'var(--color-experiment)' },
};

export function QuestEmblem({
    category,
    size = 'md',
    className,
}: QuestEmblemProps): React.JSX.Element {
    const { label, color } = categories[category];
    const Icon = categoryIcons[category];

    return (
        <div
            role="img"
            aria-label={label}
            style={{ '--c': color } as React.CSSProperties}
            className={cn(
                'quest-emblem grid shrink-0 place-items-center rounded-full p-[3px] shadow-[0_4px_14px_rgb(0_0_0/0.5)]',
                size === 'lg'
                    ? 'size-24'
                    : size === 'sm'
                      ? 'size-10'
                      : 'size-[72px]',
                className,
            )}
        >
            <span className="quest-emblem-core grid size-full place-items-center rounded-full text-(--c)">
                <Icon
                    aria-hidden
                    className={cn(
                        'drop-shadow-[0_0_8px_color-mix(in_srgb,var(--c)_70%,transparent)]',
                        size === 'lg'
                            ? 'size-[42px]'
                            : size === 'sm'
                              ? 'size-[19px]'
                              : 'size-8',
                    )}
                />
            </span>
        </div>
    );
}
