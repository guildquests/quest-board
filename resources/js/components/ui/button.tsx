import { Slot } from "@radix-ui/react-slot"
import { cva, type VariantProps } from "class-variance-authority"
import * as React from "react"

import { cn } from "@/lib/utils"

const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl text-sm font-semibold transition-[color,background-color,border-color,box-shadow,filter] disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 [&_svg]:shrink-0 outline-none focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive",
  {
    variants: {
      variant: {
        default:
          "bg-linear-to-b from-primary-soft to-primary text-on-primary shadow-[0_6px_22px_rgb(232_183_74/0.3),inset_0_1px_0_rgb(255_255_255/0.4)] hover:brightness-106 hover:shadow-[0_8px_30px_rgb(232_183_74/0.5),inset_0_1px_0_rgb(255_255_255/0.4)]",
        destructive:
          "bg-hard text-bg shadow-xs hover:bg-hard/90 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40",
        outline:
          "border border-line bg-panel/85 text-foreground hover:border-accent hover:shadow-[0_0_14px_color-mix(in_srgb,var(--color-accent)_35%,transparent)]",
        secondary:
          "border border-line bg-panel-2 text-secondary-foreground hover:border-accent/60",
        ghost:
          "border border-line bg-bg/60 text-muted-foreground hover:border-accent hover:text-foreground hover:shadow-[0_0_14px_color-mix(in_srgb,var(--color-accent)_35%,transparent)]",
        link: "text-primary-soft underline-offset-4 hover:underline",
      },
      size: {
        default: "h-10 px-4 py-2 has-[>svg]:px-3",
        sm: "h-8 rounded-[10px] px-3 has-[>svg]:px-2.5",
        lg: "h-12 px-6 text-base has-[>svg]:px-4",
        icon: "size-9 rounded-[10px]",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
)

function Button({
  className,
  variant,
  size,
  asChild = false,
  ...props
}: React.ComponentProps<"button"> &
  VariantProps<typeof buttonVariants> & {
    asChild?: boolean
  }) {
  const Comp = asChild ? Slot : "button"

  return (
    <Comp
      data-slot="button"
      className={cn(buttonVariants({ variant, size, className }))}
      {...props}
    />
  )
}

export { Button, buttonVariants }
