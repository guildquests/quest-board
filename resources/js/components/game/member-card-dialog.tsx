import { router } from '@inertiajs/react';
import {
    Check,
    Copy,
    Download,
    RefreshCw,
    Share2,
    UserRound,
} from 'lucide-react';
import { useRef, useState } from 'react';
import type { ReactNode } from 'react';
import MemberSharingController from '@/actions/App/Http/Controllers/MemberSharingController';
import { MemberCard } from '@/components/game/member-card';
import type { MemberCardSide } from '@/components/game/member-card';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import {
    cardFilename,
    slugForName,
    useCardShare,
} from '@/hooks/use-card-share';
import type { MemberSummary } from '@/types/guild';

export type MemberCardDialogProps = {
    member: MemberSummary;
    publicUrl: string;
    qr: string | null;
    sharingEnabled: boolean;
    trigger: ReactNode;
    interactive?: boolean;
};

function filenameForMember(name: string, side: MemberCardSide): string {
    const suffix = side === 'back' ? '-qr' : '';

    return cardFilename('guild-id', slugForName(name, 'member'), suffix);
}

export function MemberCardDialog({
    member,
    publicUrl,
    qr,
    sharingEnabled,
    trigger,
    interactive = true,
}: MemberCardDialogProps): React.JSX.Element {
    const [open, setOpen] = useState(false);
    const [side, setSide] = useState<MemberCardSide>('front');
    const [sharingSubmitting, setSharingSubmitting] = useState(false);
    const [regenerateConfirming, setRegenerateConfirming] = useState(false);
    const cardRef = useRef<HTMLElement>(null);
    const {
        canShare,
        download: handleDownload,
        copyLink: handleCopy,
        share: handleShare,
    } = useCardShare({
        cardRef,
        cardLabel: 'The Guild ID',
        publicUrl,
        filenameFor: (currentSide) =>
            filenameForMember(member.name, currentSide),
        shareTitle: `${member.name}'s Guild ID`,
        side,
        enabled: sharingEnabled,
        messages: {
            downloaded: 'Your Guild ID was downloaded.',
            copied: 'Profile link copied.',
            copyFailed: 'The profile link could not be copied.',
        },
    });

    const handleSharingChange = (enabled: boolean): void => {
        if (!interactive || sharingSubmitting) {
            return;
        }

        setSharingSubmitting(true);
        router.patch(
            MemberSharingController.update(),
            { enabled },
            {
                preserveScroll: true,
                onFinish: () => setSharingSubmitting(false),
            },
        );
    };

    const handleRegenerate = (): void => {
        if (!interactive) {
            return;
        }

        router.post(
            MemberSharingController.regenerate(),
            {},
            {
                preserveScroll: true,
                onSuccess: () => setRegenerateConfirming(false),
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="max-h-[calc(100dvh-2rem)] gap-0 overflow-y-auto rounded-[18px] border-primary/50 bg-linear-to-b from-panel-2 to-panel p-5 sm:max-w-[520px] sm:p-6">
                <DialogHeader className="pr-8 text-left">
                    <DialogTitle className="flex items-center gap-2.5 font-title text-[25px] font-bold text-primary-soft">
                        <UserRound
                            aria-hidden
                            className="size-5 text-primary"
                        />
                        Guild ID
                    </DialogTitle>
                    <DialogDescription className="text-muted-foreground">
                        Share a collectible card and a read-only guild profile.
                    </DialogDescription>
                </DialogHeader>

                <div className="mt-5 flex justify-center">
                    <MemberCard
                        ref={cardRef}
                        member={member}
                        qr={qr}
                        side={side}
                        onSideChange={setSide}
                    />
                </div>

                <div className="mt-4 grid gap-3 border-t border-line pt-4">
                    <div className="flex items-center justify-between gap-4">
                        <div className="grid gap-0.5">
                            <Label htmlFor="share-member-profile">
                                Share my profile
                            </Label>
                            <p className="text-xs text-muted-foreground">
                                Anyone with the link can view your card.
                            </p>
                        </div>
                        <Switch
                            id="share-member-profile"
                            checked={sharingEnabled}
                            disabled={!interactive || sharingSubmitting}
                            onCheckedChange={handleSharingChange}
                        />
                    </div>

                    {!sharingEnabled && (
                        <p className="text-xs text-muted-foreground">
                            Turn on sharing to download, copy, or share your
                            card.
                        </p>
                    )}

                    <div className="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => void handleDownload()}
                            disabled={!sharingEnabled}
                        >
                            <Download aria-hidden />
                            Download PNG
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => void handleCopy()}
                            disabled={!sharingEnabled}
                        >
                            <Copy aria-hidden />
                            Copy link
                        </Button>
                        {canShare && (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => void handleShare()}
                                disabled={!sharingEnabled}
                            >
                                <Share2 aria-hidden />
                                Share
                            </Button>
                        )}
                    </div>

                    <div className="flex flex-wrap items-center gap-2 text-sm">
                        {!regenerateConfirming ? (
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() => setRegenerateConfirming(true)}
                            >
                                <RefreshCw aria-hidden />
                                Get a new link
                            </Button>
                        ) : (
                            <div className="grid w-full gap-2 rounded-xl border border-hard/40 bg-hard/10 p-3 text-sm">
                                <p className="text-muted-foreground">
                                    Your old link and QR will stop working.
                                </p>
                                <div className="flex flex-wrap gap-2">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        onClick={() =>
                                            setRegenerateConfirming(false)
                                        }
                                    >
                                        Keep current link
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="destructive"
                                        onClick={handleRegenerate}
                                        disabled={!interactive}
                                    >
                                        <Check aria-hidden />
                                        Get a new link
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                <DialogFooter className="mt-4 border-t border-line pt-4 sm:justify-start">
                    <p className="text-xs text-muted-foreground">
                        The card downloads the side that is showing.
                    </p>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
