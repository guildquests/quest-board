import { router } from '@inertiajs/react';
import { UserRound } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { destroy } from '@/routes/impersonation';

type ImpersonationBannerProps = {
    name: string;
    onStop?: () => void;
};

export function ImpersonationBanner({
    name,
    onStop,
}: ImpersonationBannerProps): React.JSX.Element {
    return (
        <div className="w-full border-b border-primary/45 bg-primary text-on-primary">
            <div className="mx-auto flex max-w-[1440px] flex-wrap items-center justify-between gap-3 px-4 py-2.5 text-sm font-semibold min-[700px]:px-6">
                <p className="flex min-w-0 items-center gap-2">
                    <UserRound aria-hidden className="size-4 shrink-0" />
                    <span>
                        You're acting as {name}. Anything you do counts as them.
                    </span>
                </p>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={onStop ?? (() => router.delete(destroy()))}
                >
                    Back to my account
                </Button>
            </div>
        </div>
    );
}
