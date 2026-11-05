import { router } from '@inertiajs/react';
import { Eye, KeyRound, Pencil } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { Portrait } from '@/components/game/portrait';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { roleTextClassName } from '@/lib/role-styles';
import { cn } from '@/lib/utils';
import { access, role as updateRole } from '@/routes/members';
import type { Member, Role, RoleOption } from '@/types';

type MemberRowProps = {
    member: Member;
    roles: RoleOption[];
    /** Briefly marks a row, e.g. a member who was just added. */
    highlighted?: boolean;
    /** The buttons stay disabled until their handler is given. */
    onEdit?: (member: Member) => void;
    onReset?: (member: Member) => void;
    onImpersonate?: (member: Member) => void;
};

/** Small caption shown above a value when the row becomes a card. */
function CellLabel({ children }: { children: ReactNode }): React.JSX.Element {
    return (
        <span className="mb-1 hidden text-[11px] font-bold tracking-[0.1em] text-muted-foreground uppercase max-[860px]:block">
            {children}
        </span>
    );
}

/** An admin cannot change their own role or access; say why on hover and focus. */
function OwnRowLock({
    locked,
    children,
}: {
    locked: boolean;
    children: ReactNode;
}): React.JSX.Element {
    if (!locked) {
        return <>{children}</>;
    }

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <span tabIndex={0} className="block rounded-[10px]">
                    {children}
                </span>
            </TooltipTrigger>
            <TooltipContent>
                You can't change your own role or access.
            </TooltipContent>
        </Tooltip>
    );
}

export function MemberRow({
    member,
    roles,
    highlighted = false,
    onEdit,
    onReset,
    onImpersonate,
}: MemberRowProps): React.JSX.Element {
    const [roleError, setRoleError] = useState<string>();
    const [accessError, setAccessError] = useState<string>();
    const impersonationReason = member.is_self
        ? "You can't view as yourself."
        : member.role === 'admin'
          ? "Admins can't be viewed as."
          : 'Turn their access back on first.';
    const impersonationButton = (
        <Button
            type="button"
            variant="ghost"
            size="icon"
            title="View as"
            aria-label={`View as ${member.name}`}
            disabled={!member.can_impersonate || !onImpersonate}
            onClick={() => onImpersonate?.(member)}
        >
            <Eye aria-hidden />
        </Button>
    );

    return (
        <li
            className={cn(
                'border-b border-line py-3.5 transition-opacity last:border-b-0',
                !member.active && 'opacity-50',
                highlighted &&
                    'bg-linear-to-r from-accent/10 to-transparent to-70% motion-safe:animate-in motion-safe:fade-in',
            )}
        >
            <div className="grid grid-cols-[minmax(0,2.2fr)_140px_120px_70px_160px_120px] items-center gap-3.5 max-[860px]:grid-cols-2 max-[860px]:gap-x-3.5 max-[860px]:gap-y-3">
                <div className="flex min-w-0 items-center gap-3 max-[860px]:col-span-full">
                    <Portrait
                        src={member.portrait_url ?? undefined}
                        name={member.name}
                        size="sm"
                    />
                    <div className="min-w-0">
                        <b className="block font-semibold">{member.name}</b>
                        <small className="block truncate text-[13px] text-muted-foreground">
                            {member.email}
                        </small>
                    </div>
                </div>

                <div>
                    <CellLabel>Role</CellLabel>
                    <OwnRowLock locked={member.is_self}>
                        <Select
                            value={member.role}
                            disabled={member.is_self}
                            onValueChange={(value: string) => {
                                setRoleError(undefined);
                                router.patch(
                                    updateRole(member.id),
                                    { role: value as Role },
                                    {
                                        preserveScroll: true,
                                        onError: (errors) =>
                                            setRoleError(errors.role),
                                    },
                                );
                            }}
                        >
                            <SelectTrigger
                                aria-label={`Role for ${member.name}`}
                                className={cn(
                                    'w-full font-semibold',
                                    roleTextClassName[member.role],
                                )}
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {roles.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </OwnRowLock>
                    <InputError message={roleError} className="mt-1" />
                </div>

                <div className="text-sm">
                    <CellLabel>Level</CellLabel>
                    Level {member.level}
                    <small className="block text-[12.5px] text-muted-foreground">
                        {member.level_title}
                    </small>
                </div>

                <div className="font-semibold tabular-nums">
                    <CellLabel>Quests</CellLabel>
                    {member.quests_done}
                </div>

                <div>
                    <CellLabel>Access</CellLabel>
                    <div className="flex items-center gap-2.5 text-[13.5px] text-muted-foreground">
                        <OwnRowLock locked={member.is_self}>
                            <Switch
                                checked={member.active}
                                disabled={member.is_self}
                                aria-label={`Access for ${member.name}`}
                                onCheckedChange={(checked) => {
                                    setAccessError(undefined);
                                    router.patch(
                                        access(member.id),
                                        { active: checked },
                                        {
                                            preserveScroll: true,
                                            onError: (errors) =>
                                                setAccessError(errors.active),
                                        },
                                    );
                                }}
                            />
                        </OwnRowLock>
                        {member.active ? 'Active' : 'Deactivated'}
                    </div>
                    <InputError message={accessError} className="mt-1" />
                </div>

                <div className="flex gap-1.5 max-[860px]:justify-end">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        title="Edit"
                        aria-label={`Edit ${member.name}`}
                        disabled={!onEdit}
                        onClick={() => onEdit?.(member)}
                    >
                        <Pencil aria-hidden />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        title="Reset password"
                        aria-label={`Reset password for ${member.name}`}
                        disabled={!onReset}
                        onClick={() => onReset?.(member)}
                    >
                        <KeyRound aria-hidden />
                    </Button>
                    {member.can_impersonate || !onImpersonate ? (
                        impersonationButton
                    ) : (
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <span
                                    tabIndex={0}
                                    className="block rounded-[10px]"
                                >
                                    {impersonationButton}
                                </span>
                            </TooltipTrigger>
                            <TooltipContent>
                                {impersonationReason}
                            </TooltipContent>
                        </Tooltip>
                    )}
                </div>
            </div>
        </li>
    );
}
