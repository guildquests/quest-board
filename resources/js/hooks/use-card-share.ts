import { toPng } from 'html-to-image';
import { useCallback } from 'react';
import type { RefObject } from 'react';
import { toast } from 'sonner';
import type { CardSide } from '@/lib/card-flip';

export function slugForName(name: string, fallback: string): string {
    const slug = name
        .normalize('NFKD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

    return slug || fallback;
}

/** Builds a file name such as `quest-my-quest-front.png`. */
export function cardFilename(
    prefix: string,
    slug: string,
    suffix: string,
): string {
    return `${prefix}-${slug}${suffix}.png`;
}

function cardFilter(node: HTMLElement): boolean {
    return !node.hasAttribute('data-export-hide');
}

async function waitForCardToSettle(): Promise<void> {
    await new Promise<void>((resolve) => {
        window.setTimeout(resolve, 300);
    });
}

async function fileFromDataUrl(
    dataUrl: string,
    filename: string,
): Promise<File> {
    const response = await fetch(dataUrl);
    const blob = await response.blob();

    return new File([blob], filename, { type: 'image/png' });
}

export type UseCardShareOptions = {
    cardRef: RefObject<HTMLElement | null>;
    /** Name of the card in the "not ready" error, for example "The Guild ID". */
    cardLabel: string;
    publicUrl: string;
    /** File name for the side that is showing. */
    filenameFor: (side: CardSide) => string;
    shareTitle: string;
    side: CardSide;
    /** When false, every action does nothing. */
    enabled?: boolean;
    messages: {
        downloaded: string;
        copied: string;
        copyFailed: string;
    };
};

export type CardShare = {
    canShare: boolean;
    download: () => Promise<void>;
    copyLink: () => Promise<void>;
    share: () => Promise<void>;
};

export function useCardShare({
    cardRef,
    cardLabel,
    publicUrl,
    filenameFor,
    shareTitle,
    side,
    enabled = true,
    messages,
}: UseCardShareOptions): CardShare {
    const canShare =
        typeof navigator !== 'undefined' &&
        typeof navigator.share === 'function';

    const exportCard = useCallback(async (): Promise<string> => {
        const card = cardRef.current;

        if (!card) {
            throw new Error(`${cardLabel} is not ready.`);
        }

        await waitForCardToSettle();
        await document.fonts.ready;

        return toPng(card, {
            pixelRatio: 2,
            filter: cardFilter,
        });
    }, [cardRef, cardLabel]);

    const download = async (): Promise<void> => {
        if (!enabled) {
            return;
        }

        try {
            const dataUrl = await exportCard();
            const link = document.createElement('a');
            link.download = filenameFor(side);
            link.href = dataUrl;
            link.click();
            toast.success(messages.downloaded);
        } catch {
            toast.error('The card could not be downloaded. Please try again.');
        }
    };

    const copyLink = async (): Promise<void> => {
        if (!enabled) {
            return;
        }

        try {
            await navigator.clipboard.writeText(publicUrl);
            toast.success(messages.copied);
        } catch {
            toast.error(messages.copyFailed);
        }
    };

    const share = async (): Promise<void> => {
        if (!enabled || !canShare) {
            return;
        }

        try {
            const dataUrl = await exportCard();
            const file = await fileFromDataUrl(dataUrl, filenameFor(side));

            if (
                typeof navigator.canShare === 'function' &&
                navigator.canShare({ files: [file] })
            ) {
                await navigator.share({
                    files: [file],
                    title: shareTitle,
                });
            } else {
                await navigator.share({
                    title: shareTitle,
                    url: publicUrl,
                });
            }
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError') {
                return;
            }

            toast.error('The card could not be shared. Please try again.');
        }
    };

    return { canShare, download, copyLink, share };
}
