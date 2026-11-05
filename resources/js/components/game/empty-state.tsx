import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type EmptyStateProps = {
    title: string;
    message?: string;
    action?: ReactNode;
    showMascot?: boolean;
    className?: string;
};

export function EmptyState({
    title,
    message,
    action,
    showMascot = false,
    className,
}: EmptyStateProps): React.JSX.Element {
    const { branding } = usePage().props;

    return (
        <div
            className={cn(
                'flex flex-col items-center gap-3 rounded-[14px] border border-dashed border-line px-6 py-10 text-center',
                className,
            )}
        >
            {showMascot && (
                <img
                    src={branding.mascots.empty}
                    alt=""
                    className="h-28 w-auto object-contain"
                />
            )}
            <p className="font-title text-xl font-bold">{title}</p>
            {message && (
                <p className="max-w-md text-sm text-muted-foreground">
                    {message}
                </p>
            )}
            {action}
        </div>
    );
}
