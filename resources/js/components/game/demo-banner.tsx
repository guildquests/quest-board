import { X } from 'lucide-react';
import { useState, useSyncExternalStore } from 'react';
import { Button } from '@/components/ui/button';

type DemoBannerProps = {
    resetTime: string;
};

const dismissalKey = 'quest-board-demo-banner-dismissed';
const dismissalEvent = 'quest-board-demo-banner-change';

function subscribeToDismissal(onStoreChange: () => void): () => void {
    window.addEventListener('storage', onStoreChange);
    window.addEventListener(dismissalEvent, onStoreChange);

    return () => {
        window.removeEventListener('storage', onStoreChange);
        window.removeEventListener(dismissalEvent, onStoreChange);
    };
}

function getDismissalSnapshot(): boolean {
    try {
        return localStorage.getItem(dismissalKey) === 'true';
    } catch {
        return false;
    }
}

function getServerDismissalSnapshot(): boolean {
    return false;
}

export function DemoBanner({
    resetTime,
}: DemoBannerProps): React.JSX.Element | null {
    const storedDismissal = useSyncExternalStore(
        subscribeToDismissal,
        getDismissalSnapshot,
        getServerDismissalSnapshot,
    );
    const [dismissedForVisit, setDismissedForVisit] = useState(false);

    if (storedDismissal || dismissedForVisit) {
        return null;
    }

    const dismiss = (): void => {
        setDismissedForVisit(true);

        try {
            localStorage.setItem(dismissalKey, 'true');
            window.dispatchEvent(new Event(dismissalEvent));
        } catch {
            // The banner still stays dismissed until the page is reloaded.
        }
    };

    return (
        <div className="w-full border-b border-primary/35 bg-primary/10 text-foreground">
            <div className="mx-auto flex max-w-[1440px] items-center justify-between gap-3 px-4 py-2.5 text-sm min-[700px]:px-6">
                <p>
                    Demo: all data resets every night at {resetTime} UTC. Do not
                    post anything private.
                </p>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="shrink-0"
                    aria-label="Dismiss demo notice"
                    onClick={dismiss}
                >
                    <X aria-hidden />
                </Button>
            </div>
        </div>
    );
}
