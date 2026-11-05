import { Copy, Download, FlipHorizontal2, Share2 } from 'lucide-react';
import { useRef, useState } from 'react';
import { QuestShareCard } from '@/components/game/quest-share-card';
import type {
    QuestShareCardQuest,
    QuestShareCardSide,
} from '@/components/game/quest-share-card';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    cardFilename,
    slugForName,
    useCardShare,
} from '@/hooks/use-card-share';
import { flipSide } from '@/lib/card-flip';

export type QuestShareDialogProps = {
    quest: QuestShareCardQuest;
    qr: string;
    publicUrl: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export function QuestShareDialog({
    quest,
    qr,
    publicUrl,
    open,
    onOpenChange,
}: QuestShareDialogProps): React.JSX.Element {
    const [side, setSide] = useState<QuestShareCardSide>('front');
    const cardRef = useRef<HTMLElement>(null);
    const { canShare, download, copyLink, share } = useCardShare({
        cardRef,
        cardLabel: 'The quest card',
        publicUrl,
        filenameFor: (currentSide) =>
            cardFilename(
                'quest',
                slugForName(quest.title, 'quest'),
                `-${currentSide}`,
            ),
        shareTitle: quest.title,
        side,
        messages: {
            downloaded: 'Your quest card was downloaded.',
            copied: 'Quest link copied.',
            copyFailed: 'The quest link could not be copied.',
        },
    });

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[calc(100dvh-2rem)] gap-0 overflow-y-auto rounded-[18px] border-primary/50 bg-linear-to-b from-panel-2 to-panel p-5 sm:max-w-[520px] sm:p-6">
                <DialogHeader className="pr-8 text-left">
                    <DialogTitle className="flex items-center gap-2.5 font-title text-[25px] font-bold text-primary-soft">
                        <Share2 aria-hidden className="size-5 text-primary" />
                        Share quest
                    </DialogTitle>
                    <DialogDescription className="text-muted-foreground">
                        Share a quest card. Anyone with the link can read the
                        quest, and they log in to accept it.
                    </DialogDescription>
                </DialogHeader>

                <div className="mt-5 flex justify-center">
                    <QuestShareCard
                        ref={cardRef}
                        quest={quest}
                        qr={qr}
                        side={side}
                        onSideChange={setSide}
                    />
                </div>

                <div className="mt-4 flex flex-wrap gap-2 border-t border-line pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => setSide(flipSide(side))}
                    >
                        <FlipHorizontal2 aria-hidden />
                        Flip card
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => void download()}
                    >
                        <Download aria-hidden />
                        Download PNG
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => void copyLink()}
                    >
                        <Copy aria-hidden />
                        Copy link
                    </Button>
                    {canShare && (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => void share()}
                        >
                            <Share2 aria-hidden />
                            Share
                        </Button>
                    )}
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
