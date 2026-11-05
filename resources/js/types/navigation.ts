import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
};

/** A tab in the app header (components/game/app-nav.tsx). */
export type AppNavItem = {
    label: string;
    /** Label under the icon on phones. */
    shortLabel: string;
    icon: LucideIcon;
    href: NonNullable<InertiaLinkProps['href']>;
    visible: boolean;
    /** Optional count badge (e.g. quests waiting for review). */
    count?: number;
};
