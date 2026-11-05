/** Mirrors App\Enums\Role; keep the two in sync. */
export type Role = 'member' | 'lead' | 'admin';

export type User = {
    id: number;
    name: string;
    email: string;
    role: Role;
    portrait_url: string | null;
    can_review: boolean;
};

export type Auth = {
    user: User;
};
