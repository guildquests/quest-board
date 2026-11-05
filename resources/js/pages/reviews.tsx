import { Head } from '@inertiajs/react';
import { CheckCheck, Eye } from 'lucide-react';
import { EmptyState } from '@/components/game/empty-state';
import { ReviewCard } from '@/components/quests/review-card';
import { ReviewedFeed } from '@/components/quests/reviewed-feed';
import type { ReviewsPageProps } from '@/types/quests';

export default function Reviews({
    quests,
    difficulties,
    posterXp,
    counts,
    recent,
}: ReviewsPageProps): React.JSX.Element {
    return (
        <>
            <Head title="Reviews" />

            <div className="grid grid-cols-[minmax(0,1fr)] gap-[22px] pb-8">
                <header className="flex flex-wrap items-end justify-between gap-4">
                    <div className="min-w-0 flex-1">
                        <h1 className="font-brand text-[clamp(26px,3vw,34px)] font-bold text-primary-soft [text-shadow:0_2px_0_var(--color-primary-shadow)]">
                            Review queue
                        </h1>
                        <p className="mt-1 text-muted-foreground">
                            Check the proof, then approve or send it back with a
                            note.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <span className="inline-flex items-center gap-2 rounded-full border border-status-review/50 bg-status-review/10 px-3.5 py-2 text-[13.5px] text-status-review">
                            <Eye aria-hidden className="size-4" />
                            <b className="text-foreground tabular-nums">
                                {counts.waiting}
                            </b>{' '}
                            waiting
                        </span>
                        <span className="inline-flex items-center gap-2 rounded-full border border-line bg-panel px-3.5 py-2 text-[13.5px] text-muted-foreground">
                            <CheckCheck aria-hidden className="size-4" />
                            <b className="text-foreground tabular-nums">
                                {counts.approvedThisWeek}
                            </b>{' '}
                            approved this week
                        </span>
                    </div>
                </header>

                <section
                    aria-label="Quests waiting for review"
                    className="grid w-full min-w-0 gap-4"
                >
                    {quests.length > 0 ? (
                        quests.map((quest) => (
                            <ReviewCard
                                key={quest.id}
                                quest={quest}
                                difficulties={difficulties}
                                posterXp={posterXp}
                            />
                        ))
                    ) : (
                        <EmptyState
                            title="Queue clear!"
                            message="Every quest has been reviewed. Nice work."
                            showMascot
                            className="flex-row justify-center border-primary/40 px-6 py-6 text-left max-[520px]:flex-col max-[520px]:text-center"
                        />
                    )}
                </section>

                <ReviewedFeed events={recent} />
            </div>
        </>
    );
}
