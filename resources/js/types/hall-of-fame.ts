import type { LeveledPerson, QuestPerson } from './quests';

export type FamePerson = LeveledPerson;

export type Highlight = {
    person: FamePerson;
    count: number;
};

export type NewestHighlight = {
    person: FamePerson;
    first_on: string;
};

export type RankedAdventurer = {
    rank: number;
    person: FamePerson;
    quests_done: number;
    xp: number;
};

export type RecentCompletion = {
    id: number;
    title: string;
    xp: number;
    approved_at: string;
    when: string;
    adventurer: QuestPerson;
};

export type HallOfFamePageProps = {
    month: string;
    highlights: {
        mostCompleted: Highlight | null;
        topGiver: Highlight | null;
        newest: NewestHighlight | null;
    };
    topAdventurers: RankedAdventurer[];
    recentCompletions: RecentCompletion[];
};
