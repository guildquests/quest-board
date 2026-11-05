import { Head } from '@inertiajs/react';
import { Crown, Megaphone, Sparkles, Sprout, Trophy } from 'lucide-react';
import { CompletionFeed } from '@/components/game/completion-feed';
import { HighlightCard } from '@/components/game/highlight-card';
import { RankList } from '@/components/game/rank-list';
import { SectionHeading } from '@/components/game/section-heading';
import type { HallOfFamePageProps } from '@/types/hall-of-fame';

export default function HallOfFame({
    month,
    highlights,
    topAdventurers,
    recentCompletions,
}: HallOfFamePageProps): React.JSX.Element {
    const mostCompletedLabel =
        highlights.mostCompleted?.count === 1 ? 'quest' : 'quests';
    const topGiverLabel = highlights.topGiver?.count === 1 ? 'quest' : 'quests';

    return (
        <>
            <Head title="Hall of Fame" />

            <div className="fame grid min-w-0 gap-6 pb-8">
                <header>
                    <h1 className="font-brand text-[clamp(28px,3vw,38px)] font-bold text-primary-soft [text-shadow:0_2px_0_var(--color-primary-shadow)]">
                        Hall of Fame
                    </h1>
                    <p className="mt-1 text-muted-foreground">
                        {month} highlights reset on the 1st of each month.
                    </p>
                </header>

                <section
                    aria-label="Monthly highlights"
                    className="grid min-w-0 grid-cols-3 gap-5 max-[900px]:grid-cols-1"
                >
                    <HighlightCard
                        icon={Trophy}
                        label="Most quests completed"
                        accent="primary"
                        person={highlights.mostCompleted?.person ?? null}
                    >
                        {highlights.mostCompleted?.count ?? 0}{' '}
                        {mostCompletedLabel} this month
                    </HighlightCard>
                    <HighlightCard
                        icon={Megaphone}
                        label="Top quest giver"
                        accent="accent"
                        person={highlights.topGiver?.person ?? null}
                    >
                        {highlights.topGiver?.count ?? 0} of their{' '}
                        {topGiverLabel} completed
                    </HighlightCard>
                    <HighlightCard
                        icon={Sprout}
                        label="Newest adventurer"
                        accent="experiment"
                        person={highlights.newest?.person ?? null}
                    >
                        First quest on {highlights.newest?.first_on ?? ''}
                    </HighlightCard>
                </section>

                <section className="grid min-w-0 grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)] gap-5 max-[1000px]:grid-cols-1">
                    <div className="min-w-0 rounded-[14px] border border-line bg-panel p-5 max-[520px]:p-4">
                        <SectionHeading
                            title="Top adventurers"
                            subtitle="all-time XP"
                            icon={Crown}
                        />
                        <RankList
                            adventurers={topAdventurers}
                            className="mt-4"
                        />
                    </div>
                    <div className="min-w-0 rounded-[14px] border border-line bg-panel p-5 max-[520px]:p-4">
                        <SectionHeading
                            title="Recent completions"
                            icon={Sparkles}
                        />
                        <CompletionFeed
                            completions={recentCompletions}
                            className="mt-4"
                        />
                    </div>
                </section>
            </div>
        </>
    );
}
