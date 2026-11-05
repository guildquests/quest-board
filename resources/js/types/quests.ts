import type { MemberSummary } from './guild';

/** Mirrors App\Enums\QuestStatus. */
export type QuestStatus = 'open' | 'in_progress' | 'in_review' | 'done';

/** Mirrors App\Enums\Category. */
export type QuestCategory = 'research' | 'tooling' | 'docs' | 'experiment';

/** Mirrors App\Enums\Difficulty. */
export type QuestDifficulty = 'easy' | 'medium' | 'hard';

/** Mirrors App\Enums\ActivityEvent. */
export type ActivityEvent =
    | 'posted'
    | 'accepted'
    | 'dropped'
    | 'turned_in'
    | 'sent_back'
    | 'approved'
    | 'expired'
    | 'released';

/** Category option sent by App\Enums\Category. */
export type CategoryOption = {
    value: QuestCategory;
    label: string;
};

/** Difficulty option sent by App\Enums\Difficulty, with config('quests.difficulties') values. */
export type DifficultyOption = {
    value: QuestDifficulty;
    label: string;
    xp: number;
    effort: string;
};

/** Status option sent by App\Enums\QuestStatus. */
export type StatusOption = {
    value: QuestStatus;
    label: string;
};

/** One card on the board (QuestBoardController). */
export type BoardQuest = {
    id: number;
    title: string;
    summary: string;
    category: QuestCategory;
    difficulty: QuestDifficulty;
    effort: string;
    /** The stored amount once approved, else the difficulty's XP. */
    xp: number;
    status: QuestStatus;
    poster: QuestPerson;
    adventurer: QuestPerson | null;
};

/** The most recently approved quest, for the banner. */
export type LatestCompletion = {
    id: number;
    title: string;
    xp: number | null;
    adventurer: QuestPerson;
};

/** Mirrors App\Enums\RewardKind. */
export type RewardKind = 'adventurer' | 'poster';

export type RewardBadge = {
    key: BadgeKey;
    name: string;
};

export type RewardLevelUp = {
    level: number;
    title: string;
};

export type RewardItem = {
    id: number;
    kind: RewardKind;
    quest: {
        id: number;
        title: string;
        category: QuestCategory;
        category_label: string;
    };
    by_label: string;
    xp: number;
    from_xp: number;
    to_xp: number;
    level_from: number;
    level_to: number;
    floor: number;
    next: number | null;
    badges: RewardBadge[];
    level_up?: RewardLevelUp | null;
};

export type RewardSummary = {
    count: number;
    total_xp: number;
    from_xp: number;
    to_xp: number;
    level_from: number;
    level_up: RewardLevelUp | null;
    floor: number;
    next: number | null;
    badges: RewardBadge[];
};

export type RewardQueue =
    | { mode: 'none'; items: []; summary: null }
    | { mode: 'single'; items: RewardItem[]; summary: null }
    | { mode: 'summary'; items: number[]; summary: RewardSummary };

/** The active board filters from the query string; null means no filter. */
export type BoardFilters = {
    status: QuestStatus | null;
    category: QuestCategory | null;
};

/** Props of the quest-board page. */
export type QuestBoardPageProps = {
    quests: BoardQuest[];
    filters: BoardFilters;
    statuses: StatusOption[];
    categories: CategoryOption[];
    latestCompletion: LatestCompletion | null;
    rewards: RewardQueue;
};

/** A person on the quest page (QuestController::show). */
export type QuestPerson = {
    id: number;
    name: string;
    portrait_url: string | null;
};

/** A person with their level, for the people panel and the review card. */
export type LeveledPerson = QuestPerson & {
    level: number;
    level_title: string;
};

/** One activity log entry; `user` is null for system events (expired, released). */
export type QuestEventItem = {
    id: number;
    user: QuestPerson | null;
    event: ActivityEvent;
    /** Log phrase from App\Enums\ActivityEvent::label(). */
    phrase: string;
    note: string | null;
    /** Already formatted in the display timezone. */
    at: string;
};

/** The quest as sent to quests/show; dates are already formatted in the display timezone. */
export type QuestDetail = {
    id: number;
    title: string;
    summary: string;
    category: QuestCategory;
    category_label: string;
    difficulty: QuestDifficulty;
    difficulty_label: string;
    effort: string;
    brief: string;
    objectives: string[];
    proof: string;
    status: QuestStatus;
    status_label: string;
    poster: LeveledPerson;
    adventurer: LeveledPerson | null;
    claimed_at: string | null;
    due_at: string | null;
    result_url: string | null;
    result_note: string | null;
    turned_in_at: string | null;
    /** XP the adventurer earns (the stored amount once approved). */
    reward_xp: number;
    /** Set once the quest is approved. */
    xp: number | null;
    approved_at: string | null;
};

/** The guest-safe quest summary from the public quest link. */
export type PublicQuest = {
    id: number;
    title: string;
    summary: string;
    category: QuestCategory;
    category_label: string;
    difficulty: QuestDifficulty;
    difficulty_label: string;
    effort: string;
    xp: number;
    brief: string;
    objectives: string[];
    proof: string;
    status: QuestStatus;
    status_label: string;
    poster: PublicQuestPerson;
    completed_by: PublicQuestPerson | null;
};

export type PublicQuestPerson = {
    name: string;
    portrait_url: string | null;
    level: number;
    level_title: string;
};

/** Props of the guest-facing public quest page. */
export type PublicQuestPageProps = {
    quest: PublicQuest;
};

/** What the viewer may do, from QuestPolicy. */
export type QuestCan = {
    update: boolean;
    delete: boolean;
    accept: boolean;
    drop: boolean;
    turnIn: boolean;
    review: boolean;
};

export type QuestShare = {
    public_url: string;
    qr: string;
};

/** Props of the quests/show page. */
export type QuestShowPageProps = {
    quest: QuestDetail;
    share: QuestShare;
    events: QuestEventItem[];
    can: QuestCan;
    /** Why Accept is disabled for an Open quest, or null. */
    acceptBlockedReason: string | null;
    claimDays: number;
};

/** The quest's current values on the edit form (QuestController::edit). */
export type QuestFormValues = {
    id: number;
    title: string;
    summary: string;
    category: QuestCategory;
    difficulty: QuestDifficulty;
    brief: string;
    objectives: string[];
    proof: string;
};

/** Props of the quests/form page; `quest` is null when posting a new quest. */
export type QuestFormPageProps = {
    quest: QuestFormValues | null;
    categories: CategoryOption[];
    difficulties: DifficultyOption[];
};

/** One turned-in quest in the review queue. */
export type ReviewQuest = {
    id: number;
    title: string;
    category: QuestCategory;
    category_label: string;
    difficulty: QuestDifficulty;
    objectives: string[];
    result_url: string | null;
    result_display: string | null;
    result_note: string | null;
    submitted: string | null;
    adventurer: LeveledPerson;
    poster: QuestPerson;
    own: boolean;
};

/** One recent approve or send-back event shown below the queue. */
export type ReviewEvent = {
    id: number;
    event: Extract<ActivityEvent, 'approved' | 'sent_back'>;
    quest: {
        id: number;
        title: string;
        xp: number | null;
    };
    adventurer: QuestPerson | null;
    reviewer: QuestPerson | null;
    note: string | null;
    when: string | null;
};

/** Badge values; mirrors App\Enums\Badge. */
export type BadgeKey =
    'first_quest' | 'scout' | 'toolsmith' | 'quest_giver' | 'explorer';

/** One row of App\Support\BadgeProgress::toArray(). */
export type BadgeProgress = {
    key: BadgeKey;
    label: string;
    description: string;
    current: number;
    target: number;
    earned: boolean;
};

/** One quest row on the My Profile page. */
export type ProfileQuestRow = {
    id: number;
    title: string;
    category: QuestCategory;
    category_label: string;
    difficulty: QuestDifficulty;
    difficulty_label: string;
    status: QuestStatus;
    detail: string;
    xp: number | null;
};

/** Props of the My Profile page. */
export type ProfilePageProps = {
    profile: {
        id: number;
        name: string;
        portrait_url: string | null;
        role_label: string;
        member_since: string;
    };
    level: {
        level: number;
        title: string;
        floor: number;
        next: number | null;
        xp: number;
        next_level: { level: number; title: string } | null;
    };
    stats: {
        quests_done: number;
        quests_posted: number;
        total_xp: number;
        badges_earned: number;
        badges_total: number;
    };
    quests: {
        active: ProfileQuestRow[];
        completed: ProfileQuestRow[];
        posted: ProfileQuestRow[];
    };
    badges: BadgeProgress[];
    card: {
        member: MemberSummary;
        public_url: string;
        qr: string | null;
        sharing_enabled: boolean;
    };
    /** XP a poster earns when their quest is completed. */
    poster_xp: number;
};

export type ReviewCounts = {
    waiting: number;
    approvedThisWeek: number;
};

/** Props of the review queue page. */
export type ReviewsPageProps = {
    quests: ReviewQuest[];
    difficulties: DifficultyOption[];
    posterXp: number;
    counts: ReviewCounts;
    recent: ReviewEvent[];
};
