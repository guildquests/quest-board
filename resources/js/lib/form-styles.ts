// Form field styles from the prototype (.input / .text), shared by the auth screens and Settings.
export const fieldInputClassName =
    'h-auto rounded-[11px] border-line bg-bg/80 py-3 text-[15px] placeholder:text-muted-text/60 focus-visible:border-accent focus-visible:ring-[3px] focus-visible:ring-accent/20 focus-visible:shadow-[0_0_16px_color-mix(in_srgb,var(--color-accent)_20%,transparent)] md:text-[15px]';

/** Read-only field (e.g. the email in Settings › Profile). */
export const fieldReadOnlyClassName =
    'bg-bg/40 text-muted-foreground disabled:opacity-100';

export const fieldLabelClassName =
    'text-[13px] font-semibold tracking-[0.02em] text-muted-foreground';
