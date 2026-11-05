import type { LucideIcon } from 'lucide-react';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { cn } from '@/lib/utils';

export type FilterChipOption = {
    value: string;
    label: string;
    icon?: LucideIcon;
    /** CSS color for the icon and the selected glow, e.g. `var(--color-research)`. */
    color?: string;
};

type FilterChipsProps = {
    /** Names the group for screen readers and shows beside the chips. */
    label: string;
    icon?: LucideIcon;
    options: FilterChipOption[];
    /** The selected option's value, or null when nothing is selected. */
    value: string | null;
    onValueChange: (value: string | null) => void;
    /** Adds a first chip with this label that clears the selection. */
    allLabel?: string;
    /** Clicking the selected chip clears it (otherwise it stays selected). */
    clearable?: boolean;
    className?: string;
};

// Stands in for "no filter" so the All chip can be the selected item.
const ALL_VALUE = '__all__';

const chipClassName =
    'h-auto min-w-0 gap-[7px] rounded-[10px] border border-line bg-panel px-3.5 py-2 text-sm font-medium text-foreground first:rounded-[10px] last:rounded-[10px] hover:border-accent/60 hover:bg-panel hover:text-foreground focus-visible:ring-0 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition-[border-color,box-shadow,background-color]';

const accentSelected =
    'data-[state=on]:border-accent data-[state=on]:bg-accent-deep data-[state=on]:text-foreground data-[state=on]:shadow-[0_0_12px_color-mix(in_srgb,var(--color-accent)_35%,transparent)]';

const colorSelected =
    'data-[state=on]:border-(--c) data-[state=on]:bg-[color-mix(in_srgb,var(--c)_18%,var(--color-panel))] data-[state=on]:text-foreground data-[state=on]:shadow-[0_0_12px_color-mix(in_srgb,var(--c)_35%,transparent)]';

/**
 * A labelled group of toggle chips, built on the shadcn toggle group. The
 * parent decides what a change does, e.g. visit the board with a new query
 * string.
 */
export function FilterChips({
    label,
    icon: GroupIcon,
    options,
    value,
    onValueChange,
    allLabel,
    clearable = false,
    className,
}: FilterChipsProps): React.JSX.Element {
    const selected = value ?? (allLabel ? ALL_VALUE : '');

    const handleChange = (next: string): void => {
        if (next === '') {
            // Radix reports '' when the selected chip is clicked again.
            if (clearable) {
                onValueChange(null);
            }

            return;
        }

        onValueChange(next === ALL_VALUE ? null : next);
    };

    return (
        <ToggleGroup
            type="single"
            aria-label={label}
            value={selected}
            onValueChange={handleChange}
            className={cn('flex-wrap gap-2', className)}
        >
            <span
                aria-hidden
                className="mr-1 inline-flex items-center gap-2 text-sm font-semibold text-muted-foreground"
            >
                {GroupIcon && <GroupIcon className="size-4 text-primary" />}
                {label}
            </span>

            {allLabel && (
                <ToggleGroupItem
                    value={ALL_VALUE}
                    className={cn(chipClassName, accentSelected)}
                >
                    {allLabel}
                </ToggleGroupItem>
            )}

            {options.map((option) => {
                const Icon = option.icon;

                return (
                    <ToggleGroupItem
                        key={option.value}
                        value={option.value}
                        style={
                            {
                                '--c': option.color ?? 'var(--color-accent)',
                            } as React.CSSProperties
                        }
                        className={cn(
                            chipClassName,
                            option.color ? colorSelected : accentSelected,
                        )}
                    >
                        {Icon && (
                            <Icon
                                aria-hidden
                                className={cn(
                                    'size-4',
                                    option.color && 'text-(--c)',
                                )}
                            />
                        )}
                        {option.label}
                    </ToggleGroupItem>
                );
            })}
        </ToggleGroup>
    );
}
