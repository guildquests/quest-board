import { Form, Link } from '@inertiajs/react';
import {
    BadgeCheck,
    CornerUpLeft,
    ExternalLink,
    Link as LinkIcon,
} from 'lucide-react';
import { useState } from 'react';
import { ObjectiveList } from '@/components/game/objective-list';
import { Portrait } from '@/components/game/portrait';
import { QuestEmblem } from '@/components/game/quest-emblem';
import { XpBadge } from '@/components/game/xp-badge';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { fieldInputClassName, fieldLabelClassName } from '@/lib/form-styles';
import { cn } from '@/lib/utils';
import { approve, sendBack, show } from '@/routes/quests';
import type { DifficultyOption, ReviewQuest } from '@/types/quests';

type ReviewCardProps = {
    quest: ReviewQuest;
    difficulties: DifficultyOption[];
    posterXp: number;
    className?: string;
};

const textareaClassName = cn(
    fieldInputClassName,
    'min-h-[84px] w-full resize-y rounded-[11px] border px-3.5 leading-normal outline-none',
);

function ReviewLabel({
    children,
}: {
    children: React.ReactNode;
}): React.JSX.Element {
    return (
        <span className="mb-2 block text-xs font-bold tracking-[0.1em] text-muted-foreground uppercase">
            {children}
        </span>
    );
}

/** One review queue card with proof, difficulty choice, and review actions. */
export function ReviewCard({
    quest,
    difficulties,
    posterXp,
    className,
}: ReviewCardProps): React.JSX.Element {
    const [difficulty, setDifficulty] = useState(quest.difficulty);
    const [sendBackOpen, setSendBackOpen] = useState(false);
    const selectedDifficulty =
        difficulties.find((option) => option.value === difficulty) ??
        difficulties[0];

    return (
        <article
            className={cn(
                'grid w-full max-w-full min-w-0 grid-cols-[minmax(0,1fr)_260px] overflow-hidden rounded-2xl border-[1.5px] border-(--review-color)/60 bg-linear-to-b from-panel-2 to-panel max-[900px]:grid-cols-1',
                className,
            )}
            style={
                {
                    '--review-color': `var(--color-${quest.category})`,
                } as React.CSSProperties
            }
        >
            <div className="grid min-w-0 gap-[18px] p-[22px] max-[640px]:p-[18px]">
                <header className="flex min-w-0 items-start gap-4">
                    <QuestEmblem category={quest.category} />
                    <div className="min-w-0">
                        <span className="mb-1 inline-block text-[11px] font-bold tracking-[0.12em] text-(--review-color) uppercase">
                            {quest.category_label}
                        </span>
                        <h2 className="font-title text-[25px] leading-[1.15] font-bold text-balance [overflow-wrap:anywhere] max-[640px]:text-[22px]">
                            <Link
                                href={show(quest.id)}
                                className="outline-none hover:text-primary-soft focus-visible:rounded-sm focus-visible:ring-2 focus-visible:ring-ring"
                            >
                                {quest.title}
                            </Link>
                        </h2>
                        <p className="mt-2 flex items-center gap-2 text-sm text-muted-foreground">
                            <Portrait
                                src={quest.adventurer.portrait_url ?? undefined}
                                name={quest.adventurer.name}
                                size="sm"
                            />
                            <span>
                                <b className="font-semibold text-foreground">
                                    {quest.adventurer.name}
                                </b>{' '}
                                · Level {quest.adventurer.level} ·{' '}
                                {quest.adventurer.level_title} · submitted{' '}
                                {quest.submitted}
                            </span>
                        </p>
                    </div>
                </header>

                <div className="grid grid-cols-2 gap-[18px] border-t border-line pt-4 max-[640px]:grid-cols-1">
                    <div>
                        <ReviewLabel>Objectives</ReviewLabel>
                        <ObjectiveList
                            objectives={quest.objectives}
                            ticked
                            className="gap-1.5 text-sm"
                        />
                    </div>

                    <div className="min-w-0">
                        <ReviewLabel>Proof</ReviewLabel>
                        {quest.result_url && quest.result_display && (
                            <a
                                href={quest.result_url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="flex min-w-0 items-center gap-2 rounded-[10px] border border-line bg-bg/70 px-3 py-2.5 text-sm font-semibold text-accent transition-colors hover:border-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <LinkIcon aria-hidden className="size-4" />
                                <span className="min-w-0 flex-1 truncate">
                                    {quest.result_display}
                                </span>
                                <ExternalLink
                                    aria-hidden
                                    className="size-4 shrink-0"
                                />
                            </a>
                        )}
                        {quest.result_note && (
                            <blockquote className="mt-2.5 border-l-2 border-primary/50 py-0.5 pl-3 text-sm text-[#c9d8d0] italic">
                                “{quest.result_note}”
                            </blockquote>
                        )}
                    </div>
                </div>
            </div>

            <div className="grid min-w-0 content-start gap-4 border-l border-line bg-bg/45 p-[22px] max-[900px]:border-t max-[900px]:border-l-0 max-[640px]:p-[18px]">
                <div className="flex items-center gap-2">
                    <XpBadge amount={selectedDifficulty.xp} size="lg" />
                    <span className="text-[13px] text-muted-foreground">
                        to {quest.adventurer.name}
                    </span>
                </div>

                <div className="grid gap-2">
                    <label
                        htmlFor={`difficulty-${quest.id}`}
                        className={fieldLabelClassName}
                    >
                        Difficulty
                    </label>
                    <Select
                        value={difficulty}
                        onValueChange={(value) =>
                            setDifficulty(value as ReviewQuest['difficulty'])
                        }
                    >
                        <SelectTrigger
                            id={`difficulty-${quest.id}`}
                            className="w-full"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {difficulties.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label} · {option.effort}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <p className="text-sm text-muted-foreground">
                    +{posterXp} XP to{' '}
                    <b className="font-semibold text-foreground">
                        {quest.poster.name}
                    </b>{' '}
                    for posting
                </p>

                <div className="grid gap-2.5">
                    <Form
                        {...approve.form(quest.id)}
                        options={{ preserveScroll: true }}
                    >
                        {({ processing }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="difficulty"
                                    value={difficulty}
                                />
                                <Button
                                    type="submit"
                                    className="w-full font-title text-xl"
                                    disabled={quest.own || processing}
                                >
                                    <BadgeCheck aria-hidden />
                                    Approve
                                </Button>
                            </>
                        )}
                    </Form>
                    <Button
                        type="button"
                        variant="ghost"
                        className="w-full"
                        disabled={quest.own}
                        onClick={() => setSendBackOpen(true)}
                    >
                        <CornerUpLeft aria-hidden />
                        Send back
                    </Button>
                </div>

                {quest.own ? (
                    <p className="text-[13px] leading-relaxed text-muted-foreground">
                        Another lead or admin must review your own quest.
                    </p>
                ) : (
                    sendBackOpen && (
                        <Form
                            {...sendBack.form(quest.id)}
                            options={{ preserveScroll: true }}
                            onSuccess={() => setSendBackOpen(false)}
                            className="grid gap-2"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <label
                                        htmlFor={`send-back-note-${quest.id}`}
                                        className={fieldLabelClassName}
                                    >
                                        What needs fixing?
                                    </label>
                                    <textarea
                                        id={`send-back-note-${quest.id}`}
                                        name="note"
                                        rows={3}
                                        maxLength={1000}
                                        placeholder="e.g. Please add the Dokploy deploy step."
                                        aria-invalid={
                                            errors.note ? true : undefined
                                        }
                                        className={textareaClassName}
                                    />
                                    <InputError message={errors.note} />
                                    <div className="grid grid-cols-2 gap-2">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            onClick={() =>
                                                setSendBackOpen(false)
                                            }
                                        >
                                            Cancel
                                        </Button>
                                        <Button
                                            type="submit"
                                            variant="ghost"
                                            className="border-status-review/60 text-status-review hover:border-status-review"
                                            disabled={processing}
                                        >
                                            Send back
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    )
                )}
            </div>
        </article>
    );
}
