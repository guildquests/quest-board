import type { BadgeKey } from './quests';

export type MemberSummary = {
    name: string;
    portrait_url: string | null;
    level: number;
    level_title: string;
    xp: number;
    quests_done: number;
    badges: {
        key: BadgeKey;
        label: string;
        description: string;
    }[];
    joined_year: number;
};
