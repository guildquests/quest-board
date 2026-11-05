import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { ThemePreset } from '@/types';

type ThemeSwatchProps = {
    preset: ThemePreset;
    /** Name of the radio group, so it works inside a form. */
    name: string;
    selected: boolean;
    onSelect: (key: string) => void;
};

/** One theme preset as a radio tile: a mini board drawn in the preset's own colours, and its name. */
export function ThemeSwatch({
    preset,
    name,
    selected,
    onSelect,
}: ThemeSwatchProps): React.JSX.Element {
    const { colors } = preset;

    return (
        <label
            className={cn(
                'relative grid cursor-pointer gap-2 rounded-xl border border-line bg-panel p-2.5 transition-[border-color,box-shadow] hover:border-primary/60 has-focus-visible:ring-[3px] has-focus-visible:ring-ring/50',
                selected &&
                    'border-primary shadow-[0_0_0_1px_var(--color-primary)] hover:border-primary',
            )}
        >
            <input
                type="radio"
                name={name}
                value={preset.key}
                checked={selected}
                onChange={() => onSelect(preset.key)}
                className="sr-only"
            />
            <span
                aria-hidden
                className="grid h-[72px] grid-cols-[1fr_auto] gap-1.5 rounded-lg border p-2"
                style={{
                    backgroundColor: colors.bg,
                    borderColor: colors.line,
                }}
            >
                <span
                    className="grid content-between rounded-md border p-1.5"
                    style={{
                        backgroundColor: colors.panel,
                        borderColor: colors.line,
                    }}
                >
                    <span
                        className="h-1.5 w-3/4 rounded-full"
                        style={{ backgroundColor: colors['primary-soft'] }}
                    />
                    <span
                        className="h-1 w-full rounded-full"
                        style={{ backgroundColor: colors['muted-text'] }}
                    />
                </span>
                <span className="grid content-between justify-items-end">
                    <span
                        className="size-3.5 rounded-full"
                        style={{ backgroundColor: colors.primary }}
                    />
                    <span
                        className="size-3.5 rounded-full"
                        style={{ backgroundColor: colors.accent }}
                    />
                </span>
            </span>
            <span className="flex items-center justify-between gap-2 text-[13.5px] font-semibold">
                {preset.label}
                {selected && (
                    <Check aria-hidden className="size-4 text-primary" />
                )}
            </span>
        </label>
    );
}
