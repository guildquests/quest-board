import type { Auth } from '@/types/auth';

/** The install's branding (App\Models\Setting), shared on every page. */
type Branding = {
    name: string;
    tagline: string;
    logo_url: string;
    banner_url: string;
    favicon_url: string | null;
    header_url: string;
    mascots: { login: string; empty: string; reward: string };
    theme_preset: string;
};

/** The signed-in user's XP and level (App\Support\Level::toArray); null for guests. */
type Progress = {
    xp: number;
    level: number;
    title: string;
    floor: number;
    next: number | null;
};

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            branding: Branding;
            repositoryUrl: string | null;
            auth: Auth;
            progress: Progress | null;
            reviewCount: number | null;
            impersonation: { name: string } | null;
            demo: { reset_time: string } | null;
            [key: string]: unknown;
        };
    }
}
