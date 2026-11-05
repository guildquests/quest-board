import { Form } from '@inertiajs/react';
import { Dices, KeyRound, Save } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { fieldInputClassName } from '@/lib/form-styles';
import {
    describePasswordRules,
    generatePassword,
} from '@/lib/generate-password';
import { password as resetPassword } from '@/routes/members';
import type { Member } from '@/types';

type ResetPasswordDialogProps = {
    open: boolean;
    /** Kept after closing, so the content does not change during the close animation. */
    member: Member | null;
    onClose: () => void;
    passwordRules: string;
};

export function ResetPasswordDialog({
    open,
    member,
    onClose,
    passwordRules,
}: ResetPasswordDialogProps): React.JSX.Element {
    return (
        <Dialog open={open} onOpenChange={(next) => !next && onClose()}>
            <DialogContent className="max-h-[calc(100dvh-2rem)] gap-0 overflow-y-auto rounded-[18px] border-primary/50 bg-linear-to-b from-panel-2 to-panel p-0 shadow-[0_0_40px_color-mix(in_srgb,var(--color-accent)_15%,transparent),0_30px_80px_rgb(0_0_0/0.7)] sm:max-w-[520px]">
                {member && (
                    <ResetForm
                        member={member}
                        passwordRules={passwordRules}
                        onClose={onClose}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}

function ResetForm({
    member,
    passwordRules,
    onClose,
}: {
    member: Member;
    passwordRules: string;
    onClose: () => void;
}): React.JSX.Element {
    const [password, setPassword] = useState('');

    return (
        <Form
            {...resetPassword.form(member.id)}
            options={{ preserveScroll: true }}
            onSuccess={() => {
                onClose();
                toast.success(
                    `Password reset. ${member.name} is signed out everywhere.`,
                );
            }}
            className="grid gap-1 px-6 pt-[22px] pb-5 max-[520px]:px-4 max-[520px]:pt-[18px]"
        >
            {({ processing, errors }) => (
                <>
                    <div className="mb-2.5 pr-8">
                        <DialogTitle className="flex items-center gap-2.5 font-title text-[25px] font-bold text-primary-soft">
                            <KeyRound
                                aria-hidden
                                className="size-5 text-primary"
                            />
                            Reset password
                        </DialogTitle>
                        <DialogDescription className="mt-1 text-sm text-muted-foreground">
                            New password for{' '}
                            <b className="text-foreground">{member.name}</b>.
                            Give it to them in person.
                        </DialogDescription>
                    </div>

                    <div className="mb-3 grid gap-[7px]">
                        <div className="flex gap-2.5 max-[520px]:flex-col">
                            <div className="min-w-0 flex-1">
                                <PasswordInput
                                    name="password"
                                    value={password}
                                    onChange={(event) =>
                                        setPassword(event.target.value)
                                    }
                                    aria-label="New password"
                                    autoComplete="off"
                                    passwordrules={passwordRules}
                                    required
                                    className={fieldInputClassName}
                                />
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                className="h-auto py-3"
                                onClick={() => setPassword(generatePassword())}
                            >
                                <Dices aria-hidden />
                                Generate
                            </Button>
                        </div>
                        <p className="text-[13px] text-muted-foreground">
                            {describePasswordRules(passwordRules)}
                        </p>
                        <InputError message={errors.password} />
                    </div>

                    <div className="mt-3.5 flex justify-end gap-2.5 max-[520px]:flex-col-reverse">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save aria-hidden />
                            Save password
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
