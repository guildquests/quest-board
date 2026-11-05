import { Link, usePage } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import * as DialogPrimitive from '@radix-ui/react-dialog';
import { ArrowRight, Sparkles, Star, Trophy, XIcon } from 'lucide-react';
import { AchievementBadge } from '@/components/game/achievement-badge';
import { QuestEmblem } from '@/components/game/quest-emblem';
import { XpBar } from '@/components/game/xp-bar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogDescription,
    DialogOverlay,
    DialogPortal,
    DialogTitle,
} from '@/components/ui/dialog';
import { useRewardTimeline } from '@/hooks/use-reward-timeline';
import { badgeLook } from '@/lib/badges';
import { cn } from '@/lib/utils';
import type { RewardItem, RewardSummary } from '@/types/quests';

type RewardDialogProps = {
    reward: RewardItem | RewardSummary | null;
    open: boolean;
    onClose: () => void;
    /** Called instead of following a link, so the page can mark the reward seen first. */
    onNavigate?: (href: InertiaLinkProps['href']) => void;
    profileHref: InertiaLinkProps['href'];
    boardHref: InertiaLinkProps['href'];
};

function ActionLink({
    href,
    children,
    onNavigate,
    onClose,
    variant,
}: {
    href: InertiaLinkProps['href'];
    children: React.ReactNode;
    onNavigate?: (href: InertiaLinkProps['href']) => void;
    onClose: () => void;
    variant?: 'outline';
}): React.JSX.Element {
    return (
        <Button asChild variant={variant} className="w-full">
            <Link
                href={href}
                onClick={(event) => {
                    if (onNavigate) {
                        event.preventDefault();
                        onNavigate(href);
                    } else {
                        onClose();
                    }
                }}
            >
                {children}
                <ArrowRight aria-hidden />
            </Link>
        </Button>
    );
}

export function RewardDialog({
    reward,
    open,
    onClose,
    onNavigate,
    profileHref,
    boardHref,
}: RewardDialogProps): React.JSX.Element | null {
    const { branding } = usePage().props;
    const timeline = useRewardTimeline(reward, open);

    if (reward === null) {
        return null;
    }

    const item = 'quest' in reward ? reward : null;
    const levelUp = timeline.levelUpVisible ? timeline.levelUp : null;
    const heading = levelUp
        ? 'You are now a ' + levelUp.title
        : timeline.isSummary
          ? 'Rewards collected'
          : item?.quest.title;
    const kicker = levelUp
        ? 'Level up!'
        : timeline.isSummary
          ? 'A fine run of quests'
          : item?.kind === 'poster'
            ? 'Your quest was completed'
            : 'Quest complete';

    return (
        <Dialog
            open={open}
            onOpenChange={(nextOpen) => {
                if (!nextOpen) {
                    onClose();
                }
            }}
        >
            <DialogPortal>
                <DialogOverlay className="reward-dialog-overlay" />
                <DialogPrimitive.Content
                    className={cn(
                        'reward-dialog',
                        levelUp && 'reward-dialog-levelup',
                    )}
                >
                    <div className="reward-dialog-rays" aria-hidden />
                    <div className="reward-dialog-sparks" aria-hidden>
                        {Array.from({ length: 12 }, (_, index) => (
                            <i key={index} />
                        ))}
                    </div>

                    <div className="reward-dialog-card">
                        <img
                            src={branding.mascots.reward}
                            alt=""
                            aria-hidden
                            className="reward-dialog-mascot"
                        />
                        <DialogClose className="reward-dialog-close">
                            <XIcon aria-hidden />
                            <span className="sr-only">Close</span>
                        </DialogClose>

                        <div className="reward-dialog-medal">
                            {levelUp ? (
                                <div className="quest-emblem size-full rounded-full p-[5px]">
                                    <span className="reward-dialog-level-number quest-emblem-core grid size-full place-items-center rounded-full font-brand">
                                        {levelUp.level}
                                    </span>
                                </div>
                            ) : item ? (
                                <QuestEmblem
                                    category={item.quest.category}
                                    className="size-full p-[5px]"
                                />
                            ) : (
                                <div className="quest-emblem size-full rounded-full p-[5px]">
                                    <span className="quest-emblem-core grid size-full place-items-center rounded-full text-primary-soft">
                                        <Trophy aria-hidden />
                                    </span>
                                </div>
                            )}
                        </div>

                        <p className="reward-dialog-kicker">{kicker}</p>
                        <DialogTitle className="reward-dialog-title">
                            {heading}
                        </DialogTitle>
                        {levelUp && item && (
                            <p className="reward-dialog-by">
                                Quest complete: {item.quest.title}
                            </p>
                        )}
                        <DialogDescription className="reward-dialog-by">
                            {timeline.isSummary
                                ? `${timeline.count} quests added to your legend`
                                : item?.by_label}
                        </DialogDescription>

                        <div className="reward-dialog-xp">
                            <Star aria-hidden />
                            <span>+{timeline.displayedXp} XP</span>
                        </div>
                        {timeline.isSummary && (
                            <p className="reward-dialog-by">
                                {timeline.totalXp} XP in total
                            </p>
                        )}

                        <div className="reward-dialog-level">
                            <span
                                className={cn(
                                    'reward-dialog-lv',
                                    levelUp && 'reward-dialog-lv-bump',
                                )}
                                key={timeline.level}
                            >
                                {timeline.level}
                            </span>
                            <div className="min-w-0 flex-1 text-left">
                                <p className="mb-1.5 flex items-center justify-between gap-2 text-sm font-semibold">
                                    <span>
                                        Level {timeline.level}
                                        {levelUp && ` · ${levelUp.title}`}
                                    </span>
                                    {reward.next === null && (
                                        <span className="text-primary-soft">
                                            Max level
                                        </span>
                                    )}
                                </p>
                                <XpBar
                                    key={`${timeline.level}-${timeline.levelUpVisible}`}
                                    xp={timeline.barXp}
                                    levelFloor={reward.floor}
                                    nextLevelXp={reward.next}
                                />
                            </div>
                        </div>

                        {timeline.badgesVisible &&
                            timeline.badges.length > 0 && (
                                <div className="reward-dialog-badge-pop">
                                    <p className="mb-3 flex items-center justify-center gap-2 font-title text-lg font-bold text-accent">
                                        <Sparkles
                                            aria-hidden
                                            className="size-4"
                                        />
                                        Badge earned
                                    </p>
                                    <div className="flex flex-wrap justify-center gap-3">
                                        {timeline.badges.map((badge) => (
                                            <AchievementBadge
                                                key={badge.key}
                                                name={badge.name}
                                                description="Newly earned"
                                                icon={badgeLook[badge.key].icon}
                                                colour={
                                                    badgeLook[badge.key].colour
                                                }
                                                unlocked
                                                className="w-32"
                                            />
                                        ))}
                                    </div>
                                </div>
                            )}

                        <div className="reward-dialog-actions">
                            <ActionLink
                                href={profileHref}
                                variant="outline"
                                onNavigate={onNavigate}
                                onClose={onClose}
                            >
                                View profile
                            </ActionLink>
                            <ActionLink
                                href={boardHref}
                                onNavigate={onNavigate}
                                onClose={onClose}
                            >
                                Find another quest
                            </ActionLink>
                        </div>
                    </div>
                </DialogPrimitive.Content>
            </DialogPortal>
        </Dialog>
    );
}
