import { Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { ImpersonationBanner } from '@/components/game/impersonation-banner';
import { PageHero } from '@/components/game/page-hero';
import { board } from '@/routes';

// Standalone layout for /docs/* pages: prototype header art, no app nav or sidebar.
export default function DocsLayout({
    children,
}: PropsWithChildren): React.JSX.Element {
    const { auth, impersonation } = usePage().props;

    return (
        <div className="min-h-svh bg-bg">
            {impersonation !== null && (
                <ImpersonationBanner name={auth.user.name} />
            )}
            <PageHero
                subtitle="Component library"
                aside={
                    <Link
                        href={board()}
                        className="inline-flex items-center gap-2 rounded-[10px] border border-accent/55 bg-bg/85 px-4 py-2.5 text-sm font-semibold transition-[border-color,box-shadow] hover:border-accent hover:shadow-[0_0_14px_color-mix(in_srgb,var(--color-accent)_35%,transparent)]"
                    >
                        <ArrowLeft aria-hidden className="size-4" />
                        Back to the board
                    </Link>
                }
            />

            <main className="mx-auto max-w-[1440px] px-4 pt-6 pb-12 min-[700px]:px-6">
                {children}
            </main>
        </div>
    );
}
