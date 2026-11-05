import { Compass, Footprints, Scroll, Search, Wrench } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { BadgeKey, BadgeProgress } from '@/types/quests';

/** Icon and colour per badge; labels, descriptions and targets come from the server. */
export const badgeLook: Record<BadgeKey, { icon: LucideIcon; colour: string }> =
    {
        first_quest: { icon: Footprints, colour: 'var(--color-primary)' },
        scout: { icon: Search, colour: 'var(--color-research)' },
        toolsmith: { icon: Wrench, colour: 'var(--color-tooling)' },
        quest_giver: { icon: Scroll, colour: 'var(--color-accent)' },
        explorer: { icon: Compass, colour: 'var(--color-experiment)' },
    };

const units: Record<BadgeKey, string> = {
    first_quest: 'quests',
    scout: 'research',
    toolsmith: 'tooling',
    quest_giver: 'posted',
    explorer: 'categories',
};

/** The locked text, e.g. "2 / 3 research". */
export function progressText(badge: BadgeProgress): string {
    return `${badge.current} / ${badge.target} ${units[badge.key]}`;
}
