import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

export type ChoiceTileOption = {
    value: string;
    label: string;
    icon: LucideIcon;
    /** CSS color for the icon and the selected glow, e.g. `var(--color-research)`. */
    color: string;
    /** Second line on a tile, e.g. "~1 hr · 10 XP". Ignored by the chip variant. */
    detail?: string;
};

type ChoiceTilesProps = {
    /** Radio input name, so the choice submits with the form. */
    name: string;
    options: ChoiceTileOption[];
    value: string;
    onValueChange: (value: string) => void;
    /** `chip`: a wrapping row of small choices. `tile`: equal columns with a detail line. */
    variant?: 'chip' | 'tile';
    className?: string;
};

const spanBase =
    'cursor-pointer border border-line bg-panel transition-[border-color,box-shadow,background-color,transform] hover:border-accent/60 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-primary';

const spanSelected =
    'peer-checked:border-(--c) peer-checked:shadow-[0_0_12px_color-mix(in_srgb,var(--c)_35%,transparent)]';

/**
 * A radio group drawn as chips or tiles. Put it inside a `fieldset` with a
 * `legend`; the inputs are real radios, so the keyboard and screen readers
 * work as for any radio group.
 */
export function ChoiceTiles({
    name,
    options,
    value,
    onValueChange,
    variant = 'chip',
    className,
}: ChoiceTilesProps): React.JSX.Element {
    const tile = variant === 'tile';

    return (
        <div
            className={cn(
                tile
                    ? 'grid grid-cols-3 gap-2.5 max-[520px]:grid-cols-1'
                    : 'flex flex-wrap gap-2',
                className,
            )}
        >
            {options.map((option) => {
                const Icon = option.icon;

                return (
                    <label
                        key={option.value}
                        className="relative"
                        style={{ '--c': option.color } as React.CSSProperties}
                    >
                        <input
                            type="radio"
                            name={name}
                            value={option.value}
                            checked={value === option.value}
                            onChange={() => onValueChange(option.value)}
                            className="peer sr-only"
                        />
                        {tile ? (
                            <span
                                className={cn(
                                    spanBase,
                                    spanSelected,
                                    'grid h-full justify-items-center gap-0.5 rounded-xl px-2.5 py-3.5 text-center peer-checked:bg-[color-mix(in_srgb,var(--c)_14%,var(--color-panel))] peer-checked:shadow-[0_0_16px_color-mix(in_srgb,var(--c)_35%,transparent)] hover:-translate-y-px motion-reduce:hover:translate-y-0 max-[520px]:grid-cols-[auto_1fr_auto] max-[520px]:items-center max-[520px]:justify-items-start max-[520px]:gap-x-3 max-[520px]:text-left peer-checked:[&_b]:text-(--c)',
                                )}
                            >
                                <Icon
                                    aria-hidden
                                    className="mb-1 size-[26px] text-(--c) max-[520px]:mb-0"
                                />
                                <b className="font-title text-xl">
                                    {option.label}
                                </b>
                                {option.detail && (
                                    <small className="text-[12.5px] text-muted-foreground tabular-nums">
                                        {option.detail}
                                    </small>
                                )}
                            </span>
                        ) : (
                            <span
                                className={cn(
                                    spanBase,
                                    spanSelected,
                                    'inline-flex items-center gap-2 rounded-[10px] px-4 py-2.5 font-medium peer-checked:bg-[color-mix(in_srgb,var(--c)_18%,var(--color-panel))]',
                                )}
                            >
                                <Icon
                                    aria-hidden
                                    className="size-4 text-(--c)"
                                />
                                {option.label}
                            </span>
                        )}
                    </label>
                );
            })}
        </div>
    );
}
