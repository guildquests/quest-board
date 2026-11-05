import { Form, Head } from '@inertiajs/react';
import { KeyRound, Mail, ScrollText, ShieldAlert, Swords } from 'lucide-react';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { fieldInputClassName, fieldLabelClassName } from '@/lib/form-styles';
import { cn } from '@/lib/utils';
import { store } from '@/routes/login';

type Props = {
    status?: string;
    demoAccounts: { email: string; role: string }[] | null;
    demoPassword: string | null;
};

export default function Login({ status, demoAccounts, demoPassword }: Props) {
    return (
        <>
            <Head title="Log in" />

            {status && (
                <div className="mb-4 rounded-[10px] border border-accent/45 bg-accent/10 px-3 py-2.5 text-center text-sm font-medium text-status-open">
                    {status}
                </div>
            )}

            {demoAccounts !== null && demoPassword !== null && (
                <div className="mb-4 rounded-[10px] border border-primary/40 bg-primary/10 px-3 py-3 text-sm">
                    <p className="mb-2 font-semibold text-primary-soft">
                        Demo sign-in details
                    </p>
                    <ul className="grid gap-1 text-muted-foreground">
                        {demoAccounts.map((account) => (
                            <li key={account.email}>
                                <span className="font-medium text-foreground">
                                    {account.role}:
                                </span>{' '}
                                {account.email}
                            </li>
                        ))}
                    </ul>
                    <p className="mt-2 border-t border-line pt-2 text-muted-foreground">
                        Password:{' '}
                        <code className="font-semibold text-foreground">
                            {demoPassword}
                        </code>
                    </p>
                </div>
            )}

            <Form {...store.form()} resetOnSuccess={['password']}>
                {({ processing, errors }) => {
                    const error = errors.email ?? errors.password;

                    return (
                        <>
                            {error && (
                                <div
                                    role="alert"
                                    className="mb-4 flex items-center gap-2 rounded-[10px] border border-hard/45 bg-hard/10 px-3 py-2.5 text-sm text-[#fecaca]"
                                >
                                    <ShieldAlert
                                        aria-hidden
                                        className="size-[18px] shrink-0"
                                    />
                                    {error}
                                </div>
                            )}

                            <div className="mb-4 grid gap-[7px]">
                                <Label
                                    htmlFor="email"
                                    className={fieldLabelClassName}
                                >
                                    Work email
                                </Label>
                                <div className="relative flex items-center">
                                    <Mail
                                        aria-hidden
                                        className="pointer-events-none absolute left-[13px] size-[18px] text-primary"
                                    />
                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        required
                                        autoFocus
                                        autoComplete="username"
                                        placeholder="you@example.com"
                                        aria-invalid={
                                            errors.email ? true : undefined
                                        }
                                        className={cn(
                                            fieldInputClassName,
                                            'pl-[42px]',
                                        )}
                                    />
                                </div>
                            </div>

                            <div className="mb-4 grid gap-[7px]">
                                <Label
                                    htmlFor="password"
                                    className={fieldLabelClassName}
                                >
                                    Password
                                </Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    autoComplete="current-password"
                                    placeholder="••••••••"
                                    icon={KeyRound}
                                    aria-invalid={
                                        errors.password ? true : undefined
                                    }
                                    className={fieldInputClassName}
                                />
                            </div>

                            <div className="mt-0.5 mb-[22px] flex items-center gap-2.5 text-sm">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    defaultChecked
                                    className="size-[17px] border-line data-[state=checked]:border-accent data-[state=checked]:bg-accent data-[state=checked]:text-bg"
                                />
                                <Label
                                    htmlFor="remember"
                                    className="font-normal"
                                >
                                    Remember me
                                </Label>
                            </div>

                            <Button
                                type="submit"
                                size="lg"
                                className="h-auto w-full gap-2.5 py-3.5 font-title text-[21px] font-bold [&_svg:not([class*='size-'])]:size-5"
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing ? (
                                    <Spinner />
                                ) : (
                                    <Swords aria-hidden />
                                )}
                                Enter the board
                            </Button>

                            <p className="mt-5 flex items-center justify-center gap-2 border-t border-line pt-4 text-center text-[13.5px] text-muted-foreground">
                                <ScrollText
                                    aria-hidden
                                    className="size-4 shrink-0 text-primary"
                                />
                                No account? Ask your team admin to add you.
                            </p>
                        </>
                    );
                }}
            </Form>
        </>
    );
}

Login.layout = {
    title: 'Welcome back, adventurer.',
};
