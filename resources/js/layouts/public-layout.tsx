import { usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

export default function PublicLayout({
    children,
}: PropsWithChildren): React.JSX.Element {
    const { branding } = usePage().props;

    return (
        <div className="relative min-h-svh overflow-hidden bg-bg">
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_50%_at_50%_0%,color-mix(in_srgb,var(--color-accent)_14%,transparent),transparent_72%)]"
            />

            <header className="relative border-b border-line/80 bg-panel/70">
                <div className="mx-auto flex max-w-[1120px] items-center gap-3 px-4 py-4 sm:px-6">
                    <img
                        src={branding.logo_url}
                        alt=""
                        className="size-10 object-contain drop-shadow-[0_4px_12px_rgb(0_0_0/0.45)]"
                    />
                    <p className="font-brand text-sm font-bold tracking-[0.08em] text-primary-soft">
                        {branding.name}
                    </p>
                </div>
            </header>

            <main className="relative mx-auto max-w-[1120px] px-4 py-8 sm:px-6 sm:py-12">
                {children}
            </main>
        </div>
    );
}
