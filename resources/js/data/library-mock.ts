// Sample data for the component library page (/docs/library) only.
// Never import this from real pages.
import type { ProfileHeroProps } from '@/components/game/profile-hero';
import type { QuestCardProps } from '@/components/game/quest-card';
import type { QuestShareCardQuest } from '@/components/game/quest-share-card';
import { portraitUrl } from '@/lib/assets';
import type { Member, PortraitOption, RoleOption, ThemePreset } from '@/types';
import type { MemberSummary } from '@/types/guild';
import type {
    FamePerson,
    Highlight,
    NewestHighlight,
    RankedAdventurer,
    RecentCompletion,
} from '@/types/hall-of-fame';
import type {
    BadgeProgress,
    DifficultyOption,
    LeveledPerson,
    ProfileQuestRow,
    QuestDetail,
    QuestEventItem,
    QuestStatus,
    RewardItem,
    RewardSummary,
    ReviewEvent,
    ReviewQuest,
} from '@/types/quests';

const sampleGiver = { name: 'Sample Quest Giver', portrait: portraitUrl(6) };
const sampleAdventurer = {
    name: 'Sample Adventurer',
    portrait: portraitUrl(3),
};

export const sampleQuests: (QuestCardProps & { id: string })[] = [
    {
        id: 'open',
        title: 'Sample Quest: Try a new linter',
        description: 'Check whether a faster linter fits our Astro projects.',
        category: 'research',
        difficulty: 'easy',
        effort: '~1 hr',
        xp: 10,
        status: 'open',
        owner: sampleGiver,
        dateLabel: 'Posted 25 Oct 2026',
    },
    {
        id: 'in-progress',
        title: 'Sample Quest: Write a deploy checklist',
        description:
            'Every deploy is done from memory. A checklist cuts mistakes.',
        category: 'tooling',
        difficulty: 'medium',
        effort: '~half day',
        xp: 30,
        status: 'in_progress',
        owner: sampleGiver,
        claimant: sampleAdventurer,
        dateLabel: 'Turn in by 1 Nov 2026',
    },
    {
        id: 'in-review',
        title: 'Sample Quest: Document the image pipeline',
        description: 'A short guide so anyone can add new art to the board.',
        category: 'docs',
        difficulty: 'medium',
        effort: '~half day',
        xp: 30,
        status: 'in_review',
        owner: sampleGiver,
        claimant: { name: 'Sample Member Without Portrait' },
    },
    {
        id: 'done',
        title: 'Sample Quest: Test a form handler',
        description: 'Try a form backend for static sites and report back.',
        category: 'experiment',
        difficulty: 'hard',
        effort: '1–2 days',
        xp: 60,
        status: 'done',
        owner: sampleGiver,
        claimant: sampleAdventurer,
        dateLabel: 'Completed 20 Oct 2026',
    },
];

export const sampleLongQuest: QuestCardProps = {
    title: 'Sample Quest: A deliberately long title that keeps going to check that the card wraps it without breaking the layout',
    description:
        'A deliberately long description. It explains why the quest matters, what to try, what to compare, and what to write down at the end, so the card has to clamp it after a few lines instead of growing forever. Supercalifragilisticexpialidociouswordwithoutspaces.',
    category: 'research',
    difficulty: 'easy',
    effort: '~1 hr',
    xp: 10,
    status: 'open',
    owner: { name: 'Sample Quest Giver With A Very Long Display Name' },
};

/** The five badges, shaped like App\Support\BadgeProgress::toArray(): earned, part-way, and at zero. */
export const sampleBadges: BadgeProgress[] = [
    {
        key: 'first_quest',
        label: 'First Quest',
        description: 'Finish 1 quest',
        current: 1,
        target: 1,
        earned: true,
    },
    {
        key: 'scout',
        label: 'Scout',
        description: 'Finish 3 Research quests',
        current: 3,
        target: 3,
        earned: true,
    },
    {
        key: 'toolsmith',
        label: 'Toolsmith',
        description: 'Finish 3 Tooling quests',
        current: 2,
        target: 3,
        earned: false,
    },
    {
        key: 'quest_giver',
        label: 'Quest Giver',
        description: '5 of your posted quests completed',
        current: 0,
        target: 5,
        earned: false,
    },
    {
        key: 'explorer',
        label: 'Explorer',
        description: 'Quests in 3 categories',
        current: 2,
        target: 3,
        earned: false,
    },
];

export const sampleMemberCard: MemberSummary = {
    name: 'Ariadne Stormweaver of the Northern Reach',
    portrait_url: portraitUrl(5),
    level: 3,
    level_title: 'Adventurer',
    xp: 95,
    quests_done: 7,
    badges: sampleBadges.map(({ key, label, description }) => ({
        key,
        label,
        description,
    })),
    joined_year: 2026,
};

export const sampleNewMemberCard: MemberSummary = {
    name: 'New Member',
    portrait_url: null,
    level: 1,
    level_title: 'Apprentice',
    xp: 0,
    quests_done: 0,
    badges: [],
    joined_year: 2026,
};

/** A copy of what App\Enums\Role sends; real pages get these as props. */
export const sampleRoles: RoleOption[] = [
    {
        value: 'member',
        label: 'Member',
        description: 'Take quests, post quests, earn XP.',
    },
    {
        value: 'lead',
        label: 'Lead',
        description: 'Everything members do, plus approve or send back quests.',
    },
    {
        value: 'admin',
        label: 'Admin',
        description: 'Everything leads do, plus manage members.',
    },
];

export const samplePortraits: PortraitOption[] = (
    [1, 2, 3, 4, 5, 6, 7, 8] as const
).map((n) => ({ key: `portrait-0${n}`, url: portraitUrl(n) }));

// Negative ids match no user, so a role or access change made on the library
// page fails with a 404 instead of changing a real account.
const sampleMember: Member = {
    id: -1,
    name: 'Sample Member',
    email: 'sample.member@example.com',
    role: 'member',
    portrait: 'portrait-03',
    portrait_url: portraitUrl(3),
    active: true,
    is_self: false,
    can_impersonate: true,
    level: 1,
    level_title: 'Apprentice',
    quests_done: 0,
};

/** An active member, a deactivated lead without a portrait, and the signed-in admin. */
export const sampleMembers: Member[] = [
    sampleMember,
    {
        ...sampleMember,
        id: -2,
        name: 'Sample Lead',
        email: 'sample.lead@example.com',
        role: 'lead',
        portrait: null,
        portrait_url: null,
        active: false,
    },
    {
        ...sampleMember,
        id: -3,
        name: 'Sample Admin',
        email: 'sample.admin@example.com',
        role: 'admin',
        portrait: 'portrait-01',
        portrait_url: portraitUrl(1),
        is_self: true,
    },
];

const samplePoster: LeveledPerson = {
    id: -1,
    name: 'Sample Quest Giver',
    portrait_url: portraitUrl(6),
    level: 4,
    level_title: 'Knight',
};
const sampleHero: LeveledPerson = {
    id: -2,
    name: 'Sample Adventurer',
    portrait_url: portraitUrl(3),
    level: 3,
    level_title: 'Adventurer',
};

export const sampleRewardAdventurer: RewardItem = {
    id: -101,
    kind: 'adventurer',
    quest: {
        id: -201,
        title: 'Map the clash rules',
        category: 'research',
        category_label: 'Research',
    },
    by_label: 'Approved by Sample Lead',
    xp: 30,
    from_xp: 42,
    to_xp: 72,
    level_from: 2,
    level_to: 2,
    floor: 30,
    next: 80,
    badges: [],
    level_up: null,
};

export const sampleRewardPoster: RewardItem = {
    ...sampleRewardAdventurer,
    id: -102,
    kind: 'poster',
    by_label: 'Completed by Sample Adventurer',
    xp: 5,
    from_xp: 75,
    to_xp: 80,
    level_from: 2,
    level_to: 3,
    floor: 80,
    next: 150,
};

export const sampleRewardLevelUp: RewardItem = {
    ...sampleRewardAdventurer,
    id: -103,
    xp: 60,
    from_xp: 25,
    to_xp: 85,
    level_from: 1,
    level_to: 3,
    floor: 80,
    next: 150,
    badges: [{ key: 'scout', name: 'Scout' }],
    level_up: { level: 3, title: 'Adventurer' },
};

export const sampleRewardSummary: RewardSummary = {
    count: 4,
    total_xp: 105,
    from_xp: 72,
    to_xp: 177,
    level_from: 2,
    level_up: { level: 4, title: 'Knight' },
    floor: 150,
    next: 250,
    badges: [
        { key: 'scout', name: 'Scout' },
        { key: 'explorer', name: 'Explorer' },
    ],
};

export const sampleProfileHero: ProfileHeroProps = {
    profile: {
        id: -4,
        name: 'Sample Adventurer',
        portrait_url: portraitUrl(3),
        role_label: 'Developer',
        member_since: 'Aug 2026',
    },
    level: {
        level: 3,
        title: 'Adventurer',
        floor: 80,
        next: 150,
        xp: 95,
        next_level: { level: 4, title: 'Knight' },
    },
    stats: {
        quests_done: 5,
        quests_posted: 2,
        total_xp: 95,
        badges_earned: 2,
        badges_total: 5,
    },
};

export const sampleLegendProfileHero: ProfileHeroProps = {
    profile: {
        ...sampleProfileHero.profile,
        name: 'Sample Legend',
        portrait_url: portraitUrl(6),
    },
    level: {
        level: 6,
        title: 'Legend',
        floor: 400,
        next: null,
        xp: 520,
        next_level: null,
    },
    stats: {
        quests_done: 18,
        quests_posted: 9,
        total_xp: 520,
        badges_earned: 5,
        badges_total: 5,
    },
};

export const sampleProfileQuestRows: ProfileQuestRow[] = [
    {
        id: -31,
        title: 'In progress: Try the new lint setup',
        category: 'tooling',
        category_label: 'Tooling',
        difficulty: 'easy',
        difficulty_label: 'Easy',
        status: 'in_progress',
        detail: 'Tooling · Easy · due 2 Nov',
        xp: 10,
    },
    {
        id: -32,
        title: 'In review: Document the image pipeline',
        category: 'docs',
        category_label: 'Docs',
        difficulty: 'medium',
        difficulty_label: 'Medium',
        status: 'in_review',
        detail: 'Docs · Medium · turned in yesterday',
        xp: 30,
    },
    {
        id: -33,
        title: 'Done: Compare two form handlers',
        category: 'research',
        category_label: 'Research',
        difficulty: 'medium',
        difficulty_label: 'Medium',
        status: 'done',
        detail: 'Research · Medium · 3 days ago',
        xp: 30,
    },
    {
        id: -34,
        title: 'Open: Map our WordPress plugins',
        category: 'research',
        category_label: 'Research',
        difficulty: 'hard',
        difficulty_label: 'Hard',
        status: 'open',
        detail: 'Research · Hard · posted 4 days ago',
        xp: null,
    },
];

export const sampleHallOfFameHighlights: {
    mostCompleted: Highlight;
    topGiver: Highlight;
    newest: NewestHighlight;
} = {
    mostCompleted: {
        person: sampleHero,
        count: 6,
    },
    topGiver: {
        person: samplePoster,
        count: 4,
    },
    newest: {
        person: { ...sampleHero, name: 'Sample New Adventurer' },
        first_on: '3 Oct',
    },
};

export const sampleRankedAdventurers: RankedAdventurer[] = [
    { rank: 1, person: sampleHero, quests_done: 8, xp: 260 },
    {
        rank: 2,
        person: { ...samplePoster, name: 'Sample Quest Giver Two' },
        quests_done: 6,
        xp: 190,
    },
    {
        rank: 3,
        person: { ...sampleHero, name: 'Sample Scout' },
        quests_done: 5,
        xp: 145,
    },
    {
        rank: 4,
        person: { ...sampleHero, name: 'Sample Builder' },
        quests_done: 3,
        xp: 90,
    },
    {
        rank: 5,
        person: { ...sampleHero, name: 'Sample Scribe', portrait_url: null },
        quests_done: 1,
        xp: 30,
    },
];

const sampleFamePerson: FamePerson = sampleHero;

export const sampleRecentCompletions: RecentCompletion[] = [
    {
        id: -21,
        title: 'Test the new deploy checklist',
        xp: 60,
        approved_at: '2026-10-27T09:00:00+06:30',
        when: 'Today',
        adventurer: sampleFamePerson,
    },
    {
        id: -22,
        title: 'Document the image pipeline',
        xp: 30,
        approved_at: '2026-10-26T14:30:00+06:30',
        when: 'Yesterday',
        adventurer: { ...sampleHero, name: 'Sample Member Two' },
    },
    {
        id: -23,
        title: 'Compare two form handlers',
        xp: 10,
        approved_at: '2026-10-24T11:15:00+06:30',
        when: '3 days ago',
        adventurer: { ...sampleHero, name: 'Sample Researcher' },
    },
    {
        id: -24,
        title: 'Write the release notes',
        xp: 30,
        approved_at: '2026-10-21T16:00:00+06:30',
        when: '6 days ago',
        adventurer: { ...sampleHero, name: 'Sample Scribe' },
    },
    {
        id: -25,
        title: 'Try the new linter',
        xp: 10,
        approved_at: '2026-10-20T10:00:00+06:30',
        when: '20 Oct',
        adventurer: { ...sampleHero, name: 'Sample Explorer' },
    },
];

export const sampleReviewDifficulties: DifficultyOption[] = [
    { value: 'easy', label: 'Easy', effort: '~1 hr', xp: 10 },
    { value: 'medium', label: 'Medium', effort: '~half day', xp: 30 },
    { value: 'hard', label: 'Hard', effort: '1–2 days', xp: 60 },
];

export const sampleReviewQuest: ReviewQuest = {
    id: -10,
    title: 'Sample Quest: Check the Dokploy deploy checklist',
    category: 'docs',
    category_label: 'Docs',
    difficulty: 'easy',
    objectives: ['Run one deploy', 'Write down the missing steps'],
    result_url: 'https://wiki.example.com/dokploy-checklist',
    result_display: 'wiki.example.com/dokploy-checklist',
    result_note: 'The checklist catches the env file step clearly.',
    submitted: '3 hours ago',
    adventurer: sampleHero,
    poster: samplePoster,
    own: false,
};

export const sampleReviewEvents: ReviewEvent[] = [
    {
        id: -11,
        event: 'approved',
        quest: { id: -12, title: 'Sample Quest: Test a form handler', xp: 60 },
        adventurer: sampleHero,
        reviewer: samplePoster,
        note: null,
        when: 'today',
    },
    {
        id: -13,
        event: 'sent_back',
        quest: {
            id: -14,
            title: 'Sample Quest: Clean up the shared docs',
            xp: 30,
        },
        adventurer: { ...sampleHero, name: 'Sample Member Two' },
        reviewer: samplePoster,
        note: 'Please add the final screenshot.',
        when: '2 days ago',
    },
    {
        id: -15,
        event: 'sent_back',
        quest: {
            id: -16,
            title: 'Sample Quest: Tidy the release notes',
            xp: 10,
        },
        adventurer: null,
        reviewer: samplePoster,
        note: 'The claim was released before this review.',
        when: '1 week ago',
    },
];

/** A quest page's data in each status, for the action panel examples. */
export const sampleQuestDetails: Record<QuestStatus, QuestDetail> = (() => {
    const open: QuestDetail = {
        id: -1,
        title: 'Sample Quest: Try a new linter',
        summary: 'Check whether a faster linter fits our Astro projects.',
        category: 'research',
        category_label: 'Research',
        difficulty: 'easy',
        difficulty_label: 'Easy',
        effort: '~1 hr',
        brief: 'Sample brief.',
        objectives: ['Install it', 'Run it on one project'],
        proof: 'A short note with your verdict.',
        status: 'open',
        status_label: 'Open',
        poster: samplePoster,
        adventurer: null,
        claimed_at: null,
        due_at: null,
        result_url: null,
        result_note: null,
        turned_in_at: null,
        reward_xp: 10,
        xp: null,
        approved_at: null,
    };
    const inProgress: QuestDetail = {
        ...open,
        status: 'in_progress',
        status_label: 'In progress',
        adventurer: sampleHero,
        claimed_at: 'Sat, 25 Oct',
        due_at: 'Sat, 1 Nov',
    };
    const inReview: QuestDetail = {
        ...inProgress,
        status: 'in_review',
        status_label: 'In review',
        result_url: 'https://example.com/result',
        turned_in_at: 'Sun, 26 Oct',
    };

    return {
        open,
        in_progress: inProgress,
        in_review: inReview,
        done: {
            ...inReview,
            status: 'done',
            status_label: 'Done',
            xp: 10,
            approved_at: 'Mon, 27 Oct',
        },
    };
})();

export const sampleQuestEvents: QuestEventItem[] = [
    {
        id: -1,
        user: samplePoster,
        event: 'posted',
        phrase: 'posted this quest',
        note: null,
        at: '25 Oct, 09:00',
    },
    {
        id: -2,
        user: sampleHero,
        event: 'turned_in',
        phrase: 'turned in the quest',
        note: null,
        at: '26 Oct, 14:30',
    },
    {
        id: -3,
        user: samplePoster,
        event: 'sent_back',
        phrase: 'sent the quest back',
        note: 'Please link the build, not the screenshot.',
        at: '26 Oct, 16:10',
    },
    {
        id: -4,
        user: null,
        event: 'expired',
        phrase: 'Claim expired. The quest is open again.',
        note: null,
        at: '2 Nov, 00:00',
    },
];

export const sampleThemePresets: ThemePreset[] = [
    {
        key: 'zinc-violet',
        label: 'Zinc and violet',
        colors: {
            bg: '#09090b',
            panel: '#111114',
            line: '#2a2a31',
            'muted-text': '#a1a1ad',
            accent: '#9a8cf5',
            primary: '#8b5cf6',
            'primary-soft': '#c4b5fd',
        },
    },
    {
        key: 'stone-amber',
        label: 'Stone and amber',
        colors: {
            bg: '#0c0a09',
            panel: '#171412',
            line: '#3a332d',
            'muted-text': '#b0a69b',
            accent: '#c9926a',
            primary: '#f59e0b',
            'primary-soft': '#fcd34d',
        },
    },
];

/** Quest share card samples: an Open Easy quest and a long-title Hard quest. */
export const sampleShareQuest: QuestShareCardQuest = {
    title: 'Sample Quest: Try a new linter',
    summary: 'Check whether a faster linter fits our Astro projects.',
    category: 'research',
    category_label: 'Research',
    difficulty: 'easy',
    difficulty_label: 'Easy',
    xp: 10,
    poster: samplePoster,
};

export const sampleLongShareQuest: QuestShareCardQuest = {
    title: 'Sample Quest: A deliberately long title that keeps going to check that the card wraps it without breaking the layout',
    summary:
        'A deliberately long reason. It explains why the quest matters, what to try, what to compare, and what to write down at the end, so the card has to clamp it after a few lines instead of growing forever. Supercalifragilisticexpialidociouswordwithoutspaces.',
    category: 'experiment',
    category_label: 'Experiment',
    difficulty: 'hard',
    difficulty_label: 'Hard',
    xp: 50,
    poster: {
        ...samplePoster,
        name: 'Sample Quest Giver With A Very Long Display Name',
    },
};
