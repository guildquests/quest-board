import { Head, Link, router } from '@inertiajs/react';
import { UserPlus, UsersRound } from 'lucide-react';
import { useState } from 'react';
import { SectionHeading } from '@/components/game/section-heading';
import { MemberDialog } from '@/components/settings/member-dialog';
import { MemberRow } from '@/components/settings/member-row';
import { ResetPasswordDialog } from '@/components/settings/reset-password-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { roleBadgeClassName } from '@/lib/role-styles';
import { cn } from '@/lib/utils';
import { impersonate, index as membersIndex } from '@/routes/members';
import type {
    Member,
    MemberCounts,
    PortraitOption,
    Role,
    RoleOption,
} from '@/types';

type Props = {
    members: Member[];
    counts: MemberCounts;
    /** The active role filter, or null for everyone. */
    filter: Role | null;
    roles: RoleOption[];
    portraits: PortraitOption[];
    passwordRules: string;
    /** Id of a member who was just added. */
    highlightId: number | null;
};

const tableHeadings = ['Member', 'Role', 'Level', 'Quests', 'Access'] as const;

export default function Members({
    members,
    counts,
    filter,
    roles,
    portraits,
    passwordRules,
    highlightId,
}: Props) {
    // The member stays set after closing, so the dialog content does not change
    // while it fades out.
    const [memberDialog, setMemberDialog] = useState<{
        open: boolean;
        member: Member | null;
    }>({ open: false, member: null });
    const [resetDialog, setResetDialog] = useState<{
        open: boolean;
        member: Member | null;
    }>({ open: false, member: null });

    const pills: { key: Role | 'all'; label: string }[] = [
        { key: 'all', label: 'All' },
        ...roles.map((role) => ({ key: role.value, label: `${role.label}s` })),
    ];

    return (
        <>
            <Head title="Members" />

            <SectionHeading
                title="Members"
                subtitle="Only admins can add people. There is no public sign-up."
                icon={UsersRound}
                tone="primary"
                badge={
                    <Badge
                        variant="outline"
                        className={cn(
                            'ml-1 font-sans',
                            roleBadgeClassName.admin,
                        )}
                    >
                        Admin only
                    </Badge>
                }
                action={
                    <Button
                        className="max-[520px]:w-full"
                        onClick={() =>
                            setMemberDialog({ open: true, member: null })
                        }
                    >
                        <UserPlus aria-hidden />
                        Add member
                    </Button>
                }
            />

            <nav
                aria-label="Filter members by role"
                className="flex flex-wrap gap-2"
            >
                {pills.map((pill) => {
                    const selected = (filter ?? 'all') === pill.key;

                    return (
                        <Link
                            key={pill.key}
                            href={membersIndex(
                                pill.key === 'all'
                                    ? undefined
                                    : { query: { role: pill.key } },
                            )}
                            preserveScroll
                            aria-current={selected ? 'true' : undefined}
                            className={cn(
                                'inline-flex items-center gap-2 rounded-full border border-line bg-panel px-4 py-2 text-sm font-semibold text-muted-foreground transition-colors hover:border-accent/60 hover:text-foreground',
                                selected &&
                                    'border-accent bg-accent/12 text-foreground',
                            )}
                        >
                            {pill.label}
                            <span className="rounded-full bg-white/[0.06] px-2 text-xs tabular-nums">
                                {counts[pill.key]}
                            </span>
                        </Link>
                    );
                })}
            </nav>

            <section className="rounded-[14px] border border-line bg-panel px-5 py-2 max-[860px]:px-4 max-[860px]:py-1">
                <div
                    aria-hidden
                    className="grid grid-cols-[minmax(0,2.2fr)_140px_120px_70px_160px_120px] gap-3.5 border-b border-line py-3 text-xs font-bold tracking-[0.1em] text-muted-foreground uppercase max-[860px]:hidden"
                >
                    {tableHeadings.map((heading) => (
                        <span key={heading}>{heading}</span>
                    ))}
                    <span />
                </div>

                {members.length > 0 ? (
                    <ul>
                        {members.map((member) => (
                            <MemberRow
                                key={member.id}
                                member={member}
                                roles={roles}
                                highlighted={member.id === highlightId}
                                onEdit={(target) =>
                                    setMemberDialog({
                                        open: true,
                                        member: target,
                                    })
                                }
                                onReset={(target) =>
                                    setResetDialog({
                                        open: true,
                                        member: target,
                                    })
                                }
                                onImpersonate={(target) =>
                                    router.post(impersonate(target.id))
                                }
                            />
                        ))}
                    </ul>
                ) : (
                    <p className="p-7 text-center text-muted-foreground">
                        Nobody has this role yet.
                    </p>
                )}
            </section>

            <div className="grid grid-cols-3 gap-3 text-[13.5px] text-muted-foreground max-[1000px]:grid-cols-1">
                {roles.map((role) => (
                    <div
                        key={role.value}
                        className="flex items-center gap-2.5 rounded-xl border border-line bg-bg/40 px-3.5 py-3"
                    >
                        <Badge
                            variant="outline"
                            className={roleBadgeClassName[role.value]}
                        >
                            {role.label}
                        </Badge>
                        {role.description}
                    </div>
                ))}
            </div>

            <MemberDialog
                open={memberDialog.open}
                onOpenChange={(open) =>
                    setMemberDialog((current) => ({ ...current, open }))
                }
                member={memberDialog.member}
                roles={roles}
                portraits={portraits}
                passwordRules={passwordRules}
            />
            <ResetPasswordDialog
                open={resetDialog.open}
                member={resetDialog.member}
                onClose={() =>
                    setResetDialog((current) => ({ ...current, open: false }))
                }
                passwordRules={passwordRules}
            />
        </>
    );
}
