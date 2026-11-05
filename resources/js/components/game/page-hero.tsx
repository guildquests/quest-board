import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type PageHeroProps = {
    subtitle: string;
    /** Right side of the top row (e.g. PlayerCard or a back link). */
    aside?: ReactNode;
    /** Row under the brand (e.g. AppNav). */
    children?: ReactNode;
    className?: string;
};

// Two overlays from the prototype .hero::after: dark on the left for the title, fade into the page at the bottom.
const heroOverlay =
    'linear-gradient(90deg, color-mix(in srgb, var(--color-bg) 82%, transparent) 0%, color-mix(in srgb, var(--color-bg) 35%, transparent) 38%, transparent 60%, color-mix(in srgb, var(--color-bg) 45%, transparent) 100%), linear-gradient(180deg, transparent 55%, var(--color-bg) 100%)';

/** The header art with crest, app name and subtitle; ends with the primary rule. */
export function PageHero({
    subtitle,
    aside,
    children,
    className,
}: PageHeroProps): React.JSX.Element {
    const { branding } = usePage().props;

    return (
        <header
            className={cn(
                'relative bg-bg bg-cover bg-top bg-no-repeat',
                className,
            )}
            style={{ backgroundImage: `url(${branding.header_url})` }}
        >
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0"
                style={{ background: heroOverlay }}
            />
            <div className="relative mx-auto grid max-w-[1440px] gap-[22px] px-4 pt-[22px] min-[700px]:px-6">
                <div className="flex flex-wrap items-center justify-between gap-5">
                    <div className="flex min-w-0 items-center gap-4">
                        <img
                            src={branding.logo_url}
                            alt=""
                            className="size-16 shrink-0 object-contain drop-shadow-[0_4px_14px_rgb(0_0_0/0.6)] sm:size-[92px]"
                        />
                        <div className="min-w-0">
                            <h1 className="font-brand text-[clamp(28px,4vw,44px)] leading-[1.05] font-bold tracking-[0.02em] text-balance text-primary-soft [text-shadow:0_2px_0_var(--color-primary-shadow),0_6px_22px_rgb(0_0_0/0.7)]">
                                {branding.name}
                            </h1>
                            <p className="mt-1.5 font-title text-[17px] font-semibold [text-shadow:0_2px_10px_rgb(0_0_0/0.8)] sm:text-[21px]">
                                {subtitle}
                            </p>
                        </div>
                    </div>
                    {aside}
                </div>
                {children ?? <div className="pb-2" />}
            </div>
            <div className="relative h-px bg-linear-to-r from-transparent via-primary/50 to-transparent" />
        </header>
    );
}
