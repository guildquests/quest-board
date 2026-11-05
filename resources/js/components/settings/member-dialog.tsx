import { Form } from '@inertiajs/react';
import {
    Crown,
    Dices,
    Save,
    ShieldAlert,
    ShieldCheck,
    UserPlus,
    UserRound,
    UsersRound,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useState } from 'react';
import { PortraitPicker } from '@/components/game/portrait-picker';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { fieldInputClassName, fieldLabelClassName } from '@/lib/form-styles';
import {
    describePasswordRules,
    generatePassword,
} from '@/lib/generate-password';
import { roleAccent } from '@/lib/role-styles';
import { cn } from '@/lib/utils';
import { store, update } from '@/routes/members';
import type { Member, PortraitOption, Role, RoleOption } from '@/types';

type MemberDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** The member to edit, or null to add a new one. */
    member: Member | null;
    roles: RoleOption[];
    portraits: PortraitOption[];
    passwordRules: string;
};

const roleIcons: Record<Role, LucideIcon> = {
    member: UserRound,
    lead: ShieldCheck,
    admin: Crown,
};

export function MemberDialog({
    open,
    onOpenChange,
    member,
    roles,
    portraits,
    passwordRules,
}: MemberDialogProps): React.JSX.Element {
    const editing = member !== null;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[calc(100dvh-2rem)] gap-0 overflow-y-auto rounded-[18px] border-primary/50 bg-linear-to-b from-panel-2 to-panel p-0 shadow-[0_0_40px_color-mix(in_srgb,var(--color-accent)_15%,transparent),0_30px_80px_rgb(0_0_0/0.7)] sm:max-w-[520px]">
                {/* Remounts with the dialog, so every open starts with a clean form. */}
                <MemberForm
                    member={member}
                    editing={editing}
                    roles={roles}
                    portraits={portraits}
                    passwordRules={passwordRules}
                    onDone={() => onOpenChange(false)}
                />
            </DialogContent>
        </Dialog>
    );
}

function MemberForm({
    member,
    editing,
    roles,
    portraits,
    passwordRules,
    onDone,
}: Omit<MemberDialogProps, 'open' | 'onOpenChange'> & {
    editing: boolean;
    onDone: () => void;
}): React.JSX.Element {
    const [password, setPassword] = useState('');

    return (
        <Form
            {...(member ? update.form(member.id) : store.form())}
            options={{ preserveScroll: true }}
            onSuccess={onDone}
            className="grid gap-1 px-6 pt-[22px] pb-5 max-[520px]:px-4 max-[520px]:pt-[18px]"
        >
            {({ processing, errors }) => (
                <>
                    <div className="mb-2.5 pr-8">
                        <DialogTitle className="flex items-center gap-2.5 font-title text-[25px] font-bold text-primary-soft">
                            <UserPlus
                                aria-hidden
                                className="size-5 text-primary"
                            />
                            {editing ? 'Edit member' : 'Add a member'}
                        </DialogTitle>
                        <DialogDescription className="sr-only">
                            {editing
                                ? 'Change the name, email, or portrait.'
                                : 'Create an account. There is no public sign-up.'}
                        </DialogDescription>
                    </div>

                    <div className="mb-3 grid gap-[7px]">
                        <Label
                            htmlFor="member-name"
                            className={fieldLabelClassName}
                        >
                            Full name
                        </Label>
                        <Input
                            id="member-name"
                            name="name"
                            defaultValue={member?.name}
                            placeholder="e.g. Nandar Hlaing"
                            autoComplete="off"
                            required
                            className={fieldInputClassName}
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="mb-3 grid gap-[7px]">
                        <Label
                            htmlFor="member-email"
                            className={fieldLabelClassName}
                        >
                            Work email
                        </Label>
                        <Input
                            id="member-email"
                            name="email"
                            type="email"
                            defaultValue={member?.email}
                            placeholder="name@example.com"
                            autoComplete="off"
                            required
                            className={fieldInputClassName}
                        />
                        <InputError message={errors.email} />
                    </div>

                    {!editing && (
                        <fieldset className="mb-3 min-w-0">
                            <legend
                                className={cn(fieldLabelClassName, 'mb-[7px]')}
                            >
                                Role
                            </legend>
                            <div className="grid grid-cols-3 gap-2.5 max-[520px]:grid-cols-1">
                                {roles.map((role, index) => {
                                    const Icon = roleIcons[role.value];

                                    return (
                                        <label
                                            key={role.value}
                                            className="relative"
                                            style={
                                                {
                                                    '--c': roleAccent[
                                                        role.value
                                                    ],
                                                } as React.CSSProperties
                                            }
                                        >
                                            <input
                                                type="radio"
                                                name="role"
                                                value={role.value}
                                                defaultChecked={index === 0}
                                                className="peer sr-only"
                                            />
                                            <span className="grid h-full cursor-pointer content-start justify-items-center gap-0.5 rounded-xl border border-line bg-panel px-2.5 py-3.5 text-center transition-[border-color,box-shadow,background-color] peer-checked:border-(--c) peer-checked:bg-[color-mix(in_srgb,var(--c)_14%,var(--color-panel))] peer-checked:shadow-[0_0_16px_color-mix(in_srgb,var(--c)_35%,transparent)] peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-primary hover:border-accent/60 max-[520px]:grid-cols-[auto_1fr] max-[520px]:justify-items-start max-[520px]:gap-x-3 max-[520px]:text-left">
                                                <Icon
                                                    aria-hidden
                                                    className="mb-1 size-[22px] text-(--c) max-[520px]:row-span-2 max-[520px]:mb-0"
                                                />
                                                <b className="font-title text-lg">
                                                    {role.label}
                                                </b>
                                                <small className="text-[12.5px] leading-snug text-muted-foreground">
                                                    {role.description}
                                                </small>
                                            </span>
                                        </label>
                                    );
                                })}
                            </div>
                            <InputError
                                message={errors.role}
                                className="mt-1.5"
                            />
                        </fieldset>
                    )}

                    <fieldset className="mb-3 min-w-0">
                        <legend className={cn(fieldLabelClassName, 'mb-[7px]')}>
                            Portrait
                        </legend>
                        <PortraitPicker
                            portraits={portraits}
                            defaultValue={member?.portrait ?? null}
                        />
                        <p className="mt-2 text-[13px] text-muted-foreground">
                            Without a portrait, their initials show instead.
                        </p>
                        <InputError message={errors.portrait} />
                    </fieldset>

                    {!editing && (
                        <div className="mb-3 grid gap-[7px]">
                            <Label
                                htmlFor="member-password"
                                className={fieldLabelClassName}
                            >
                                Starting password
                            </Label>
                            <div className="flex gap-2.5 max-[520px]:flex-col">
                                <div className="min-w-0 flex-1">
                                    <PasswordInput
                                        id="member-password"
                                        name="password"
                                        value={password}
                                        onChange={(event) =>
                                            setPassword(event.target.value)
                                        }
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
                                    onClick={() =>
                                        setPassword(generatePassword())
                                    }
                                >
                                    <Dices aria-hidden />
                                    Generate
                                </Button>
                            </div>
                            <p className="text-[13px] text-muted-foreground">
                                Give it to them in person. They can change it in
                                Settings. {describePasswordRules(passwordRules)}
                            </p>
                            <InputError message={errors.password} />
                        </div>
                    )}

                    <div className="mt-3.5 flex justify-end gap-2.5 max-[520px]:flex-col-reverse">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onDone}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {editing ? (
                                <Save aria-hidden />
                            ) : (
                                <UsersRound aria-hidden />
                            )}
                            {editing ? 'Save' : 'Add member'}
                        </Button>
                    </div>

                    {Object.keys(errors).length > 0 && (
                        <p
                            role="alert"
                            className="mt-3 flex items-center gap-2 text-sm text-red-400"
                        >
                            <ShieldAlert aria-hidden className="size-4" />
                            Check the highlighted fields and try again.
                        </p>
                    )}
                </>
            )}
        </Form>
    );
}
