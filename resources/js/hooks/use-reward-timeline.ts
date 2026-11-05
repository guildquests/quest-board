import { useEffect, useState } from 'react';
import type {
    RewardBadge,
    RewardItem,
    RewardLevelUp,
    RewardSummary,
} from '@/types/quests';

export type RewardTimelineState = {
    isSummary: boolean;
    count: number;
    totalXp: number;
    displayedXp: number;
    barXp: number;
    level: number;
    levelUp: RewardLevelUp | null;
    levelUpVisible: boolean;
    badgesVisible: boolean;
    badges: RewardBadge[];
};

type RewardTimelineInput = RewardItem | RewardSummary;

function isSummary(reward: RewardTimelineInput): reward is RewardSummary {
    return 'count' in reward;
}

function initialState(reward: RewardTimelineInput | null): RewardTimelineState {
    if (reward === null) {
        return {
            isSummary: false,
            count: 0,
            totalXp: 0,
            displayedXp: 0,
            barXp: 0,
            level: 1,
            levelUp: null,
            levelUpVisible: false,
            badgesVisible: false,
            badges: [],
        };
    }

    const summary = isSummary(reward);
    const levelUp = reward.level_up ?? null;

    return {
        isSummary: summary,
        count: summary ? reward.count : 1,
        totalXp: summary ? reward.total_xp : reward.xp,
        displayedXp: 0,
        barXp: reward.from_xp,
        level: reward.level_from,
        levelUp,
        levelUpVisible: false,
        badgesVisible: levelUp === null,
        badges: reward.badges,
    };
}

function finalState(reward: RewardTimelineInput): RewardTimelineState {
    const summary = isSummary(reward);
    const levelUp = reward.level_up ?? null;

    return {
        isSummary: summary,
        count: summary ? reward.count : 1,
        totalXp: summary ? reward.total_xp : reward.xp,
        displayedXp: summary ? reward.total_xp : reward.xp,
        barXp: barXpFor(reward, levelUp),
        level: levelUp?.level ?? reward.level_from,
        levelUp,
        levelUpVisible: levelUp !== null,
        badgesVisible: true,
        badges: reward.badges,
    };
}

/**
 * The XP to show on the bar. A reward without a level-up stays on its own
 * level, so a reward that crosses into the next level but is not the last in
 * the queue must not show XP past that level's target.
 */
function barXpFor(
    reward: RewardTimelineInput,
    levelUp: RewardLevelUp | null,
): number {
    if (levelUp === null && reward.next !== null) {
        return Math.min(reward.to_xp, reward.next);
    }

    return reward.to_xp;
}

function easeOutCubic(progress: number): number {
    return 1 - (1 - progress) ** 3;
}

function reducedMotion(): boolean {
    return (
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
}

export function useRewardTimeline(
    reward: RewardTimelineInput | null,
    open: boolean,
): RewardTimelineState {
    const [timeline, setTimeline] = useState<RewardTimelineState>(() =>
        reward !== null && reducedMotion()
            ? finalState(reward)
            : initialState(reward),
    );

    useEffect(() => {
        if (!open || reward === null) {
            return;
        }

        const finish = finalState(reward);
        const timers: number[] = [];
        let animationFrame: number | null = null;

        const clearAnimation = (): void => {
            if (animationFrame !== null) {
                window.cancelAnimationFrame(animationFrame);
            }

            timers.forEach((timer) => window.clearTimeout(timer));
        };

        if (reducedMotion()) {
            return clearAnimation;
        }

        const animateCount = (now: number, beganAt: number): void => {
            const progress = Math.min(1, (now - beganAt) / 900);
            const eased = easeOutCubic(progress);
            const rewardXp = isSummary(reward) ? reward.total_xp : reward.xp;
            const displayedXp = Math.round(rewardXp * eased);

            setTimeline((current) => ({ ...current, displayedXp }));

            if (progress < 1) {
                animationFrame = window.requestAnimationFrame((nextNow) =>
                    animateCount(nextNow, beganAt),
                );
            }
        };

        animationFrame = window.requestAnimationFrame((now) =>
            animateCount(now, now),
        );

        timers.push(
            window.setTimeout(() => {
                setTimeline((current) => ({
                    ...current,
                    barXp: barXpFor(reward, finish.levelUp),
                    displayedXp: finish.totalXp,
                    badgesVisible: finish.levelUp === null,
                }));
            }, 350),
        );

        if (finish.levelUp !== null) {
            timers.push(
                window.setTimeout(() => {
                    setTimeline((current) => ({
                        ...current,
                        level: finish.level,
                        levelUpVisible: true,
                        badgesVisible: true,
                        barXp: reward.floor,
                    }));

                    timers.push(
                        window.setTimeout(() => {
                            setTimeline((current) => ({
                                ...current,
                                barXp: reward.to_xp,
                            }));
                        }, 60),
                    );
                }, 1500),
            );
        } else {
            timers.push(
                window.setTimeout(() => {
                    setTimeline(finish);
                }, 900),
            );
        }

        return clearAnimation;
    }, [open, reward]);

    return timeline;
}
