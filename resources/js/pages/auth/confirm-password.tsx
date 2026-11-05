import { Form, Head } from '@inertiajs/react';
import { KeyRound, ShieldAlert, ShieldCheck } from 'lucide-react';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { fieldInputClassName, fieldLabelClassName } from '@/lib/form-styles';
import { store } from '@/routes/password/confirm';

export default function ConfirmPassword() {
    return (
        <>
            <Head title="Confirm password" />

            <Form {...store.form()} resetOnSuccess={['password']}>
                {({ processing, errors }) => (
                    <>
                        {errors.password && (
                            <div
                                role="alert"
                                className="mb-4 flex items-center gap-2 rounded-[10px] border border-hard/45 bg-hard/10 px-3 py-2.5 text-sm text-[#fecaca]"
                            >
                                <ShieldAlert
                                    aria-hidden
                                    className="size-[18px] shrink-0"
                                />
                                {errors.password}
                            </div>
                        )}

                        <div className="mb-[22px] grid gap-[7px]">
                            <Label
                                htmlFor="password"
                                className={fieldLabelClassName}
                            >
                                Password
                            </Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                placeholder="••••••••"
                                autoComplete="current-password"
                                autoFocus
                                icon={KeyRound}
                                aria-invalid={
                                    errors.password ? true : undefined
                                }
                                className={fieldInputClassName}
                            />
                        </div>

                        <Button
                            size="lg"
                            className="h-auto w-full gap-2.5 py-3.5 font-title text-[21px] font-bold [&_svg:not([class*='size-'])]:size-5"
                            disabled={processing}
                            data-test="confirm-password-button"
                        >
                            {processing ? (
                                <Spinner />
                            ) : (
                                <ShieldCheck aria-hidden />
                            )}
                            Confirm
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}

ConfirmPassword.layout = {
    title: 'Confirm your password to continue.',
};
