import type { Role } from '@/types';

// Role colors from the prototype (.role-badge, .select.lead, .select.admin).
// Labels and descriptions come from the server (App\Enums\Role), not from here.
export const roleBadgeClassName: Record<Role, string> = {
    member: 'border-accent/50 text-accent',
    lead: 'border-tooling/50 text-[#9cc8fb]',
    admin: 'border-primary/50 text-primary-soft',
};

/** Accent color (CSS value) for each role's tile in the Member dialog. */
export const roleAccent: Record<Role, string> = {
    member: 'var(--color-accent)',
    lead: 'var(--color-tooling)',
    admin: 'var(--color-primary)',
};

export const roleTextClassName: Record<Role, string> = {
    member: '',
    lead: 'text-[#9cc8fb]',
    admin: 'text-primary-soft',
};
