import { Link, usePage } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type QuestBannerProps = {
    /** Who finished the quest. */
    name: string;
    questTitle: string;
    /** Link target for the quest title. */
    questHref: InertiaLinkProps['href'];
    xp: number;
    /** Link target for "View Hall of Fame". */
    fameHref: InertiaLinkProps['href'];
    className?: string;
};

/** The board's announcement of the latest completion. The parent hides it when nothing is done yet. */
export function QuestBanner({
    name,
    questTitle,
    questHref,
    xp,
    fameHref,
    className,
}: QuestBannerProps): React.JSX.Element {
    const { branding } = usePage().props;

    return (
        <section
            aria-label="Latest completion"
            style={{ backgroundImage: `url(${branding.banner_url})` }}
            className={cn(
                'relative flex min-h-[92px] flex-wrap items-center gap-[18px] overflow-hidden rounded-[14px] border border-primary/35 bg-bg bg-cover bg-right bg-no-repeat py-2.5 pr-[22px] pl-[140px] max-[520px]:py-3.5 max-[520px]:pr-4 max-[520px]:pl-24',
                className,
            )}
        >
            <img
                src={branding.mascots.reward}
                alt=""
                className="absolute bottom-[-34px] left-2.5 w-[124px] drop-shadow-[0_4px_10px_rgb(0_0_0/0.6)] max-[520px]:bottom-[-24px] max-[520px]:left-1 max-[520px]:w-[90px]"
            />
            <p className="min-w-0 flex-1 font-title text-[22px] font-semibold [overflow-wrap:anywhere] max-[520px]:text-lg">
                <b className="font-bold text-accent">{name}</b> completed{' '}
                <Link
                    href={questHref}
                    className="underline decoration-primary/50 underline-offset-4 hover:decoration-primary"
                >
                    ‘{questTitle}’
                </Link>{' '}
                and earned{' '}
                <span className="font-bold text-primary-soft">{xp} XP</span>.
            </p>
            <Button asChild variant="outline">
                <Link href={fameHref}>
                    View Hall of Fame
                    <ArrowRight aria-hidden />
                </Link>
            </Button>
        </section>
    );
}
