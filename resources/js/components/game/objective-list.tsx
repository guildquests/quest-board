import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';

type ObjectiveListProps = {
    objectives: string[];
    /** Tick every box, once the quest is turned in or done. */
    ticked?: boolean;
    className?: string;
};

/** A read-only objectives checklist. */
export function ObjectiveList({
    objectives,
    ticked = false,
    className,
}: ObjectiveListProps): React.JSX.Element {
    return (
        <ul className={cn('grid gap-2', className)}>
            {objectives.map((objective, index) => (
                <li
                    key={index}
                    className="flex items-center gap-3 rounded-[11px] border border-line bg-bg/55 px-3.5 py-[11px]"
                >
                    <span
                        aria-hidden
                        className={cn(
                            'grid size-[22px] flex-none place-items-center rounded-md border-[1.5px]',
                            ticked
                                ? 'border-accent bg-accent text-[#04140e]'
                                : 'border-accent/60 text-transparent',
                        )}
                    >
                        <Check className="size-3.5 stroke-[3]" />
                    </span>
                    <span className="min-w-0 [overflow-wrap:anywhere]">
                        {objective}
                    </span>
                    <span className="sr-only">
                        {ticked ? '(done)' : '(not done yet)'}
                    </span>
                </li>
            ))}
        </ul>
    );
}
