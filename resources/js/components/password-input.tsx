import { Eye, EyeOff } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ComponentProps, Ref } from 'react';
import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

export default function PasswordInput({
    className,
    ref,
    icon: Icon,
    ...props
}: Omit<ComponentProps<'input'>, 'type'> & {
    ref?: Ref<HTMLInputElement>;
    /** Optional icon on the left (primary), as in the prototype login fields. */
    icon?: LucideIcon;
}) {
    const [showPassword, setShowPassword] = useState(false);

    return (
        <div className="relative flex items-center">
            {Icon && (
                <Icon
                    aria-hidden
                    className="pointer-events-none absolute left-[13px] size-[18px] text-primary"
                />
            )}
            <Input
                type={showPassword ? 'text' : 'password'}
                className={cn('pr-11', Icon && 'pl-[42px]', className)}
                ref={ref}
                {...props}
            />
            <button
                type="button"
                onClick={() => setShowPassword((prev) => !prev)}
                className="absolute right-1.5 grid size-[34px] place-items-center rounded-lg text-muted-foreground hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring focus-visible:outline-none"
                aria-label={showPassword ? 'Hide password' : 'Show password'}
            >
                {showPassword ? (
                    <EyeOff className="size-4" />
                ) : (
                    <Eye className="size-4" />
                )}
            </button>
        </div>
    );
}
