export type CardSide = 'front' | 'back';

export function flipSide(side: CardSide): CardSide {
    return side === 'front' ? 'back' : 'front';
}

export function reducedMotion(): boolean {
    return (
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
}
