import type { InertiaLinkProps } from '@inertiajs/react';
import { Link, usePage } from '@inertiajs/react';
import { KeyRound, Palette, UserRound, UsersRound } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import { edit as editBranding } from '@/routes/branding';
import { index as membersIndex } from '@/routes/members';
import { edit as editProfile } from '@/routes/profile';
import { edit as editPassword } from '@/routes/security';

type SettingsNavItem = {
    label: string;
    icon: LucideIcon;
    href: NonNullable<InertiaLinkProps['href']>;
    /** Items in the admin group are shown to admins only. */
    group: 'account' | 'admin';
};

// Future admin tools are added here as new Settings sections.
const settingsNavItems: SettingsNavItem[] = [
    {
        label: 'Profile',
        icon: UserRound,
        href: editProfile(),
        group: 'account',
    },
    {
        label: 'Password',
        icon: KeyRound,
        href: editPassword(),
        group: 'account',
    },
    {
        label: 'Members',
        icon: UsersRound,
        href: membersIndex(),
        group: 'admin',
    },
    {
        label: 'Branding',
        icon: Palette,
        href: editBranding(),
        group: 'admin',
    },
];

function SettingsLink({ item }: { item: SettingsNavItem }) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    const active = isCurrentOrParentUrl(item.href);

    return (
        <Link
            href={item.href}
            aria-current={active ? 'page' : undefined}
            className={cn(
                'flex items-center gap-2.5 rounded-[10px] px-3 py-[11px] text-[14.5px] font-semibold text-muted-foreground transition-colors hover:bg-white/[0.03] hover:text-foreground max-[900px]:flex-none max-[520px]:gap-[7px] max-[520px]:px-2.5 max-[520px]:py-[9px] max-[520px]:text-sm',
                active &&
                    'bg-accent/12 text-foreground shadow-[inset_3px_0_0_var(--color-accent)] max-[900px]:shadow-[inset_0_-2px_0_var(--color-accent)]',
            )}
        >
            <item.icon
                aria-hidden
                className={cn('size-[18px]', active && 'text-accent')}
            />
            {item.label}
        </Link>
    );
}

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { branding, auth } = usePage().props;
    const visible = settingsNavItems.filter(
        (item) => item.group !== 'admin' || auth.user.role === 'admin',
    );
    const account = visible.filter((item) => item.group === 'account');
    const admin = visible.filter((item) => item.group === 'admin');

    return (
        <div>
            <div className="mb-6">
                <h2 className="font-brand text-[clamp(26px,3vw,34px)] font-bold text-primary-soft [text-shadow:0_2px_0_var(--color-primary-shadow)]">
                    Settings
                </h2>
                <p className="mt-1 text-muted-foreground">
                    Your account, and admin tools for running {branding.name}.
                </p>
            </div>

            <div className="grid grid-cols-[220px_minmax(0,1fr)] items-start gap-6 max-[900px]:grid-cols-1">
                <nav
                    aria-label="Settings sections"
                    className="sticky top-4 grid gap-1 rounded-[14px] border border-line bg-panel p-2 max-[900px]:static max-[900px]:flex max-[900px]:flex-wrap max-[900px]:p-1.5 max-[520px]:gap-0.5"
                >
                    {account.map((item) => (
                        <SettingsLink key={item.label} item={item} />
                    ))}
                    {admin.length > 0 && (
                        <>
                            <span className="mx-3 mt-2.5 mb-0.5 text-[11px] font-bold tracking-[0.12em] text-primary uppercase max-[900px]:self-center max-[520px]:hidden">
                                Admin
                            </span>
                            {admin.map((item) => (
                                <SettingsLink key={item.label} item={item} />
                            ))}
                        </>
                    )}
                </nav>

                <div className="grid min-w-0 gap-[18px]">{children}</div>
            </div>
        </div>
    );
}
