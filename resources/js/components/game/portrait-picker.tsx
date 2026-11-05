import { cn } from '@/lib/utils';
import type { PortraitOption } from '@/types';

type PortraitPickerProps = {
    portraits: PortraitOption[];
    /** Key of the portrait selected at first; null selects "None" (initials). */
    defaultValue?: string | null;
    /** Form field name; "None" submits an empty value. */
    name?: string;
    /** Accessible name of the group. */
    legend?: string;
    className?: string;
};

const tileClassName =
    'grid size-[58px] cursor-pointer place-items-center overflow-hidden rounded-full border-2 border-line bg-panel text-xs font-semibold text-muted-foreground transition-[border-color,box-shadow,transform] hover:border-accent/60 peer-checked:border-primary peer-checked:shadow-[0_0_14px_rgb(232_183_74/0.45)] peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-primary';

/** Pick one of the fixed portraits, or none (the person's initials show instead). */
export function PortraitPicker({
    portraits,
    defaultValue = null,
    name = 'portrait',
    legend = 'Portrait',
    className,
}: PortraitPickerProps): React.JSX.Element {
    return (
        <fieldset className={cn('min-w-0', className)}>
            <legend className="sr-only">{legend}</legend>
            <div className="flex flex-wrap gap-2.5">
                {portraits.map((portrait, index) => (
                    <label key={portrait.key} className="relative">
                        <input
                            type="radio"
                            name={name}
                            value={portrait.key}
                            defaultChecked={defaultValue === portrait.key}
                            className="peer sr-only"
                        />
                        <span className={tileClassName}>
                            <img
                                src={portrait.url}
                                alt={`Portrait ${index + 1}`}
                                className="size-full object-cover"
                            />
                        </span>
                    </label>
                ))}
                <label className="relative">
                    <input
                        type="radio"
                        name={name}
                        value=""
                        defaultChecked={defaultValue === null}
                        className="peer sr-only"
                    />
                    <span className={cn(tileClassName, 'border-dashed')}>
                        None
                    </span>
                </label>
            </div>
        </fieldset>
    );
}
