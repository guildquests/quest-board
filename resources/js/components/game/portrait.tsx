import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';

export type PortraitSize = 'sm' | 'md' | 'xl' | 'xxl';

type PortraitProps = {
    src?: string;
    name: string;
    size?: PortraitSize;
    className?: string;
};

const sizeClasses: Record<PortraitSize, string> = {
    sm: 'size-[34px] p-0.5',
    md: 'size-[58px] p-0.5',
    xl: 'size-24 p-[3px] shadow-[0_0_22px_rgb(243_210_127/0.4)]',
    xxl: 'size-[136px] p-1 shadow-[0_0_30px_rgb(232_183_74/0.35)] max-[640px]:size-28',
};

const initialsClasses: Record<PortraitSize, string> = {
    sm: 'text-[11px]',
    md: 'text-[13px]',
    xl: 'text-3xl',
    xxl: 'text-4xl',
};

export function Portrait({
    src,
    name,
    size = 'md',
    className,
}: PortraitProps): React.JSX.Element {
    const getInitials = useInitials();

    return (
        <div
            className={cn(
                'shrink-0 rounded-full bg-linear-[145deg] from-primary-soft to-[#8a6214]',
                sizeClasses[size],
                className,
            )}
        >
            {src ? (
                <img
                    src={src}
                    alt={name}
                    className="size-full rounded-full bg-bg object-cover"
                />
            ) : (
                <span
                    role="img"
                    aria-label={name}
                    className={cn(
                        'grid size-full place-items-center rounded-full bg-panel-2 font-bold text-primary-soft',
                        initialsClasses[size],
                    )}
                >
                    {getInitials(name)}
                </span>
            )}
        </div>
    );
}
