const UI_BASE = '/assets/ui';
const BRAND_BASE = '/assets/brand';

// Editable branding images come from the shared `branding` prop. This helper only keeps fixed UI art.
export const assets = {
    mascots: {
        notFound: `${BRAND_BASE}/mascot-404.webp`,
    },
} as const;

export type PortraitNumber = 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8;

export function portraitUrl(n: PortraitNumber): string {
    return `${UI_BASE}/portraits/portrait-${String(n).padStart(2, '0')}.webp`;
}
