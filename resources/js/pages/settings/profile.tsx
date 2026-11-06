import { Form, Head, usePage } from '@inertiajs/react';
import { Check, Save, UserRound } from 'lucide-react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { Portrait } from '@/components/game/portrait';
import { PortraitPicker } from '@/components/game/portrait-picker';
import { SectionHeading } from '@/components/game/section-heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    fieldInputClassName,
    fieldLabelClassName,
    fieldReadOnlyClassName,
} from '@/lib/form-styles';
import { cn } from '@/lib/utils';
import type { PortraitOption } from '@/types';

type ProfileProps = {
    portraits: PortraitOption[];
    currentPortrait: string | null;
};

export default function Profile() {
    const { auth, portraits, currentPortrait } = usePage<ProfileProps>().props;

    return (
        <>
            <Head title="Profile" />

            <SectionHeading
                title="Profile"
                subtitle="How you appear on the board."
                icon={UserRound}
                tone="primary"
            />

            <Form
                {...ProfileController.update.form()}
                options={{
                    preserveScroll: true,
                }}
                className="grid max-w-[560px] gap-1 rounded-2xl border border-line bg-panel p-[22px] max-[520px]:px-4 max-[520px]:py-[18px]"
            >
                {({ processing, errors, recentlySuccessful }) => (
                    <>
                        <div className="mb-3.5 grid gap-3">
                            <div className="flex items-center gap-4">
                                <Portrait
                                    src={auth.user.portrait_url ?? undefined}
                                    name={auth.user.name}
                                    size="xl"
                                />
                                <p className="text-[13.5px] text-muted-foreground">
                                    Pick a portrait, or None to show your
                                    initials.
                                </p>
                            </div>
                            <PortraitPicker
                                portraits={portraits}
                                defaultValue={currentPortrait}
                            />
                            <InputError message={errors.portrait} />
                        </div>

                        <div className="mb-3.5 grid gap-[7px]">
                            <Label
                                htmlFor="name"
                                className={fieldLabelClassName}
                            >
                                Name
                            </Label>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={auth.user.name}
                                required
                                autoComplete="name"
                                className={fieldInputClassName}
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="mb-3.5 grid gap-[7px]">
                            <Label
                                htmlFor="email"
                                className={fieldLabelClassName}
                            >
                                Work email
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                value={auth.user.email}
                                disabled
                                readOnly
                                aria-describedby="email-help"
                                className={cn(
                                    fieldInputClassName,
                                    fieldReadOnlyClassName,
                                )}
                            />
                            <small
                                id="email-help"
                                className="text-[12.5px] text-muted-foreground"
                            >
                                Ask an admin to change your email.
                            </small>
                        </div>

                        <div className="mt-3 flex items-center justify-end gap-3 border-t border-line pt-[18px]">
                            {recentlySuccessful && (
                                <span
                                    role="status"
                                    className="mr-auto inline-flex items-center gap-1.5 text-sm font-semibold text-accent"
                                >
                                    <Check aria-hidden className="size-4" />
                                    Saved
                                </span>
                            )}
                            <Button
                                disabled={processing}
                                className="h-auto px-[22px] py-[11px] font-title text-lg font-bold"
                                data-test="update-profile-button"
                            >
                                <Save aria-hidden />
                                Save
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}
