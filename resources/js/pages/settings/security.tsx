import { Form, Head } from '@inertiajs/react';
import { Check, KeyRound, Save, ShieldAlert } from 'lucide-react';
import { useRef } from 'react';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import { SectionHeading } from '@/components/game/section-heading';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { fieldInputClassName, fieldLabelClassName } from '@/lib/form-styles';

type Props = {
    passwordRules: string;
};

const fields = [
    {
        name: 'current_password',
        label: 'Current password',
        autoComplete: 'current-password',
    },
    { name: 'password', label: 'New password', autoComplete: 'new-password' },
    {
        name: 'password_confirmation',
        label: 'Confirm new password',
        autoComplete: 'new-password',
    },
] as const;

export default function Security({ passwordRules }: Props) {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    return (
        <>
            <Head title="Password" />

            <SectionHeading
                title="Password"
                subtitle="Use at least 8 characters."
                icon={KeyRound}
                tone="primary"
            />

            <Form
                {...SecurityController.update.form()}
                options={{
                    preserveScroll: true,
                }}
                resetOnError={[
                    'password',
                    'password_confirmation',
                    'current_password',
                ]}
                resetOnSuccess
                onError={(errors) => {
                    if (errors.password) {
                        passwordInput.current?.focus();
                    }

                    if (errors.current_password) {
                        currentPasswordInput.current?.focus();
                    }
                }}
                className="grid max-w-[560px] gap-1 rounded-2xl border border-line bg-panel p-[22px] max-[520px]:px-4 max-[520px]:py-[18px]"
            >
                {({ errors, processing, recentlySuccessful }) => {
                    const messages = [
                        ...new Set(Object.values(errors).filter(Boolean)),
                    ];

                    return (
                        <>
                            {fields.map((field) => (
                                <div
                                    key={field.name}
                                    className="mb-3.5 grid gap-[7px]"
                                >
                                    <Label
                                        htmlFor={field.name}
                                        className={fieldLabelClassName}
                                    >
                                        {field.label}
                                    </Label>
                                    <PasswordInput
                                        id={field.name}
                                        name={field.name}
                                        ref={
                                            field.name === 'current_password'
                                                ? currentPasswordInput
                                                : field.name === 'password'
                                                  ? passwordInput
                                                  : undefined
                                        }
                                        autoComplete={field.autoComplete}
                                        passwordrules={
                                            field.name === 'current_password'
                                                ? undefined
                                                : passwordRules
                                        }
                                        aria-invalid={
                                            errors[field.name]
                                                ? true
                                                : undefined
                                        }
                                        className={fieldInputClassName}
                                    />
                                </div>
                            ))}

                            {messages.length > 0 && (
                                <div
                                    role="alert"
                                    className="mt-1 flex items-start gap-2 rounded-[10px] border border-hard/45 bg-hard/10 px-3 py-2.5 text-sm text-[#fecaca]"
                                >
                                    <ShieldAlert
                                        aria-hidden
                                        className="mt-0.5 size-[18px] shrink-0"
                                    />
                                    <ul className="grid gap-1">
                                        {messages.map((message) => (
                                            <li key={message}>{message}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            <div className="mt-3 flex items-center justify-end gap-3 border-t border-line pt-[18px]">
                                {recentlySuccessful && (
                                    <span
                                        role="status"
                                        className="mr-auto inline-flex items-center gap-1.5 text-sm font-semibold text-accent"
                                    >
                                        <Check aria-hidden className="size-4" />
                                        Password changed
                                    </span>
                                )}
                                <Button
                                    disabled={processing}
                                    className="h-auto px-[22px] py-[11px] font-title text-lg font-bold"
                                    data-test="update-password-button"
                                >
                                    <Save aria-hidden />
                                    Change password
                                </Button>
                            </div>
                        </>
                    );
                }}
            </Form>
        </>
    );
}
