import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type SectionHeadingProps = {
    title: string;
    subtitle?: string;
    icon?: LucideIcon;
    action?: ReactNode;
    /** Small label after the title, e.g. "Admin only". */
    badge?: ReactNode;
    /** primary = primary-soft title, as in the prototype Settings sections. */
    tone?: 'default' | 'primary';
    className?: string;
};

export function SectionHeading({
    title,
    subtitle,
    icon: Icon,
    action,
    badge,
    tone = 'default',
    className,
}: SectionHeadingProps): React.JSX.Element {
    return (
        <header
            className={cn(
                'flex flex-wrap items-end gap-x-4 gap-y-2',
                className,
            )}
        >
            <div className="min-w-0 flex-1">
                <h2
                    className={cn(
                        'flex items-center gap-2.5 font-title text-2xl leading-tight font-bold',
                        tone === 'primary' && 'text-[26px] text-primary-soft',
                    )}
                >
                    {Icon && (
                        <Icon aria-hidden className="size-5 text-primary" />
                    )}
                    {title}
                    {badge}
                </h2>
                {subtitle && (
                    <p className="mt-0.5 text-sm text-muted-foreground">
                        {subtitle}
                    </p>
                )}
            </div>
            {action}
        </header>
    );
}
