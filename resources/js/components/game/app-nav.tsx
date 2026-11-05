import { Link, usePage } from '@inertiajs/react';
import { ScrollText, ShieldCheck, Trophy, UserRound } from 'lucide-react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import { board, hallOfFame, me, reviews } from '@/routes';
import type { AppNavItem } from '@/types';

type AppNavProps = {
    /** Count shown on the Reviews tab once it is visible. */
    reviewCount?: number;
    className?: string;
};

export function appNavItems(
    reviewCount?: number,
    canReview = false,
): AppNavItem[] {
    return [
        {
            label: 'Quest Board',
            shortLabel: 'Board',
            icon: ScrollText,
            href: board(),
            visible: true,
        },
        {
            label: 'Hall of Fame',
            shortLabel: 'Fame',
            icon: Trophy,
            href: hallOfFame(),
            visible: true,
        },
        {
            label: 'Status Window',
            shortLabel: 'Status',
            icon: UserRound,
            href: me(),
            visible: true,
        },
        {
            label: 'Reviews',
            shortLabel: 'Reviews',
            icon: ShieldCheck,
            href: reviews(),
            visible: canReview,
            count: reviewCount,
        },
    ];
}

export function AppNav({
    reviewCount,
    className,
}: AppNavProps): React.JSX.Element {
    const { isCurrentUrl } = useCurrentUrl();
    const { auth } = usePage().props;
    const items = appNavItems(reviewCount, auth.user.can_review).filter(
        (item) => item.visible,
    );

    return (
        <nav aria-label="Main" className={className}>
            <ul
                className="-mx-1 flex gap-1 max-[520px]:mx-0 max-[520px]:grid max-[520px]:grid-cols-(--cols) max-[520px]:gap-0"
                style={
                    {
                        '--cols': `repeat(${items.length}, minmax(0, 1fr))`,
                    } as React.CSSProperties
                }
            >
                {items.map((item) => {
                    const active = isCurrentUrl(item.href);

                    return (
                        <li key={item.label} className="min-w-0">
                            <Link
                                href={item.href}
                                aria-current={active ? 'page' : undefined}
                                className={cn(
                                    'group inline-flex items-center gap-2.5 border-b-2 border-transparent px-[22px] py-3.5 font-title text-xl font-bold whitespace-nowrap text-muted-foreground transition-[color,border-color,text-shadow] hover:text-foreground min-[521px]:max-[700px]:px-3.5 min-[521px]:max-[700px]:py-3 min-[521px]:max-[700px]:text-[17px]',
                                    'max-[520px]:flex max-[520px]:w-full max-[520px]:flex-col max-[520px]:gap-1 max-[520px]:px-1 max-[520px]:py-2.5 max-[520px]:font-sans max-[520px]:text-[13.5px] max-[520px]:font-semibold',
                                    active &&
                                        'border-accent text-foreground [text-shadow:0_0_14px_color-mix(in_srgb,var(--color-accent)_60%,transparent)]',
                                )}
                            >
                                <span className="relative">
                                    <item.icon
                                        aria-hidden
                                        className={cn(
                                            'size-[18px] max-[520px]:size-5',
                                            active && 'text-accent',
                                        )}
                                    />
                                    {item.count !== undefined &&
                                        item.count > 0 && (
                                            <span className="absolute -top-2 -right-3 min-w-5 rounded-full bg-primary px-1.5 text-center font-sans text-xs leading-[18px] font-bold text-on-primary shadow-[0_0_10px_rgb(232_183_74/0.6)]">
                                                {item.count}
                                            </span>
                                        )}
                                </span>
                                <span className="max-[520px]:hidden">
                                    {item.label}
                                </span>
                                <span className="hidden max-[520px]:inline">
                                    {item.shortLabel}
                                </span>
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
