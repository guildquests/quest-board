import { Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { ShieldOff } from 'lucide-react';
import { board } from '@/routes';

export default function MemberUnavailable(): React.JSX.Element {
    return (
        <>
            <Head title="Profile unavailable">
                <meta name="robots" content="noindex" />
            </Head>

            <section className="mx-auto max-w-[560px] py-12 text-center sm:py-20">
                <div className="mx-auto grid size-16 place-items-center rounded-full border border-primary/40 bg-panel-2 text-primary shadow-[0_0_26px_color-mix(in_srgb,var(--color-primary)_12%,transparent)]">
                    <ShieldOff aria-hidden className="size-8" />
                </div>
                <h1 className="mt-6 font-brand text-[clamp(30px,5vw,44px)] font-bold text-primary-soft [text-shadow:0_2px_0_var(--color-primary-shadow)]">
                    This profile isn&apos;t available
                </h1>
                <p className="mt-3 text-muted-foreground">
                    The link may be old, or the member has turned off sharing.
                </p>
                <Link
                    href={board()}
                    className="mt-7 inline-flex rounded-lg border border-accent/60 bg-accent-deep/45 px-4 py-2.5 text-sm font-semibold text-foreground transition-[border-color,box-shadow] hover:border-accent hover:shadow-[0_0_16px_color-mix(in_srgb,var(--color-accent)_28%,transparent)]"
                >
                    Visit the guild board
                </Link>
            </section>
        </>
    );
}
