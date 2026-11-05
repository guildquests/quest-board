import { Head, usePage } from '@inertiajs/react';
import {
    Crown,
    Megaphone,
    Plus,
    Share2,
    SlidersHorizontal,
    Sparkles,
    Sprout,
    Swords,
    Tag,
    Trophy,
    UserRound,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { AppFooter } from '@/components/app-footer';
import { AchievementBadge } from '@/components/game/achievement-badge';
import { ActivityLog } from '@/components/game/activity-log';
import { AppNav } from '@/components/game/app-nav';
import { ChoiceTiles } from '@/components/game/choice-tiles';
import { ComingSoonPanel } from '@/components/game/coming-soon-panel';
import { CompletionFeed } from '@/components/game/completion-feed';
import { DemoBanner } from '@/components/game/demo-banner';
import { DifficultyBadge } from '@/components/game/difficulty-badge';
import { EmptyState } from '@/components/game/empty-state';
import { FilterChips } from '@/components/game/filter-chips';
import { GameCard } from '@/components/game/game-card';
import { HighlightCard } from '@/components/game/highlight-card';
import { ImpersonationBanner } from '@/components/game/impersonation-banner';
import { LevelBadge } from '@/components/game/level-badge';
import { MemberCard } from '@/components/game/member-card';
import { MemberCardDialog } from '@/components/game/member-card-dialog';
import { ObjectiveList } from '@/components/game/objective-list';
import { ObjectivesEditor } from '@/components/game/objectives-editor';
import { PlayerCard } from '@/components/game/player-card';
import { Portrait } from '@/components/game/portrait';
import { PortraitPicker } from '@/components/game/portrait-picker';
import { ProfileHero } from '@/components/game/profile-hero';
import { QuestBanner } from '@/components/game/quest-banner';
import { QuestCard } from '@/components/game/quest-card';
import { QuestEmblem } from '@/components/game/quest-emblem';
import { QuestGrid } from '@/components/game/quest-grid';
import { QuestListRow } from '@/components/game/quest-list-row';
import { QuestShareCard } from '@/components/game/quest-share-card';
import { QuestShareDialog } from '@/components/game/quest-share-dialog';
import { RankList } from '@/components/game/rank-list';
import { RewardDialog } from '@/components/game/reward-dialog';
import { SectionHeading } from '@/components/game/section-heading';
import { StatusPill } from '@/components/game/status-pill';
import { XpBadge } from '@/components/game/xp-badge';
import { XpBar } from '@/components/game/xp-bar';
import { QuestActionPanel } from '@/components/quests/quest-action-panel';
import { ReviewCard } from '@/components/quests/review-card';
import { ReviewedFeed } from '@/components/quests/reviewed-feed';
import { MemberDialog } from '@/components/settings/member-dialog';
import { MemberRow } from '@/components/settings/member-row';
import { ResetPasswordDialog } from '@/components/settings/reset-password-dialog';
import { ThemeSwatch } from '@/components/settings/theme-swatch';
import { Badge } from '@/components/ui/badge';
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    sampleBadges,
    sampleHallOfFameHighlights,
    sampleLongQuest,
    sampleLongShareQuest,
    sampleMembers,
    samplePortraits,
    sampleProfileHero,
    sampleProfileQuestRows,
    sampleQuestDetails,
    sampleQuestEvents,
    sampleQuests,
    sampleReviewDifficulties,
    sampleReviewEvents,
    sampleReviewQuest,
    sampleRankedAdventurers,
    sampleRecentCompletions,
    sampleRewardAdventurer,
    sampleRewardLevelUp,
    sampleRewardPoster,
    sampleRewardSummary,
    sampleRoles,
    sampleShareQuest,
    sampleLegendProfileHero,
    sampleMemberCard,
    sampleNewMemberCard,
    sampleThemePresets,
} from '@/data/library-mock';
import { portraitUrl } from '@/lib/assets';
import { badgeLook, progressText } from '@/lib/badges';
import { categoryIcons, difficultyIcons, statusIcons } from '@/lib/quest-icons';
import { board, hallOfFame } from '@/routes';
import type { QuestCan, QuestCategory, QuestStatus } from '@/types/quests';

type LibraryProps = {
    sample_qr: string;
};

const noQuestActions: QuestCan = {
    update: false,
    delete: false,
    accept: false,
    drop: false,
    turnIn: false,
    review: false,
};

// Sample values only; nothing on this page is real data.
const categories: QuestCategory[] = [
    'research',
    'tooling',
    'docs',
    'experiment',
];
const statuses: QuestStatus[] = ['open', 'in_progress', 'in_review', 'done'];
const buttonVariants = [
    'default',
    'outline',
    'secondary',
    'ghost',
    'destructive',
    'link',
] as const;
const colorTokens = [
    'bg',
    'panel',
    'panel-2',
    'line',
    'text',
    'muted-text',
    'accent',
    'accent-deep',
    'primary',
    'primary-soft',
    'on-primary',
    'research',
    'tooling',
    'docs',
    'experiment',
    'easy',
    'medium',
    'hard',
    'status-open',
    'status-open-line',
    'status-review',
];

function SwitchExample() {
    const [checked, setChecked] = useState(true);

    return (
        <div className="flex items-center gap-2">
            <Switch
                id="sample-switch"
                checked={checked}
                onCheckedChange={setChecked}
            />
            <Label htmlFor="sample-switch">
                Sample switch ({checked ? 'on' : 'off'})
            </Label>
        </div>
    );
}

function ChoiceTilesExample() {
    const [category, setCategory] = useState('research');
    const [difficulty, setDifficulty] = useState('easy');

    return (
        <div className="space-y-5">
            <fieldset className="min-w-0">
                <legend className="mb-2 text-[13px] font-semibold text-muted-foreground">
                    Chips
                </legend>
                <ChoiceTiles
                    name="sample-category"
                    value={category}
                    onValueChange={setCategory}
                    options={[
                        {
                            value: 'research',
                            label: 'Research',
                            icon: categoryIcons.research,
                            color: 'var(--color-research)',
                        },
                        {
                            value: 'tooling',
                            label: 'Tooling',
                            icon: categoryIcons.tooling,
                            color: 'var(--color-tooling)',
                        },
                        {
                            value: 'docs',
                            label: 'Docs',
                            icon: categoryIcons.docs,
                            color: 'var(--color-docs)',
                        },
                        {
                            value: 'experiment',
                            label: 'Experiment',
                            icon: categoryIcons.experiment,
                            color: 'var(--color-experiment)',
                        },
                    ]}
                />
            </fieldset>
            <fieldset className="min-w-0">
                <legend className="mb-2 text-[13px] font-semibold text-muted-foreground">
                    Tiles
                </legend>
                <ChoiceTiles
                    name="sample-difficulty"
                    variant="tile"
                    value={difficulty}
                    onValueChange={setDifficulty}
                    options={[
                        {
                            value: 'easy',
                            label: 'Easy',
                            icon: difficultyIcons.easy,
                            color: 'var(--color-easy)',
                            detail: '~1 hr · 10 XP',
                        },
                        {
                            value: 'medium',
                            label: 'Medium',
                            icon: difficultyIcons.medium,
                            color: 'var(--color-medium)',
                            detail: '~half day · 30 XP',
                        },
                        {
                            value: 'hard',
                            label: 'Hard',
                            icon: difficultyIcons.hard,
                            color: 'var(--color-hard)',
                            detail: '1–2 days · 60 XP',
                        },
                    ]}
                />
            </fieldset>
        </div>
    );
}

function FilterChipsExample() {
    const [status, setStatus] = useState<string | null>(null);
    const [category, setCategory] = useState<string | null>('research');

    return (
        <div className="flex flex-wrap items-center gap-x-[18px] gap-y-3">
            <FilterChips
                label="Status"
                icon={SlidersHorizontal}
                allLabel="All"
                value={status}
                onValueChange={setStatus}
                options={[
                    { value: 'open', label: 'Open', icon: statusIcons.open },
                    {
                        value: 'in_progress',
                        label: 'In progress',
                        icon: statusIcons.in_progress,
                    },
                    {
                        value: 'in_review',
                        label: 'In review',
                        icon: statusIcons.in_review,
                    },
                    { value: 'done', label: 'Done', icon: statusIcons.done },
                ]}
            />
            <FilterChips
                label="Category"
                icon={Tag}
                clearable
                value={category}
                onValueChange={setCategory}
                options={[
                    {
                        value: 'research',
                        label: 'Research',
                        icon: categoryIcons.research,
                        color: 'var(--color-research)',
                    },
                    {
                        value: 'tooling',
                        label: 'Tooling',
                        icon: categoryIcons.tooling,
                        color: 'var(--color-tooling)',
                    },
                ]}
            />
        </div>
    );
}

// The local password rule; production sends the stricter one.
const samplePasswordRules = 'minlength: 8;';

function ThemeSwatchExample() {
    const [selected, setSelected] = useState(sampleThemePresets[0].key);

    return (
        <div className="grid max-w-[420px] grid-cols-2 gap-2.5">
            {sampleThemePresets.map((preset) => (
                <ThemeSwatch
                    key={preset.key}
                    preset={preset}
                    name="library-theme"
                    selected={selected === preset.key}
                    onSelect={setSelected}
                />
            ))}
        </div>
    );
}

// Edit mode only: add mode would create a real account when an admin saves it.
function MemberDialogsExample() {
    const [editOpen, setEditOpen] = useState(false);
    const [resetOpen, setResetOpen] = useState(false);

    return (
        <div className="flex flex-wrap gap-3">
            <Button variant="outline" onClick={() => setEditOpen(true)}>
                Edit member
            </Button>
            <Button variant="outline" onClick={() => setResetOpen(true)}>
                Reset password
            </Button>

            <MemberDialog
                open={editOpen}
                onOpenChange={setEditOpen}
                member={sampleMembers[0]}
                roles={sampleRoles}
                portraits={samplePortraits}
                passwordRules={samplePasswordRules}
            />
            <ResetPasswordDialog
                open={resetOpen}
                member={sampleMembers[0]}
                onClose={() => setResetOpen(false)}
                passwordRules={samplePasswordRules}
            />
        </div>
    );
}

function RewardDialogExample(): React.JSX.Element {
    const [reward, setReward] = useState<
        | typeof sampleRewardAdventurer
        | typeof sampleRewardPoster
        | typeof sampleRewardLevelUp
        | typeof sampleRewardSummary
        | null
    >(null);

    return (
        <>
            <div className="flex flex-wrap gap-3">
                <Button
                    variant="outline"
                    onClick={() => setReward(sampleRewardAdventurer)}
                >
                    Adventurer reward
                </Button>
                <Button
                    variant="outline"
                    onClick={() => setReward(sampleRewardPoster)}
                >
                    Poster reward
                </Button>
                <Button
                    variant="outline"
                    onClick={() => setReward(sampleRewardLevelUp)}
                >
                    Level-up reward
                </Button>
                <Button
                    variant="outline"
                    onClick={() => setReward(sampleRewardSummary)}
                >
                    Four-reward summary
                </Button>
            </div>
            <RewardDialog
                key={
                    reward === null
                        ? 'none'
                        : 'quest' in reward
                          ? reward.id
                          : `summary-${reward.count}-${reward.to_xp}`
                }
                reward={reward}
                open={reward !== null}
                onClose={() => setReward(null)}
                profileHref={board()}
                boardHref={board()}
            />
        </>
    );
}

function QuestShareExamples(): React.JSX.Element {
    const { sample_qr } = usePage<LibraryProps>().props;
    const [open, setOpen] = useState(false);

    return (
        <div className="grid gap-6 xl:grid-cols-2">
            <QuestShareCard quest={sampleShareQuest} qr={sample_qr} />
            <QuestShareCard
                quest={sampleShareQuest}
                qr={sample_qr}
                side="back"
            />
            <QuestShareCard quest={sampleLongShareQuest} qr={sample_qr} />
            <QuestShareCard
                quest={sampleLongShareQuest}
                qr={sample_qr}
                side="back"
            />
            <div className="w-full max-w-[320px] rounded-xl border border-dashed border-primary/35 p-2">
                <p className="mb-2 text-xs text-muted-foreground">
                    320px phone frame
                </p>
                <QuestShareCard
                    quest={sampleLongShareQuest}
                    qr={sample_qr}
                    className="w-full"
                />
            </div>
            <div className="flex min-h-28 items-center justify-center rounded-xl border border-dashed border-primary/35 p-4 xl:col-span-2">
                <Button variant="outline" onClick={() => setOpen(true)}>
                    <Share2 aria-hidden />
                    Open Share quest dialog
                </Button>
                <QuestShareDialog
                    quest={sampleShareQuest}
                    qr={sample_qr}
                    publicUrl="/q/0123456789abcdef01234567"
                    open={open}
                    onOpenChange={setOpen}
                />
            </div>
        </div>
    );
}

function MemberCardExamples(): React.JSX.Element {
    const { sample_qr } = usePage<LibraryProps>().props;

    return (
        <div className="grid gap-6 xl:grid-cols-2">
            <MemberCard member={sampleMemberCard} qr={sample_qr} />
            <MemberCard member={sampleNewMemberCard} qr={sample_qr} />
            <MemberCard member={sampleMemberCard} qr={sample_qr} side="back" />
            <MemberCard member={sampleMemberCard} qr={null} side="back" />
            <div className="w-full max-w-[320px] rounded-xl border border-dashed border-primary/35 p-2">
                <p className="mb-2 text-xs text-muted-foreground">
                    320px phone frame
                </p>
                <MemberCard
                    member={sampleMemberCard}
                    qr={sample_qr}
                    className="w-full"
                />
            </div>
            <div className="flex min-h-28 items-center justify-center rounded-xl border border-dashed border-primary/35 p-4 xl:col-span-2">
                <MemberCardDialog
                    member={sampleMemberCard}
                    publicUrl="/guild/m/0123456789abcdef01234567"
                    qr={sample_qr}
                    sharingEnabled
                    interactive={false}
                    trigger={
                        <Button variant="outline">
                            <UserRound aria-hidden />
                            Open Guild ID dialog
                        </Button>
                    }
                />
            </div>
        </div>
    );
}

type LibrarySection = {
    id: string;
    title: string;
    purpose: string;
    importPath: string;
    example: ReactNode;
};

type LibraryGroup = {
    title: string;
    sections: LibrarySection[];
};

const groups: LibraryGroup[] = [
    {
        title: 'Foundation',
        sections: [
            {
                id: 'colors',
                title: 'Colors',
                purpose:
                    'Design tokens from the prototype; use them as Tailwind colors (bg-panel, text-primary, border-line).',
                importPath: 'resources/css/app.css (@theme)',
                example: (
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        {colorTokens.map((token) => (
                            <div
                                key={token}
                                className="flex items-center gap-2"
                            >
                                <span
                                    className="size-8 shrink-0 rounded-lg border border-white/10"
                                    style={{
                                        background: `var(--color-${token})`,
                                    }}
                                />
                                <code className="text-xs break-all">
                                    {token}
                                </code>
                            </div>
                        ))}
                    </div>
                ),
            },
            {
                id: 'fonts',
                title: 'Fonts',
                purpose:
                    'font-brand for the app name and big headings, font-title for section headings, quest titles and tabs, font-sans for everything else.',
                importPath: 'resources/css/app.css (@theme)',
                example: (
                    <div className="space-y-2">
                        <p className="font-brand text-3xl text-primary-soft">
                            font-brand · Cinzel
                        </p>
                        <p className="font-title text-2xl font-bold">
                            font-title · Cormorant Garamond
                        </p>
                        <p>font-sans · Figtree, body text at 15px.</p>
                        <p className="text-sm text-muted-foreground">
                            Secondary text at 14px.
                        </p>
                    </div>
                ),
            },
            {
                id: 'button',
                title: 'Button',
                purpose:
                    'Every clickable action. default = the main primary action; outline / ghost for secondary actions.',
                importPath: '@/components/ui/button',
                example: (
                    <div className="flex flex-wrap items-center gap-3">
                        {buttonVariants.map((variant) => (
                            <Button key={variant} variant={variant}>
                                {variant}
                            </Button>
                        ))}
                        <Button size="lg">
                            <Swords /> Large with icon
                        </Button>
                        <Button size="sm" variant="outline">
                            Small
                        </Button>
                        <Button disabled>Disabled</Button>
                    </div>
                ),
            },
            {
                id: 'shadcn',
                title: 'shadcn parts',
                purpose:
                    'Badge, Dialog, DropdownMenu, Tabs, Select, Switch and Progress, restyled with the tokens.',
                importPath: '@/components/ui/*',
                example: (
                    <div className="space-y-6">
                        <div className="flex flex-wrap gap-3">
                            <Badge>default</Badge>
                            <Badge variant="secondary">secondary</Badge>
                            <Badge variant="outline">outline</Badge>
                            <Badge variant="destructive">destructive</Badge>
                        </div>
                        <div className="flex flex-wrap gap-3">
                            <Dialog>
                                <DialogTrigger asChild>
                                    <Button variant="outline">
                                        Open dialog
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogHeader>
                                        <DialogTitle>Sample dialog</DialogTitle>
                                        <DialogDescription>
                                            A short message for the adventurer.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <DialogFooter>
                                        <Button>Got it</Button>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="outline">Open menu</Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent>
                                    <DropdownMenuLabel>
                                        Sample menu
                                    </DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem>
                                        First item
                                    </DropdownMenuItem>
                                    <DropdownMenuItem>
                                        Second item
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                        <Tabs defaultValue="first">
                            <TabsList>
                                <TabsTrigger value="first">
                                    First tab
                                </TabsTrigger>
                                <TabsTrigger value="second">
                                    Second tab
                                </TabsTrigger>
                            </TabsList>
                            <TabsContent value="first" className="text-sm">
                                Content of the first tab.
                            </TabsContent>
                            <TabsContent value="second" className="text-sm">
                                Content of the second tab.
                            </TabsContent>
                        </Tabs>
                        <div className="flex flex-wrap items-center gap-6">
                            <Select defaultValue="research">
                                <SelectTrigger
                                    className="w-48"
                                    aria-label="Sample category"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="research">
                                        Research
                                    </SelectItem>
                                    <SelectItem value="tooling">
                                        Tooling
                                    </SelectItem>
                                    <SelectItem value="docs">Docs</SelectItem>
                                    <SelectItem value="experiment">
                                        Experiment
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <SwitchExample />
                            <Progress
                                value={60}
                                aria-label="Sample progress"
                                className="w-48"
                            />
                        </div>
                    </div>
                ),
            },
        ],
    },
    {
        title: 'Game',
        sections: [
            {
                id: 'portrait',
                title: 'Portrait',
                purpose:
                    'A person on the board: round image in a primary ring, initials when there is no portrait.',
                importPath: '@/components/game/portrait',
                example: (
                    <div className="flex flex-wrap items-center gap-4">
                        <Portrait
                            src={portraitUrl(1)}
                            name="Sample Adventurer"
                            size="sm"
                        />
                        <Portrait
                            src={portraitUrl(2)}
                            name="Sample Adventurer"
                            size="md"
                        />
                        <Portrait
                            src={portraitUrl(3)}
                            name="Sample Adventurer"
                            size="xl"
                        />
                        <Portrait name="Sample Adventurer" size="sm" />
                        <Portrait name="Sample Adventurer" size="md" />
                        <Portrait name="Sample Adventurer" size="xl" />
                        <Portrait name="Sample Adventurer" size="xxl" />
                    </div>
                ),
            },
            {
                id: 'portrait-picker',
                title: 'PortraitPicker',
                purpose:
                    'Pick one of the fixed portraits, or none (initials). Radio inputs named "portrait"; used in Settings › Members.',
                importPath: '@/components/game/portrait-picker',
                example: (
                    <PortraitPicker
                        portraits={samplePortraits}
                        defaultValue="portrait-02"
                    />
                ),
            },
            {
                id: 'xp-bar',
                title: 'XpBar',
                purpose: 'Progress inside the current level, with the XP text.',
                importPath: '@/components/game/xp-bar',
                example: (
                    <div className="grid max-w-md gap-3">
                        <XpBar xp={0} levelFloor={0} nextLevelXp={30} />
                        <XpBar xp={95} levelFloor={80} nextLevelXp={150} />
                        <XpBar xp={420} levelFloor={400} nextLevelXp={null} />
                    </div>
                ),
            },
            {
                id: 'player-card',
                title: 'PlayerCard',
                purpose:
                    'The signed-in user in the header: portrait, level, XP, Settings and Log out.',
                importPath: '@/components/game/player-card',
                example: (
                    <div className="flex flex-wrap gap-4">
                        <PlayerCard
                            name="Sample Adventurer"
                            portrait={portraitUrl(1)}
                            level={3}
                            title="Adventurer"
                            xp={95}
                            levelFloor={80}
                            nextLevelXp={150}
                        />
                        <PlayerCard
                            name="New Sample Member"
                            level={1}
                            title="Apprentice"
                            xp={0}
                            levelFloor={0}
                            nextLevelXp={30}
                        />
                        <PlayerCard
                            name="Sample Legend"
                            portrait={portraitUrl(6)}
                            level={6}
                            title="Legend"
                            xp={520}
                            levelFloor={400}
                            nextLevelXp={null}
                        />
                    </div>
                ),
            },
            {
                id: 'profile-hero',
                title: 'ProfileHero',
                purpose:
                    'A profile summary with the player portrait, level, XP progress, role, membership date and four profile stats. Includes the max-level state.',
                importPath: '@/components/game/profile-hero',
                example: (
                    <div className="grid gap-5">
                        <ProfileHero
                            {...sampleProfileHero}
                            action={
                                <Button variant="outline">
                                    <UserRound aria-hidden />
                                    Guild ID
                                </Button>
                            }
                        />
                        <ProfileHero {...sampleLegendProfileHero} />
                    </div>
                ),
            },
            {
                id: 'member-card',
                title: 'MemberCard',
                purpose:
                    'A collectible Guild ID with the shared guild brand, member stats, earned badges and a QR profile link. Sharing-off cards show a placeholder and keep the preview usable.',
                importPath: '@/components/game/member-card',
                example: <MemberCardExamples />,
            },
            {
                id: 'quest-share-card',
                title: 'QuestShareCard, QuestShareDialog',
                purpose:
                    'A collectible quest card to share. The brand, emblem and title stay still while the lower part flips between the quest details and a QR code. The dialog adds Flip card, Download PNG (the side that is showing), Copy link and Share (only where the browser supports it). Shown for an Open Easy quest and a long-title Hard quest.',
                importPath:
                    '@/components/game/quest-share-card, @/components/game/quest-share-dialog',
                example: <QuestShareExamples />,
            },
            {
                id: 'quest-emblem',
                title: 'QuestEmblem',
                purpose: 'The category of a quest, as a round primary emblem.',
                importPath: '@/components/game/quest-emblem',
                example: (
                    <div className="flex flex-wrap items-center gap-4">
                        {categories.map((category) => (
                            <QuestEmblem key={category} category={category} />
                        ))}
                        <QuestEmblem category="research" size="sm" />
                        <QuestEmblem category="research" size="lg" />
                    </div>
                ),
            },
            {
                id: 'quest-list-row',
                title: 'QuestListRow',
                purpose:
                    'A compact full-row quest link for profile lists, with a category edge, mini emblem, truncated title, detail line, status and optional XP reward.',
                importPath: '@/components/game/quest-list-row',
                example: (
                    <ul className="grid min-w-0 gap-2">
                        {sampleProfileQuestRows.map((quest) => (
                            <QuestListRow key={quest.id} quest={quest} />
                        ))}
                    </ul>
                ),
            },
            {
                id: 'status-pill',
                title: 'StatusPill',
                purpose: 'Quest status, always icon and text.',
                importPath: '@/components/game/status-pill',
                example: (
                    <div className="flex flex-wrap gap-3">
                        {statuses.map((status) => (
                            <StatusPill key={status} status={status} />
                        ))}
                    </div>
                ),
            },
            {
                id: 'badges',
                title: 'DifficultyBadge, XpBadge, LevelBadge',
                purpose:
                    'Difficulty (icon and text), XP rewards (the only way XP is shown), and a level label.',
                importPath:
                    '@/components/game/difficulty-badge · @/components/game/xp-badge · @/components/game/level-badge',
                example: (
                    <div className="flex flex-wrap items-center gap-5">
                        <DifficultyBadge difficulty="easy" />
                        <DifficultyBadge difficulty="medium" />
                        <DifficultyBadge difficulty="hard" />
                        <XpBadge amount={5} size="sm" />
                        <XpBadge amount={30} />
                        <XpBadge amount={60} size="lg" />
                        <LevelBadge level={1} />
                        <LevelBadge level={3} title="Adventurer" />
                    </div>
                ),
            },
            {
                id: 'achievement-badge',
                title: 'AchievementBadge',
                purpose:
                    'A badge: earned (primary ring, glow and icon in its colour) or locked (dashed ring, lock icon, progress text and bar). Icons and colours come from @/lib/badges.',
                importPath: '@/components/game/achievement-badge',
                example: (
                    <div className="grid grid-cols-3 gap-x-2.5 gap-y-3.5 sm:grid-cols-5">
                        {sampleBadges.map((badge) => (
                            <AchievementBadge
                                key={badge.key}
                                className="w-full"
                                name={badge.label}
                                description={badge.description}
                                icon={badgeLook[badge.key].icon}
                                colour={badgeLook[badge.key].colour}
                                unlocked={badge.earned}
                                progress={{
                                    current: badge.current,
                                    target: badge.target,
                                    text: progressText(badge),
                                }}
                            />
                        ))}
                    </div>
                ),
            },
            {
                id: 'game-card',
                title: 'GameCard',
                purpose:
                    'The panel surface for cards. interactive glows on hover and focus; completed is primary with a shine on hover.',
                importPath: '@/components/game/game-card',
                example: (
                    <div className="grid gap-4 md:grid-cols-3">
                        <GameCard>default</GameCard>
                        <GameCard variant="interactive" tabIndex={0}>
                            interactive (hover or focus me)
                        </GameCard>
                        <GameCard variant="completed" tabIndex={0}>
                            completed (hover me)
                        </GameCard>
                    </div>
                ),
            },
            {
                id: 'section-heading',
                title: 'SectionHeading',
                purpose:
                    'A section title with optional subtitle, icon and action.',
                importPath: '@/components/game/section-heading',
                example: (
                    <div className="grid gap-6">
                        <SectionHeading
                            title="Hall of Fame"
                            subtitle="Sample subtitle for the section."
                            icon={Trophy}
                            action={
                                <Button size="sm" variant="outline">
                                    Sample action
                                </Button>
                            }
                        />
                        <SectionHeading
                            title="Profile"
                            subtitle='tone="primary" and a badge, as in the Settings sections.'
                            icon={UserRound}
                            tone="primary"
                            badge={
                                <Badge variant="outline" className="ml-1">
                                    Admin only
                                </Badge>
                            }
                        />
                    </div>
                ),
            },
            {
                id: 'empty-state',
                title: 'EmptyState',
                purpose: 'What to show when a list is empty.',
                importPath: '@/components/game/empty-state',
                example: (
                    <div className="grid gap-4 md:grid-cols-2">
                        <EmptyState
                            title="No quests here yet"
                            message="Sample message: post the first one."
                            action={
                                <Button>
                                    <Plus /> Post a quest
                                </Button>
                            }
                            showMascot
                        />
                        <EmptyState title="Nothing waiting for review" />
                    </div>
                ),
            },
            {
                id: 'coming-soon-panel',
                title: 'ComingSoonPanel',
                purpose:
                    'Placeholder content for a page whose feature is not built yet.',
                importPath: '@/components/game/coming-soon-panel',
                example: <ComingSoonPanel title="Sample Page" />,
            },
            {
                id: 'reward-dialog',
                title: 'RewardDialog',
                purpose:
                    'A one-time reward celebration for an adventurer, quest giver, level-up, or several queued rewards. Open each sample to see the count-up and final state.',
                importPath: '@/components/game/reward-dialog',
                example: <RewardDialogExample />,
            },
        ],
    },
    {
        title: 'Hall of Fame',
        sections: [
            {
                id: 'highlight-card',
                title: 'HighlightCard',
                purpose:
                    'A monthly Hall of Fame highlight with a themed ribbon, portrait, level, and stat line. The empty variant keeps the ribbon and invites the first completion.',
                importPath: '@/components/game/highlight-card',
                example: (
                    <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                        <HighlightCard
                            icon={Trophy}
                            label="Most quests completed"
                            accent="primary"
                            person={
                                sampleHallOfFameHighlights.mostCompleted.person
                            }
                        >
                            6 quests this month
                        </HighlightCard>
                        <HighlightCard
                            icon={Megaphone}
                            label="Top quest giver"
                            accent="accent"
                            person={sampleHallOfFameHighlights.topGiver.person}
                        >
                            4 of their quests completed
                        </HighlightCard>
                        <HighlightCard
                            icon={Sprout}
                            label="Newest adventurer"
                            accent="experiment"
                            person={sampleHallOfFameHighlights.newest.person}
                        >
                            First quest on 3 Oct
                        </HighlightCard>
                        <HighlightCard
                            icon={Trophy}
                            label="Most quests completed"
                            accent="primary"
                            person={null}
                        >
                            Nobody yet this month. It could be you!
                        </HighlightCard>
                    </div>
                ),
            },
            {
                id: 'rank-list',
                title: 'RankList',
                purpose:
                    'The top five adventurers by lifetime XP, with rank colors, level, quest count, relative meter, and an empty state.',
                importPath: '@/components/game/rank-list',
                example: (
                    <div className="grid gap-5 lg:grid-cols-2">
                        <div className="rounded-[14px] border border-line bg-panel p-5">
                            <SectionHeading
                                title="Top adventurers"
                                subtitle="all-time XP"
                                icon={Crown}
                            />
                            <RankList
                                adventurers={sampleRankedAdventurers}
                                className="mt-4"
                            />
                        </div>
                        <div className="rounded-[14px] border border-line bg-panel p-5">
                            <SectionHeading
                                title="Empty rank list"
                                subtitle="Nothing earned yet"
                                icon={Crown}
                            />
                            <RankList adventurers={[]} className="mt-4" />
                        </div>
                    </div>
                ),
            },
            {
                id: 'completion-feed',
                title: 'CompletionFeed',
                purpose:
                    'Recent quest completions with a linked title, local time label, adventurer portrait, and compact XP chip.',
                importPath: '@/components/game/completion-feed',
                example: (
                    <div className="grid gap-5 lg:grid-cols-2">
                        <div className="rounded-[14px] border border-line bg-panel p-5">
                            <SectionHeading
                                title="Recent completions"
                                icon={Sparkles}
                            />
                            <CompletionFeed
                                completions={sampleRecentCompletions}
                                className="mt-4"
                            />
                        </div>
                        <div className="rounded-[14px] border border-line bg-panel p-5">
                            <SectionHeading
                                title="Empty completion feed"
                                icon={Sparkles}
                            />
                            <CompletionFeed completions={[]} className="mt-4" />
                        </div>
                    </div>
                ),
            },
        ],
    },
    {
        title: 'Layout',
        sections: [
            {
                id: 'page-hero',
                title: 'PageHero',
                purpose:
                    'The header art with crest, app name and subtitle, ending in the primary rule. Takes an aside (right side) and children (row under the brand).',
                importPath: '@/components/game/page-hero',
                example: (
                    <p className="text-sm">
                        The header at the top of this page is a PageHero (with a
                        back link as its aside). The app layout uses it with the
                        PlayerCard and AppNav.
                    </p>
                ),
            },
            {
                id: 'app-nav',
                title: 'AppNav',
                purpose:
                    'The main tabs from one typed config; icon over short label on phones (520px and below). Reviews shows only for leads and admins.',
                importPath: '@/components/game/app-nav',
                example: <AppNav />,
            },
            {
                id: 'app-footer',
                title: 'AppFooter',
                purpose:
                    'A small muted line at the bottom of app pages and the login page: "<app name> is open source." and a link to the public repo. Reads the shared branding and repositoryUrl props; renders nothing when repositoryUrl is empty.',
                importPath: '@/components/app-footer',
                example: <AppFooter />,
            },
            {
                id: 'demo-banner',
                title: 'DemoBanner',
                purpose:
                    'A dismissible notice that tells public-demo visitors when data resets and warns them not to post private information.',
                importPath: '@/components/game/demo-banner',
                example: <DemoBanner resetTime="00:00" />,
            },
            {
                id: 'impersonation-banner',
                title: 'ImpersonationBanner',
                purpose:
                    'A full-width primary notice while an admin is acting as another user, with a button to return to the admin account.',
                importPath: '@/components/game/impersonation-banner',
                example: (
                    <ImpersonationBanner
                        name="Sample Adventurer"
                        onStop={() => {}}
                    />
                ),
            },
        ],
    },
    {
        title: 'Quest',
        sections: [
            {
                id: 'quest-card',
                title: 'QuestCard',
                purpose:
                    'A quest on the board, in every status; the parent passes any action. Includes a long-text card.',
                importPath: '@/components/game/quest-card',
                example: (
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        {sampleQuests.map(({ id, ...quest }) => (
                            <QuestCard key={id} {...quest} />
                        ))}
                        <QuestCard
                            {...sampleLongQuest}
                            action={
                                <Button className="w-full">
                                    <Swords /> Accept quest
                                </Button>
                            }
                        />
                    </div>
                ),
            },
            {
                id: 'choice-tiles',
                title: 'ChoiceTiles',
                purpose:
                    'A radio group drawn as chips (category) or tiles (difficulty); real radio inputs, so it works inside a form. Put it in a fieldset with a legend.',
                importPath: '@/components/game/choice-tiles',
                example: <ChoiceTilesExample />,
            },
            {
                id: 'objectives-editor',
                title: 'ObjectivesEditor',
                purpose:
                    'The growing list of objective inputs (objectives[]) on the quest form: Add objective focuses the new row, × removes one, at least one row stays, at most 8.',
                importPath: '@/components/game/objectives-editor',
                example: (
                    <div className="max-w-xl">
                        <ObjectivesEditor
                            defaultValues={[
                                'Install it',
                                'Write up the result',
                            ]}
                            errors={{
                                'objectives.1': 'Sample error under a row.',
                            }}
                        />
                    </div>
                ),
            },
            {
                id: 'quest-banner',
                title: 'QuestBanner',
                purpose:
                    'The board announcement of the latest completion, with a link to the quest and to the Hall of Fame. The board hides it when nothing is done yet.',
                importPath: '@/components/game/quest-banner',
                example: (
                    <QuestBanner
                        name="Sample Adventurer"
                        questTitle="Sample Quest: Test image uploads"
                        questHref={board()}
                        xp={60}
                        fameHref={hallOfFame()}
                    />
                ),
            },
            {
                id: 'filter-chips',
                title: 'FilterChips',
                purpose:
                    'A labelled group of toggle chips, built on the shadcn toggle group. Status has an "All" chip; category chips clear when clicked again. The board turns each change into a visit with the query string.',
                importPath: '@/components/game/filter-chips',
                example: <FilterChipsExample />,
            },
            {
                id: 'objective-list',
                title: 'ObjectiveList',
                purpose:
                    'A read-only objectives checklist; ticked once the quest is turned in or done.',
                importPath: '@/components/game/objective-list',
                example: (
                    <div className="grid max-w-xl gap-4">
                        <ObjectiveList
                            objectives={['Install it', 'Run it on one project']}
                        />
                        <ObjectiveList
                            ticked
                            objectives={['Install it', 'Run it on one project']}
                        />
                    </div>
                ),
            },
            {
                id: 'activity-log',
                title: 'ActivityLog',
                purpose:
                    "A quest's history, oldest first: portrait, name, phrase and time. System events show an icon, and send-back notes sit under their entry.",
                importPath: '@/components/game/activity-log',
                example: (
                    <div className="max-w-xl">
                        <ActivityLog events={sampleQuestEvents} />
                    </div>
                ),
            },
            {
                id: 'quest-action-panel',
                title: 'QuestActionPanel',
                purpose:
                    'The quest page action panel, once per status (open, in progress as the adventurer, in review, done). After accepting, Mission accepted gets a one-time stamp and glow from flash data. The buttons are disabled here so no real quest changes.',
                importPath: '@/components/quests/quest-action-panel',
                example: (
                    <fieldset disabled className="contents">
                        <div className="grid gap-5 md:grid-cols-2">
                            <QuestActionPanel
                                quest={sampleQuestDetails.open}
                                can={{
                                    ...noQuestActions,
                                    accept: true,
                                    update: true,
                                    delete: true,
                                }}
                                acceptBlockedReason={null}
                                claimDays={7}
                            />
                            <QuestActionPanel
                                quest={sampleQuestDetails.in_progress}
                                can={{
                                    ...noQuestActions,
                                    drop: true,
                                    turnIn: true,
                                }}
                                acceptBlockedReason={null}
                                claimDays={7}
                            />
                            <QuestActionPanel
                                quest={sampleQuestDetails.in_review}
                                can={{ ...noQuestActions, review: true }}
                                acceptBlockedReason={null}
                                claimDays={7}
                            />
                            <QuestActionPanel
                                quest={sampleQuestDetails.done}
                                can={noQuestActions}
                                acceptBlockedReason={null}
                                claimDays={7}
                            />
                        </div>
                    </fieldset>
                ),
            },
            {
                id: 'review-card',
                title: 'ReviewCard, ReviewedFeed',
                purpose:
                    'The review queue card and its recent-review feed. The sample card is disabled so the library cannot approve or send back a real quest.',
                importPath:
                    '@/components/quests/review-card, @/components/quests/reviewed-feed',
                example: (
                    <div className="grid gap-5">
                        <fieldset
                            disabled
                            className="contents [&_a]:pointer-events-none"
                        >
                            <ReviewCard
                                quest={sampleReviewQuest}
                                difficulties={sampleReviewDifficulties}
                                posterXp={5}
                            />
                        </fieldset>
                        <ReviewedFeed events={sampleReviewEvents} />
                    </div>
                ),
            },
            {
                id: 'quest-grid',
                title: 'QuestGrid',
                purpose:
                    'The responsive grid for quest cards, with loading and empty states.',
                importPath: '@/components/game/quest-grid',
                example: (
                    <div className="space-y-6">
                        <QuestGrid>
                            {sampleQuests
                                .slice(0, 3)
                                .map(({ id, ...quest }) => (
                                    <QuestCard key={id} {...quest} />
                                ))}
                        </QuestGrid>
                        <QuestGrid loading />
                        <QuestGrid
                            empty={
                                <EmptyState
                                    title="No quests match these filters"
                                    message="Sample empty state for the grid."
                                />
                            }
                        />
                    </div>
                ),
            },
        ],
    },
    {
        title: 'Settings',
        sections: [
            {
                id: 'member-row',
                title: 'MemberRow',
                purpose:
                    "One person in Settings › Members: inline Role select and Access switch that save on change, Edit and Reset password buttons. Deactivated rows fade; the signed-in admin's own row is locked with a tooltip. Becomes a card at 860px and below. Sample ids match no user, so changes here fail.",
                importPath: '@/components/settings/member-row',
                example: (
                    <ul className="rounded-[14px] border border-line bg-panel px-5 py-2">
                        {sampleMembers.map((member, index) => (
                            <MemberRow
                                key={member.id}
                                member={member}
                                roles={sampleRoles}
                                highlighted={index === 0}
                                onEdit={() => {}}
                                onReset={() => {}}
                            />
                        ))}
                    </ul>
                ),
            },
            {
                id: 'member-dialogs',
                title: 'MemberDialog, ResetPasswordDialog',
                purpose:
                    'Add or edit a member, and set a new password for one. Add mode (role tiles and a starting password with Generate) is only on Settings › Members, since saving it creates a real account. The samples here use an id that matches no user, so saving fails.',
                importPath:
                    '@/components/settings/member-dialog, @/components/settings/reset-password-dialog',
                example: <MemberDialogsExample />,
            },
            {
                id: 'theme-swatch',
                title: 'ThemeSwatch',
                purpose:
                    "One theme preset as a radio tile for Settings › Branding: a mini board drawn in the preset's own colours, with its name and a check when picked. The radios share a name, so it works inside a form.",
                importPath: '@/components/settings/theme-swatch',
                example: <ThemeSwatchExample />,
            },
        ],
    },
];

export default function Library(): React.JSX.Element {
    return (
        <>
            <Head title="Component library" />

            <div>
                <p className="mb-6 text-muted-foreground">
                    Shared UI parts. All names and numbers are sample data.
                </p>

                <div className="grid gap-6 lg:grid-cols-[200px_minmax(0,1fr)]">
                    <nav
                        aria-label="Library sections"
                        className="top-4 self-start rounded-[14px] border border-line bg-panel p-4 lg:sticky"
                    >
                        <div className="flex flex-wrap gap-x-6 gap-y-3 lg:block lg:space-y-4">
                            {groups.map((group) => (
                                <div key={group.title}>
                                    <p className="mb-1 text-[11px] font-bold tracking-[0.12em] text-muted-foreground uppercase">
                                        {group.title}
                                    </p>
                                    <ul className="flex flex-wrap gap-x-3 gap-y-1 text-sm lg:block lg:space-y-1">
                                        {group.sections.map((section) => (
                                            <li key={section.id}>
                                                <a
                                                    href={`#${section.id}`}
                                                    className="text-foreground hover:text-primary-soft"
                                                >
                                                    {section.title}
                                                </a>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            ))}
                        </div>
                    </nav>

                    <div className="min-w-0 space-y-10">
                        {groups.map((group) => (
                            <div key={group.title} className="space-y-5">
                                <h2 className="font-brand text-2xl text-primary-soft">
                                    {group.title}
                                </h2>
                                {group.sections.map((section) => (
                                    <section
                                        key={section.id}
                                        id={section.id}
                                        className="scroll-mt-4 space-y-4 rounded-[14px] border border-line bg-panel p-5"
                                    >
                                        <header>
                                            <h3 className="text-2xl font-bold">
                                                {section.title}
                                            </h3>
                                            <p className="text-sm text-muted-foreground">
                                                {section.purpose}
                                            </p>
                                            <code className="text-xs break-all text-muted-foreground">
                                                {section.importPath}
                                            </code>
                                        </header>
                                        {section.example}
                                    </section>
                                ))}
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </>
    );
}
