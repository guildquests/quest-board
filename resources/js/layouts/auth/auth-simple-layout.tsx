import { usePage } from '@inertiajs/react';
import type { AuthLayoutProps } from '@/types';

// Prototype #login screen: full-screen art, centered card with crest, mascot on wide screens.
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { branding } = usePage().props;

    return (
        <div
            className="relative grid min-h-svh place-items-center overflow-hidden bg-bg bg-cover bg-center bg-no-repeat px-4 pt-20 pb-8"
            style={{ backgroundImage: `url(${branding.header_url})` }}
        >
            <div
                aria-hidden
                className="absolute inset-0 bg-[radial-gradient(60%_70%_at_50%_50%,color-mix(in_srgb,var(--color-bg)_55%,transparent),color-mix(in_srgb,var(--color-bg)_92%,transparent)_100%)]"
            />

            <div className="relative z-10 w-full max-w-[440px] rounded-[20px] border border-primary/50 bg-linear-to-b from-[color-mix(in_srgb,var(--color-panel-2)_92%,transparent)] to-[color-mix(in_srgb,var(--color-panel)_94%,transparent)] px-6 pt-[34px] pb-7 shadow-[0_0_0_4px_color-mix(in_srgb,var(--color-bg)_60%,transparent),0_0_40px_color-mix(in_srgb,var(--color-accent)_18%,transparent),0_24px_60px_rgb(0_0_0/0.6)] backdrop-blur-md sm:px-8">
                <span
                    aria-hidden
                    className="absolute top-2.5 left-2.5 size-[22px] rounded-tl-lg border-t-2 border-l-2 border-primary"
                />
                <span
                    aria-hidden
                    className="absolute right-2.5 bottom-2.5 size-[22px] rounded-br-lg border-r-2 border-b-2 border-primary"
                />

                <img
                    src={branding.logo_url}
                    alt=""
                    className="mx-auto -mt-[92px] mb-1.5 size-28 object-contain drop-shadow-[0_6px_18px_rgb(0_0_0/0.7)]"
                />
                <h1 className="text-center font-brand text-[34px] leading-tight font-bold text-primary-soft [text-shadow:0_2px_0_var(--color-primary-shadow),0_6px_22px_rgb(0_0_0/0.7)]">
                    {branding.name}
                </h1>
                {title && (
                    <p className="mt-1 text-center font-title text-[19px] font-semibold text-muted-foreground">
                        {title}
                    </p>
                )}
                {description && (
                    <p className="mt-1 text-center text-sm text-muted-foreground">
                        {description}
                    </p>
                )}

                <div className="mt-6">{children}</div>
            </div>

            <img
                src={branding.mascots.login}
                alt=""
                className="pointer-events-none absolute right-[max(2vw,16px)] bottom-0 z-10 hidden w-[clamp(180px,22vw,300px)] drop-shadow-[0_8px_24px_rgb(0_0_0/0.7)] min-[901px]:block"
            />
        </div>
    );
}
