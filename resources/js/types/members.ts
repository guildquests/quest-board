import type { Role } from './auth';

/** One row of Settings › Members (see MemberController::index). */
export type Member = {
    id: number;
    name: string;
    email: string;
    role: Role;
    /** Portrait key from config('quests.portraits'), or null for initials. */
    portrait: string | null;
    portrait_url: string | null;
    active: boolean;
    is_self: boolean;
    can_impersonate: boolean;
    level: number;
    level_title: string;
    quests_done: number;
};

/** Role option with the labels and guide text sent by App\Enums\Role. */
export type RoleOption = {
    value: Role;
    label: string;
    description: string;
};

export type PortraitOption = {
    key: string;
    url: string;
};

export type MemberCounts = Record<Role | 'all', number>;
