import { Form, Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, ScrollText } from 'lucide-react';
import { useState } from 'react';
import { ChoiceTiles } from '@/components/game/choice-tiles';
import { GameCard } from '@/components/game/game-card';
import { ObjectivesEditor } from '@/components/game/objectives-editor';
import { QuestCard } from '@/components/game/quest-card';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { fieldInputClassName, fieldLabelClassName } from '@/lib/form-styles';
import { categoryIcons, difficultyIcons } from '@/lib/quest-icons';
import { cn } from '@/lib/utils';
import { board } from '@/routes';
import { show, store, update } from '@/routes/quests';
import type {
    QuestCategory,
    QuestDifficulty,
    QuestFormPageProps,
} from '@/types/quests';

const textareaClassName = cn(
    fieldInputClassName,
    'min-h-[110px] w-full resize-y rounded-[11px] border px-3.5 leading-normal outline-none',
);

function Counter({
    value,
    max,
}: {
    value: string;
    max: number;
}): React.JSX.Element {
    return (
        <span className="text-xs text-muted-foreground tabular-nums">
            {value.length}/{max}
        </span>
    );
}

export default function QuestForm({
    quest,
    categories,
    difficulties,
}: QuestFormPageProps): React.JSX.Element {
    const { auth } = usePage().props;
    const editing = quest !== null;

    const [title, setTitle] = useState(quest?.title ?? '');
    const [summary, setSummary] = useState(quest?.summary ?? '');
    const [category, setCategory] = useState<QuestCategory>(
        quest?.category ?? categories[0].value,
    );
    const [difficulty, setDifficulty] = useState<QuestDifficulty>(
        quest?.difficulty ?? difficulties[0].value,
    );

    const selectedDifficulty =
        difficulties.find((option) => option.value === difficulty) ??
        difficulties[0];

    const cancelHref = editing ? show(quest.id) : board();

    return (
        <>
            <Head title={editing ? 'Edit quest' : 'Post a quest'} />

            <div className="grid gap-[18px] pb-8">
                <Link
                    href={cancelHref}
                    className="inline-flex items-center gap-2 justify-self-start text-sm font-semibold text-muted-foreground no-underline hover:text-primary-soft"
                >
                    <ArrowLeft aria-hidden className="size-4" />
                    {editing ? 'Back to the quest' : 'Back to the board'}
                </Link>

                <div>
                    <h2 className="font-brand text-[clamp(26px,3vw,34px)] font-bold text-primary-soft [text-shadow:0_2px_0_var(--color-primary-shadow)]">
                        {editing ? 'Edit quest' : 'Post a quest'}
                    </h2>
                    <p className="mt-1 text-muted-foreground">
                        {editing
                            ? 'Change the details while the quest is still open.'
                            : 'Share a task the team can volunteer for. Keep it small and clear.'}
                    </p>
                </div>

                <div className="grid grid-cols-[minmax(0,1fr)_400px] items-start gap-6 max-[1100px]:grid-cols-[minmax(0,1fr)_340px] max-[900px]:grid-cols-1">
                    <GameCard accent="var(--color-accent)" className="p-6">
                        <Form
                            {...(editing
                                ? update.form(quest.id)
                                : store.form())}
                            options={{ preserveScroll: true }}
                            className="grid gap-1"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="mb-4 grid gap-[7px]">
                                        <div className="flex items-baseline justify-between gap-3">
                                            <Label
                                                htmlFor="quest-title"
                                                className={fieldLabelClassName}
                                            >
                                                Quest title
                                            </Label>
                                            <Counter value={title} max={60} />
                                        </div>
                                        <Input
                                            id="quest-title"
                                            name="title"
                                            value={title}
                                            onChange={(event) =>
                                                setTitle(event.target.value)
                                            }
                                            maxLength={60}
                                            placeholder="e.g. Trial Uptime Kuma for client sites"
                                            autoComplete="off"
                                            aria-invalid={
                                                errors.title ? true : undefined
                                            }
                                            className={fieldInputClassName}
                                        />
                                        <small className="text-[12.5px] text-muted-foreground">
                                            Start with a verb. Short and fun is
                                            fine.
                                        </small>
                                        <InputError message={errors.title} />
                                    </div>

                                    <div className="mb-4 grid gap-[7px]">
                                        <div className="flex items-baseline justify-between gap-3">
                                            <Label
                                                htmlFor="quest-summary"
                                                className={fieldLabelClassName}
                                            >
                                                Why it matters
                                            </Label>
                                            <Counter value={summary} max={90} />
                                        </div>
                                        <Input
                                            id="quest-summary"
                                            name="summary"
                                            value={summary}
                                            onChange={(event) =>
                                                setSummary(event.target.value)
                                            }
                                            maxLength={90}
                                            placeholder="One line shown on the card"
                                            autoComplete="off"
                                            aria-invalid={
                                                errors.summary
                                                    ? true
                                                    : undefined
                                            }
                                            className={fieldInputClassName}
                                        />
                                        <small className="text-[12.5px] text-muted-foreground">
                                            Shown on the card.
                                        </small>
                                        <InputError message={errors.summary} />
                                    </div>

                                    <fieldset className="mb-4 min-w-0">
                                        <legend
                                            className={cn(
                                                fieldLabelClassName,
                                                'mb-[7px]',
                                            )}
                                        >
                                            Category
                                        </legend>
                                        <ChoiceTiles
                                            name="category"
                                            variant="chip"
                                            value={category}
                                            onValueChange={(value) =>
                                                setCategory(
                                                    value as QuestCategory,
                                                )
                                            }
                                            options={categories.map(
                                                (option) => ({
                                                    value: option.value,
                                                    label: option.label,
                                                    icon: categoryIcons[
                                                        option.value
                                                    ],
                                                    color: `var(--color-${option.value})`,
                                                }),
                                            )}
                                        />
                                        <InputError
                                            message={errors.category}
                                            className="mt-1.5"
                                        />
                                    </fieldset>

                                    <fieldset className="mb-4 min-w-0">
                                        <legend
                                            className={cn(
                                                fieldLabelClassName,
                                                'mb-[7px]',
                                            )}
                                        >
                                            Difficulty
                                        </legend>
                                        <ChoiceTiles
                                            name="difficulty"
                                            variant="tile"
                                            value={difficulty}
                                            onValueChange={(value) =>
                                                setDifficulty(
                                                    value as QuestDifficulty,
                                                )
                                            }
                                            options={difficulties.map(
                                                (option) => ({
                                                    value: option.value,
                                                    label: option.label,
                                                    icon: difficultyIcons[
                                                        option.value
                                                    ],
                                                    color: `var(--color-${option.value})`,
                                                    detail: `${option.effort} · ${option.xp} XP`,
                                                }),
                                            )}
                                        />
                                        <small className="mt-1.5 block text-[12.5px] text-muted-foreground">
                                            Difficulty sets the effort and XP
                                            reward.
                                        </small>
                                        <InputError
                                            message={errors.difficulty}
                                            className="mt-1.5"
                                        />
                                    </fieldset>

                                    <div className="mb-4 grid gap-[7px]">
                                        <Label
                                            htmlFor="quest-brief"
                                            className={fieldLabelClassName}
                                        >
                                            The brief
                                        </Label>
                                        <textarea
                                            id="quest-brief"
                                            name="brief"
                                            rows={4}
                                            maxLength={2000}
                                            defaultValue={quest?.brief}
                                            placeholder="What is the problem, and what should the adventurer find out or build?"
                                            aria-invalid={
                                                errors.brief ? true : undefined
                                            }
                                            className={textareaClassName}
                                        />
                                        <InputError message={errors.brief} />
                                    </div>

                                    <div className="mb-4 grid gap-[7px]">
                                        <span className={fieldLabelClassName}>
                                            Objectives
                                        </span>
                                        <ObjectivesEditor
                                            defaultValues={quest?.objectives}
                                            errors={errors}
                                        />
                                    </div>

                                    <div className="mb-4 grid gap-[7px]">
                                        <Label
                                            htmlFor="quest-proof"
                                            className={fieldLabelClassName}
                                        >
                                            Proof of completion
                                        </Label>
                                        <Input
                                            id="quest-proof"
                                            name="proof"
                                            defaultValue={quest?.proof}
                                            maxLength={500}
                                            placeholder="e.g. A short note in the SOP site with your verdict"
                                            autoComplete="off"
                                            aria-invalid={
                                                errors.proof ? true : undefined
                                            }
                                            className={fieldInputClassName}
                                        />
                                        <InputError message={errors.proof} />
                                    </div>

                                    <div className="mt-3 flex items-center justify-end gap-3 border-t border-line pt-[18px] max-[520px]:flex-col-reverse max-[520px]:items-stretch">
                                        <Button
                                            asChild
                                            variant="outline"
                                            className="max-[520px]:w-full"
                                        >
                                            <Link href={cancelHref}>
                                                Cancel
                                            </Link>
                                        </Button>
                                        <Button
                                            type="submit"
                                            size="lg"
                                            disabled={processing}
                                            className="px-[26px] font-title text-xl max-[520px]:w-full"
                                        >
                                            <ScrollText aria-hidden />
                                            {editing
                                                ? 'Save changes'
                                                : 'Post to the board'}
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </GameCard>

                    <aside className="sticky top-4 grid gap-2.5 max-[900px]:static max-[900px]:order-first">
                        <span className="text-xs font-bold tracking-widest text-muted-foreground uppercase">
                            Live preview
                        </span>
                        <QuestCard
                            title={title.trim() || 'Your quest title'}
                            description={
                                summary.trim() || 'Why it matters goes here.'
                            }
                            category={category}
                            difficulty={difficulty}
                            effort={selectedDifficulty.effort}
                            xp={selectedDifficulty.xp}
                            status="open"
                            owner={{
                                name: auth.user.name,
                                portrait: auth.user.portrait_url ?? undefined,
                            }}
                        />
                        <p className="text-[13px] text-muted-foreground">
                            This is how your quest will look on the board.
                        </p>
                    </aside>
                </div>
            </div>
        </>
    );
}
