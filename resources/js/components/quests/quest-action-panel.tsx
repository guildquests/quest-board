import { Form, Link, usePage } from '@inertiajs/react';
import {
    CalendarClock,
    Crown,
    Eye,
    Hourglass,
    Link as LinkIcon,
    Pencil,
    Send,
    Swords,
    Trash2,
    Undo2,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useState } from 'react';
import { XpBadge } from '@/components/game/xp-badge';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { fieldInputClassName, fieldLabelClassName } from '@/lib/form-styles';
import { cn } from '@/lib/utils';
import { reviews } from '@/routes';
import { accept, destroy, drop, edit, turnIn } from '@/routes/quests';
import type { QuestCan, QuestDetail } from '@/types/quests';
import type { FlashData } from '@/types/ui';

type QuestActionPanelProps = {
    quest: QuestDetail;
    can: QuestCan;
    /** Why Accept is disabled for an Open quest, or null. */
    acceptBlockedReason: string | null;
    /** Days an adventurer has to turn in, from config('quests.claim_days'). */
    claimDays: number;
    className?: string;
};

const textareaClassName = cn(
    fieldInputClassName,
    'min-h-[84px] w-full resize-y rounded-[11px] border px-3.5 leading-normal outline-none',
);

function StateLabel({
    icon: Icon,
    children,
    tone,
    className,
}: {
    icon: LucideIcon;
    children: React.ReactNode;
    tone: 'progress' | 'review' | 'done';
    className?: string;
}): React.JSX.Element {
    return (
        <span
            className={cn(
                'inline-flex w-full items-center justify-center gap-2 rounded-full border px-3 py-2 text-sm font-semibold',
                tone === 'progress' && 'border-accent bg-accent/14 text-accent',
                tone === 'review' &&
                    'border-status-review bg-status-review/12 text-status-review',
                tone === 'done' &&
                    'border-primary bg-linear-to-b from-primary-soft to-primary text-on-primary',
                className,
            )}
        >
            <Icon aria-hidden className="size-4" />
            {children}
        </span>
    );
}

function Deadline({
    children,
}: {
    children: React.ReactNode;
}): React.JSX.Element {
    return (
        <p className="flex items-start gap-2 text-sm text-muted-foreground">
            <CalendarClock aria-hidden className="mt-0.5 size-4 flex-none" />
            <span>{children}</span>
        </p>
    );
}

/** A destructive action behind a confirm step, as a dialog with a form inside. */
function ConfirmAction({
    trigger,
    title,
    description,
    confirmLabel,
    form,
}: {
    trigger: React.ReactNode;
    title: string;
    description: string;
    confirmLabel: string;
    form: { action: string; method: 'post' };
}): React.JSX.Element {
    const [open, setOpen] = useState(false);

    return (
        <>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogTrigger asChild>{trigger}</DialogTrigger>
                <DialogContent className="gap-0 rounded-[18px] border-primary/50 bg-linear-to-b from-panel-2 to-panel p-6 sm:max-w-[420px]">
                    <DialogTitle className="font-title text-[25px] font-bold text-primary-soft">
                        {title}
                    </DialogTitle>
                    <DialogDescription className="mt-1.5 text-muted-foreground">
                        {description}
                    </DialogDescription>
                    <Form {...form} onSuccess={() => setOpen(false)}>
                        {({ processing }) => (
                            <DialogFooter className="mt-5 gap-2.5 max-[520px]:flex-col-reverse">
                                <DialogClose asChild>
                                    <Button type="button" variant="outline">
                                        Keep it
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    {confirmLabel}
                                </Button>
                            </DialogFooter>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>
        </>
    );
}

/** The quest page's action panel: the XP reward on top, then the actions for the quest's state. */
export function QuestActionPanel({
    quest,
    can,
    acceptBlockedReason,
    claimDays,
    className,
}: QuestActionPanelProps): React.JSX.Element {
    // Flash data lives on the page for the visit that carried it, so the stamp
    // plays once and a reload shows the plain label.
    const { flash } = usePage();
    const missionAccepted =
        (flash as FlashData | undefined)?.mission_accepted === true;

    return (
        <section
            aria-label="Quest actions"
            className={cn(
                'grid gap-3.5 rounded-2xl border border-primary/50 bg-linear-to-b from-panel-2 to-panel p-5 shadow-[0_0_26px_color-mix(in_srgb,var(--color-primary)_12%,transparent)]',
                className,
            )}
        >
            <div className="flex items-center gap-3">
                <XpBadge amount={quest.reward_xp} size="lg" />
                <small className="text-[13px] text-muted-foreground">
                    Reward on completion
                </small>
            </div>

            {quest.status === 'open' && (
                <div className="grid gap-3">
                    <Form {...accept.form(quest.id)}>
                        {({ processing }) => (
                            <Button
                                type="submit"
                                size="lg"
                                className="w-full font-title text-xl"
                                disabled={!can.accept || processing}
                            >
                                <Swords aria-hidden />
                                Accept quest
                            </Button>
                        )}
                    </Form>
                    {acceptBlockedReason && (
                        <p className="text-[13px] text-muted-foreground">
                            {acceptBlockedReason}
                        </p>
                    )}
                    {(can.update || can.delete) && (
                        <div className="flex flex-wrap gap-2.5">
                            {can.update && (
                                <Button asChild variant="outline" size="sm">
                                    <Link href={edit(quest.id)}>
                                        <Pencil aria-hidden />
                                        Edit
                                    </Link>
                                </Button>
                            )}
                            {can.delete && (
                                <ConfirmAction
                                    trigger={
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                        >
                                            <Trash2 aria-hidden />
                                            Delete
                                        </Button>
                                    }
                                    title="Delete this quest?"
                                    description="It leaves the board for good, along with its activity log."
                                    confirmLabel="Delete quest"
                                    form={destroy.form(quest.id)}
                                />
                            )}
                        </div>
                    )}
                </div>
            )}

            {quest.status === 'in_progress' && can.turnIn && (
                <div className="grid gap-3">
                    <StateLabel
                        icon={Hourglass}
                        tone="progress"
                        className={
                            missionAccepted
                                ? 'reward-mission-accepted'
                                : undefined
                        }
                    >
                        Mission accepted
                    </StateLabel>
                    <Deadline>
                        Turn in by{' '}
                        <b className="text-foreground">{quest.due_at}</b>. After{' '}
                        {claimDays} days the quest goes back to Open.
                    </Deadline>
                    <Form
                        {...turnIn.form(quest.id)}
                        options={{ preserveScroll: true }}
                        className="grid gap-3"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-[7px]">
                                    <Label
                                        htmlFor="result-url"
                                        className={fieldLabelClassName}
                                    >
                                        Link to your result
                                    </Label>
                                    <div className="relative">
                                        <LinkIcon
                                            aria-hidden
                                            className="pointer-events-none absolute top-1/2 left-[13px] size-[18px] -translate-y-1/2 text-primary"
                                        />
                                        <Input
                                            id="result-url"
                                            name="result_url"
                                            type="url"
                                            defaultValue={
                                                quest.result_url ?? undefined
                                            }
                                            placeholder="https://wiki.example.com/..."
                                            autoComplete="off"
                                            aria-invalid={
                                                errors.result_url
                                                    ? true
                                                    : undefined
                                            }
                                            className={cn(
                                                fieldInputClassName,
                                                'pl-[42px]',
                                            )}
                                        />
                                    </div>
                                    <InputError message={errors.result_url} />
                                </div>
                                <div className="grid gap-[7px]">
                                    <Label
                                        htmlFor="result-note"
                                        className={fieldLabelClassName}
                                    >
                                        Note for the lead
                                    </Label>
                                    <textarea
                                        id="result-note"
                                        name="result_note"
                                        rows={3}
                                        maxLength={1000}
                                        defaultValue={
                                            quest.result_note ?? undefined
                                        }
                                        placeholder="What you found, anything to watch out for"
                                        aria-invalid={
                                            errors.result_note
                                                ? true
                                                : undefined
                                        }
                                        className={textareaClassName}
                                    />
                                    {errors.result_note !==
                                        errors.result_url && (
                                        <InputError
                                            message={errors.result_note}
                                        />
                                    )}
                                </div>
                                <Button
                                    type="submit"
                                    size="lg"
                                    className="w-full font-title text-xl"
                                    disabled={processing}
                                >
                                    <Send aria-hidden />
                                    Turn in
                                </Button>
                            </>
                        )}
                    </Form>
                    {can.drop && (
                        <ConfirmAction
                            trigger={
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="w-full"
                                >
                                    <Undo2 aria-hidden />
                                    Drop quest
                                </Button>
                            }
                            title="Drop this quest?"
                            description="It goes back on the board for someone else, and what you wrote here is cleared."
                            confirmLabel="Drop quest"
                            form={drop.form(quest.id)}
                        />
                    )}
                </div>
            )}

            {quest.status === 'in_progress' && !can.turnIn && (
                <div className="grid gap-3">
                    <StateLabel icon={Hourglass} tone="progress">
                        Claimed by {quest.adventurer?.name}
                    </StateLabel>
                    {quest.due_at && (
                        <Deadline>
                            Due by{' '}
                            <b className="text-foreground">{quest.due_at}</b>.
                        </Deadline>
                    )}
                </div>
            )}

            {quest.status === 'in_review' && (
                <div className="grid gap-3">
                    <StateLabel icon={Eye} tone="review">
                        In review
                    </StateLabel>
                    <p className="text-[13px] text-muted-foreground">
                        A lead will check the work. The adventurer gets the XP
                        once it's approved.
                    </p>
                    {can.review && (
                        <Button asChild variant="outline">
                            <Link href={reviews()}>
                                <Eye aria-hidden />
                                Review in the queue
                            </Link>
                        </Button>
                    )}
                </div>
            )}

            {quest.status === 'done' && (
                <div className="grid gap-3">
                    <StateLabel icon={Crown} tone="done">
                        Completed by {quest.adventurer?.name}
                    </StateLabel>
                    {quest.xp !== null && (
                        <p className="flex items-center gap-2 text-sm text-muted-foreground">
                            Earned <XpBadge amount={quest.xp} />
                        </p>
                    )}
                </div>
            )}
        </section>
    );
}
