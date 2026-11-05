import type { ReactNode } from 'react';
import { Children } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';

type QuestGridProps = {
    children?: ReactNode;
    loading?: boolean;
    /** Shown instead of the grid when there are no children. */
    empty?: ReactNode;
    className?: string;
};

const SKELETON_COUNT = 3;

export function QuestGrid({
    children,
    loading = false,
    empty,
    className,
}: QuestGridProps): React.JSX.Element {
    if (!loading && Children.count(children) === 0 && empty) {
        return <>{empty}</>;
    }

    return (
        <div
            aria-busy={loading || undefined}
            className={cn(
                'grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3',
                className,
            )}
        >
            {loading
                ? Array.from({ length: SKELETON_COUNT }, (_, i) => (
                      <Skeleton
                          key={i}
                          className="h-72 rounded-[14px] border border-line bg-panel-2"
                      />
                  ))
                : children}
        </div>
    );
}
